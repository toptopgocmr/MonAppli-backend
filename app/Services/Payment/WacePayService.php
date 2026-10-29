<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * WacePay (Digitwace) – collecte Mobile Money + décaissement – TopTopGo
 *
 * Doc : https://docs.digitwace.com
 *
 * ── COLLECTE (WacePay PayIn) ─────────────────────────────────────────────
 *   Base : WACEPAY_PAYIN_BASE_URL (prod : https://payinws.wacepay.com/api/v1)
 *   1. GET  /payments/get-token     Authorization: Basic base64(public_key:private_key)
 *                                   → { status, message, token, expiresAt }
 *   2. POST /payments/create        Authorization: Bearer {token}
 *                                   wp-subscription-key: {subscription id}
 *      body : currency, amount, referenceId, customer_msisdn, customer_name,
 *             customer_email, countryCode, callback_url, operator
 *   3. GET  /payments/check-status  header referenceId: {notre référence}
 *   4. callback_url → POST /api/webhooks/wacepay/collect?token=WACEPAY_WEBHOOK_SECRET
 *      La doc ne décrit aucune signature : le webhook est protégé par un jeton
 *      secret dans l'URL ET le statut est toujours reconfirmé via check-status.
 *
 * ── DÉCAISSEMENT (WacePay Business) ──────────────────────────────────────
 *   Base : WACEPAY_PAYOUT_BASE_URL (fournie par Digitwace avec les accès)
 *   1. POST /login {email, password}                → access_token, expires_in
 *   2. POST /sender/create                          → code expéditeur (TopTopGo, créé
 *                                                     une fois : php artisan wacepay:create-sender)
 *   3. POST /beneficiary/create                     → code bénéficiaire (chauffeur / société)
 *   4. POST /transaction/payercode                  → payerCode du service
 *   5. POST /transaction/wallet/create              → référence (ex: WPMG7229671969)
 *   6. POST /transaction/confirm {reference}
 *   7. GET  /transaction/status/{reference}         → CANCEL|PENDING|WAITING CONFIRMATION|
 *                                                     PROCESSING|LOCKED|PAID
 *   Pas de webhook payout documenté : la commande wacepay:sync-payouts suit les statuts.
 */
class WacePayService implements PaymentProviderInterface
{
    protected array $cfg;

    public function __construct()
    {
        $this->cfg = (array) config('payments.wacepay', []);
    }

    protected function c(string $key, $default = null)
    {
        return data_get($this->cfg, $key, $default);
    }

    protected function timeout(): int
    {
        return (int) $this->c('timeout', 30);
    }

    /** Une réponse WacePay est OK si HTTP 2xx et le champ "status" du corps (s'il existe) vaut 2xx ou 2000. */
    protected function bodyOk($response): bool
    {
        if (!$response->successful()) {
            return false;
        }
        $status = $response->json('status') ?? $response->json('stauts');
        if ($status === null || $status === true) {
            return true;
        }
        if (is_numeric($status)) {
            $s = (int) $status;
            return ($s >= 200 && $s < 300) || $s === 2000;
        }
        return in_array(strtolower((string) $status), ['success', 'ok', 'successful'], true);
    }

    protected function errorMessage($response, string $fallback): string
    {
        $json = $response->json();
        if (is_array($json)) {
            if (!empty($json['errors']) && is_array($json['errors'])) {
                $first = collect($json['errors'])->flatten()->first();
                if ($first) {
                    return (string) $first;
                }
            }
            foreach (['message', 'messages', 'error', 'msg'] as $k) {
                if (!empty($json[$k]) && is_string($json[$k])) {
                    return $json[$k] . (isset($json['code']) ? ' (code ' . $json['code'] . ')' : '');
                }
            }
            if (isset($json['status']) && is_numeric($json['status']) && $msg = $this->codeMessage((int) $json['status'])) {
                return $msg;
            }
        }
        return $fallback . ' (HTTP ' . $response->status() . ')';
    }

    /** Codes système WacePay Business (doc "List code system"). */
    protected function codeMessage(int $code): ?string
    {
        return [
            1001 => 'Accès non autorisé (WacePay 1001).',
            1002 => 'Donnée introuvable (WacePay 1002).',
            1003 => 'Ressource non autorisée (WacePay 1003).',
            1004 => 'Erreur de validation des données (WacePay 1004).',
            1005 => 'Pays inexistant ou désactivé (WacePay 1005).',
            1006 => 'Type de pièce d\'identité invalide (WacePay 1006).',
            1007 => 'Utilisateur inexistant ou désactivé (WacePay 1007).',
            1008 => 'Expéditeur inexistant ou désactivé (WacePay 1008).',
            1009 => 'Ville inexistante ou désactivée (WacePay 1009).',
            1010 => 'Devise inexistante ou désactivée (WacePay 1010).',
            1011 => 'Pays/ville ne correspondent pas au bénéficiaire (WacePay 1011).',
            1012 => 'Type de paiement inexistant ou désactivé (WacePay 1012).',
            1013 => 'Aucune commission configurée pour cette destination (WacePay 1013).',
            1014 => 'Point de paiement inexistant ou désactivé (WacePay 1014).',
            1015 => 'Relation expéditeur/bénéficiaire invalide (WacePay 1015).',
            1016 => 'Motif d\'envoi invalide (WacePay 1016).',
            1017 => 'Origine des fonds invalide (WacePay 1017).',
            2001 => 'Erreur système WacePay (2001).',
            3001 => 'Erreur système WacePay (3001).',
            3002 => 'Compte ou solde WacePay non activé (3002).',
            3015 => 'Référence obligatoire (WacePay 3015).',
            3016 => 'Référence introuvable (WacePay 3016).',
            4001 => 'Accès restreint (WacePay 4001).',
        ][$code] ?? null;
    }

    // =====================================================================
    // Disponibilité / couverture
    // =====================================================================

    public function getProviderName(): string
    {
        return 'wacepay';
    }

    public function isAvailable(): bool
    {
        return $this->isCollectConfigured();
    }

    public function isCollectConfigured(): bool
    {
        return filled($this->c('payin.base_url'))
            && filled($this->c('payin.public_key'))
            && filled($this->c('payin.private_key'))
            && filled($this->c('payin.subscription_key'));
    }

    public function isPayoutConfigured(): bool
    {
        return filled($this->c('payout.base_url'))
            && filled($this->c('payout.email'))
            && filled($this->c('payout.password'))
            && filled($this->c('payout.sender_code'));
    }

    protected function countries(string $key): array
    {
        return array_map('strtoupper', array_map('trim', (array) $this->c($key, [])));
    }

    /** Devise locale du pays (XAF pour la CEMAC, XOF pour l'UEMOA), null si corridor non géré. */
    public function currencyFor(?string $country): ?string
    {
        return $country ? ($this->c('currencies', [])[strtoupper($country)] ?? null) : null;
    }

    public function supportsCollectFor(?string $country): bool
    {
        return $this->isCollectConfigured() && $country && $this->currencyFor($country)
            && in_array(strtoupper($country), $this->countries('payin.countries'), true);
    }

    public function supportsPayoutFor(?string $country): bool
    {
        return $this->isPayoutConfigured() && $country && $this->currencyFor($country)
            && in_array(strtoupper($country), $this->countries('payout.countries'), true);
    }

    // =====================================================================
    // COLLECTE
    // =====================================================================

    protected function payinUrl(string $path): string
    {
        return rtrim((string) $this->c('payin.base_url'), '/') . '/' . ltrim($path, '/');
    }

    protected function payinToken(bool $fresh = false): ?string
    {
        $cacheKey = 'wacepay.payin.token.' . md5((string) $this->c('payin.public_key'));
        if ($fresh) {
            Cache::forget($cacheKey);
        }
        if ($token = Cache::get($cacheKey)) {
            return $token;
        }

        $basic = base64_encode($this->c('payin.public_key') . ':' . $this->c('payin.private_key'));
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $basic,
            'Accept'        => 'application/json',
        ])->timeout($this->timeout())->get($this->payinUrl('payments/get-token'));

        $token = $response->json('token') ?? $response->json('data.token') ?? $response->json('access_token');
        if (!$response->successful() || !$token) {
            Log::error('WacePay PayIn get-token échoué', ['http' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);
            return null;
        }

        $ttl = 3000; // 50 min par défaut
        if ($exp = $response->json('expiresAt') ?? $response->json('data.expiresAt')) {
            try {
                $ttl = max(60, now()->diffInSeconds(\Carbon\Carbon::parse($exp), false) - 60);
            } catch (\Throwable) {
            }
        }
        Cache::put($cacheKey, $token, (int) $ttl);

        return $token;
    }

    protected function payinRequest(string $method, string $path, array $body = [], array $headers = [])
    {
        $send = function (bool $fresh) use ($method, $path, $body, $headers) {
            $token = $this->payinToken($fresh);
            if (!$token) {
                return null;
            }
            $req = Http::withHeaders(array_merge([
                'Authorization'        => 'Bearer ' . $token,
                'wp-subscription-key'  => (string) $this->c('payin.subscription_key'),
                'Accept'               => 'application/json',
                'Content-Type'         => 'application/json',
            ], $headers))->timeout($this->timeout());

            return $method === 'GET'
                ? $req->get($this->payinUrl($path))
                : $req->post($this->payinUrl($path), $body);
        };

        $response = $send(false);
        if ($response && $response->status() === 401) {
            $response = $send(true); // jeton expiré → on le régénère une fois
        }
        return $response;
    }

    /** Code opérateur attendu par WacePay (surchargeable via WACEPAY_OPERATOR_MAP). */
    public function resolveOperator(string $operator, string $country): string
    {
        $op = strtoupper($operator);
        $base = match (true) {
            str_contains($op, 'MTN')        => 'MTN',
            str_contains($op, 'ORANGE')     => 'ORANGE',
            str_contains($op, 'AIRTELTIGO') => 'AIRTELTIGO',
            str_contains($op, 'AIRTEL')     => 'AIRTEL',
            str_contains($op, 'MOOV')       => 'MOOV',
            str_contains($op, 'WAVE')       => 'WAVE',
            str_contains($op, 'EXPRESS') || $op === 'EU' => 'EU',
            default                         => preg_replace('/[^A-Z]/', '', $op) ?: 'MTN',
        };

        $map = (array) $this->c('operator_map', []);
        return $map[strtoupper($country) . '.' . $base] ?? $map[$base] ?? $base;
    }

    /**
     * $data : reference, amount, currency, phone, country, operator,
     *         customer_name, email
     */
    public function collect(array $data): array
    {
        if (!$this->isCollectConfigured()) {
            return ['success' => false, 'error' => 'WacePay non configuré (WACEPAY_PAYIN_* manquants).'];
        }

        $reference = (string) $data['reference'];
        $country   = strtoupper($data['country'] ?? 'CG');
        $operator  = $this->resolveOperator($data['operator'] ?? 'MTN', $country);
        $msisdn    = $this->msisdn($data['phone'] ?? '', $country, (string) $this->c('payin.msisdn_format', 'international'));

        $payload = [
            'currency'        => $this->currencyFor($country) ?? ($data['currency'] ?? 'XAF'),
            'amount'          => (int) $data['amount'],
            'referenceId'     => $reference,
            'customer_msisdn' => (int) $msisdn,
            'customer_name'   => $data['customer_name'] ?? 'Client TopTopGo',
            'customer_email'  => $data['email'] ?? 'client@toptopgo.app',
            'countryCode'     => $country,
            'callback_url'    => $this->callbackUrl(),
            'operator'        => $operator,
        ];

        try {
            $response = $this->payinRequest('POST', 'payments/create', $payload);

            if (!$response) {
                return ['success' => false, 'error' => 'Authentification WacePay impossible (vérifiez les clés).'];
            }

            if (!$this->bodyOk($response)) {
                Log::warning('WacePay collect refusé', ['reference' => $reference, 'http' => $response->status(), 'body' => mb_substr($response->body(), 0, 1000)]);
                return [
                    'success'      => false,
                    'error'        => $this->errorMessage($response, 'Initialisation WacePay échouée'),
                    'data'         => $response->json(),
                    'http_status'  => $response->status(),
                    'request_sent' => array_merge($payload, ['customer_email' => '***']),
                ];
            }

            $d = (array) ($response->json('data') ?? []);

            return [
                'success'        => true,
                'transaction_id' => (string) ($d['transactionId'] ?? $d['transaction_id'] ?? $d['id'] ?? $d['reference'] ?? $reference),
                'reference'      => $reference,
                'status'         => $this->mapStatus((string) ($d['status'] ?? 'pending')),
                'instruction'    => $d['message'] ?? $response->json('message') ?? 'Autorisez le paiement sur votre téléphone Mobile Money.',
                'payment_url'    => $d['payment_url'] ?? $d['paymentUrl'] ?? $d['url'] ?? null,
                'data'           => $d,
            ];
        } catch (Exception $e) {
            Log::error('WacePay collect error: ' . $e->getMessage(), ['reference' => $reference]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /** Statut d'une collecte — $transactionId = NOTRE référence (referenceId). */
    public function getTransactionStatus(string $transactionId): array
    {
        try {
            $response = $this->payinRequest('GET', 'payments/check-status', [], ['referenceId' => $transactionId]);

            if (!$response || !$response->successful()) {
                return [
                    'success'     => false,
                    'error'       => $response ? $this->errorMessage($response, 'Transaction introuvable') : 'Authentification WacePay impossible',
                    'http_status' => $response?->status(),
                ];
            }

            $d   = (array) ($response->json('data') ?? $response->json());
            $raw = (string) ($d['status'] ?? $d['transaction_status'] ?? $d['state'] ?? 'pending');

            return [
                'success'    => true,
                'status'     => $this->mapStatus($raw),
                'raw_status' => $raw,
                'reason'     => $this->failureReason($d),
                'data'       => $d,
            ];
        } catch (Exception $e) {
            Log::error('WacePay status error: ' . $e->getMessage(), ['reference' => $transactionId]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function callbackUrl(): string
    {
        $url = (string) ($this->c('payin.callback_url') ?: rtrim((string) config('app.url'), '/') . '/api/webhooks/wacepay/collect');
        if ($secret = $this->c('webhook_secret')) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'token=' . urlencode($secret);
        }
        return $url;
    }

    public function verifyWebhookToken(?string $token): bool
    {
        $secret = (string) $this->c('webhook_secret');
        if ($secret === '') {
            Log::error('WacePay webhook : WACEPAY_WEBHOOK_SECRET non configuré');
            return false; // fail closed
        }
        return $token !== null && hash_equals($secret, $token);
    }

    /** Normalise le callback de collecte (format non documenté → lecture tolérante). */
    public function handleWebhook(array $payload): array
    {
        $d = isset($payload['data']) && is_array($payload['data']) ? array_merge($payload, $payload['data']) : $payload;

        $references = array_values(array_unique(array_filter(array_map(
            fn ($v) => is_scalar($v) ? (string) $v : null,
            [
                $d['referenceId'] ?? null, $d['reference_id'] ?? null, $d['reference'] ?? null,
                $d['merchant_reference'] ?? null, $d['transactionId'] ?? null, $d['transaction_id'] ?? null,
            ]
        ))));

        if (empty($references)) {
            return ['success' => false, 'error' => 'Référence absente du callback WacePay'];
        }

        $raw = (string) ($d['status'] ?? $d['transaction_status'] ?? $d['state'] ?? 'pending');

        return [
            'success'    => true,
            'status'     => $this->mapStatus($raw),
            'raw_status' => $raw,
            'references' => $references,
            'reason'     => $this->failureReason($d),
            'data'       => $d,
        ];
    }

    /** → success | failed | cancelled | pending */
    public function mapStatus(string $status): string
    {
        $s = strtoupper(trim($status));
        if (is_numeric($s)) {
            return in_array((int) $s, [200, 2000], true) ? 'success' : 'pending';
        }
        return match (true) {
            in_array($s, ['SUCCESS', 'SUCCESSFUL', 'SUCCEEDED', 'COMPLETE', 'COMPLETED', 'PAID', 'APPROVED', 'VALIDATED'], true) => 'success',
            in_array($s, ['FAILED', 'FAILURE', 'FAIL', 'ERROR', 'REJECTED', 'DECLINED'], true)                                   => 'failed',
            str_starts_with($s, 'CANCEL') || in_array($s, ['EXPIRED', 'ABANDONED', 'TIMEOUT'], true)                             => 'cancelled',
            default                                                                                                              => 'pending',
        };
    }

    protected function failureReason(array $d): ?string
    {
        $reason = $d['failure_reason'] ?? $d['reason'] ?? $d['status_reason'] ?? $d['message'] ?? $d['messages'] ?? null;
        return is_string($reason) && $reason !== '' ? $this->humanizeFailureReason($reason) : null;
    }

    public function humanizeFailureReason(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }
        $r = strtolower($raw);

        return match (true) {
            str_contains($r, 'insufficient') || str_contains($r, 'insuffisant') => 'Solde Mobile Money insuffisant.',
            str_contains($r, 'cancel') || str_contains($r, 'annul')             => 'Paiement annulé sur le téléphone.',
            str_contains($r, 'timeout') || str_contains($r, 'expired') || str_contains($r, 'expir') => 'Délai de confirmation dépassé. Veuillez réessayer.',
            str_contains($r, 'msisdn') || str_contains($r, 'phone') || str_contains($r, 'numéro') => 'Numéro de téléphone invalide pour ce moyen de paiement.',
            str_contains($r, 'operator') || str_contains($r, 'opérateur')       => 'Ce moyen de paiement n\'est pas disponible pour ce pays.',
            default                                                             => $raw,
        };
    }

    // =====================================================================
    // DÉCAISSEMENT
    // =====================================================================

    protected function payoutUrl(string $path): string
    {
        return rtrim((string) $this->c('payout.base_url'), '/') . '/' . ltrim($path, '/');
    }

    protected function payoutToken(bool $fresh = false): ?string
    {
        $cacheKey = 'wacepay.payout.token.' . md5((string) $this->c('payout.email'));
        if ($fresh) {
            Cache::forget($cacheKey);
        }
        if ($token = Cache::get($cacheKey)) {
            return $token;
        }

        $response = Http::acceptJson()->asJson()->timeout($this->timeout())
            ->post($this->payoutUrl('login'), [
                'email'    => $this->c('payout.email'),
                'password' => $this->c('payout.password'),
            ]);

        $token = $response->json('access_token') ?? $response->json('data.access_token') ?? $response->json('token');
        if (!$response->successful() || !$token) {
            Log::error('WacePay Business login échoué', ['http' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);
            return null;
        }

        $ttl = max(60, (int) ($response->json('expires_in') ?? 3600) - 60);
        Cache::put($cacheKey, $token, $ttl);

        return $token;
    }

    protected function payoutRequest(string $method, string $path, array $body = [])
    {
        $send = function (bool $fresh) use ($method, $path, $body) {
            $token = $this->payoutToken($fresh);
            if (!$token) {
                return null;
            }
            $req = Http::withToken($token)->acceptJson()->asJson()->timeout($this->timeout());
            return $method === 'GET' ? $req->get($this->payoutUrl($path)) : $req->post($this->payoutUrl($path), $body);
        };

        $response = $send(false);
        if ($response && $response->status() === 401) {
            $response = $send(true);
        }
        return $response;
    }

    /** Appel générique à l'API Business : ['ok'=>bool, 'json'=>array, 'error'=>?string]. */
    public function business(string $method, string $path, array $body = []): array
    {
        try {
            $response = $this->payoutRequest($method, $path, $body);
            if (!$response) {
                return ['ok' => false, 'json' => [], 'error' => 'Connexion WacePay Business impossible (identifiants ?).'];
            }
            $ok = $this->bodyOk($response);
            if (!$ok) {
                Log::warning('WacePay Business ' . $path . ' refusé', ['http' => $response->status(), 'body' => mb_substr($response->body(), 0, 1000)]);
            }
            return [
                'ok'    => $ok,
                'json'  => (array) $response->json(),
                'error' => $ok ? null : $this->errorMessage($response, 'Appel WacePay ' . $path . ' échoué'),
            ];
        } catch (Exception $e) {
            Log::error('WacePay Business ' . $path . ' exception: ' . $e->getMessage());
            return ['ok' => false, 'json' => [], 'error' => $e->getMessage()];
        }
    }

    /** Cherche un "code"/"reference" dans une réponse aux formes variables. */
    protected function extractCode(array $json, array $keys): ?string
    {
        foreach ($keys as $k) {
            foreach (['', 'data.', 'sender.', 'beneficiary.', 'transaction.', 'result.'] as $prefix) {
                $v = data_get($json, $prefix . $k);
                if (is_scalar($v) && $v !== '') {
                    return (string) $v;
                }
            }
        }
        return null;
    }

    /** Crée l'expéditeur TopTopGo (utilisé une fois via php artisan wacepay:create-sender). */
    public function createSender(array $s): array
    {
        $res = $this->business('POST', 'sender/create', $s);
        return [
            'success' => $res['ok'],
            'code'    => $res['ok'] ? $this->extractCode($res['json'], ['senderCode', 'code', 'reference', 'codeSender']) : null,
            'error'   => $res['error'],
            'data'    => $res['json'],
        ];
    }

    /**
     * Crée (ou met à jour) le bénéficiaire et renvoie son code. Mis en cache
     * pour ne pas recréer le bénéficiaire à chaque retrait.
     */
    public function beneficiaryCode(array $b): array
    {
        $cacheKey = 'wacepay.beneficiary.' . md5(json_encode([$b['key'] ?? null, $b['phone'], $b['country'], $b['city']]));
        if ($code = Cache::get($cacheKey)) {
            return ['success' => true, 'code' => $code];
        }

        $defaults = (array) $this->c('payout.beneficiary_defaults', []);
        $payload = [
            'firstName'      => $b['first_name'] ?: 'Beneficiaire',
            'lastName'       => $b['last_name'] ?: 'TopTopGo',
            'address'        => $b['address'] ?: ($b['city'] . ', ' . $b['country']),
            'phone'          => $b['phone'],
            'country'        => $b['country'],
            'city'           => $b['city'],
            'email'          => $b['email'] ?? null,
            'idNumber'       => $b['id_number'] ?: ($defaults['id_number_prefix'] ?? 'TTG-') . ($b['key'] ?? $b['phone']),
            'idType'         => $b['id_type'] ?: ($defaults['id_type'] ?? 'CNI'),
            'dateOfBirth'    => $this->ymd($b['date_of_birth'] ?? null) ?? ($defaults['date_of_birth'] ?? '1990-01-01'),
            'dateExpireId'   => $this->ymd($b['date_expire_id'] ?? null) ?? now()->addYears(5)->format('Y-m-d'),
            'pep'            => false,
            'updateIfExist'  => true,
        ];
        $payload = array_filter($payload, fn ($v) => $v !== null && $v !== '');

        $res = $this->business('POST', 'beneficiary/create', $payload);
        $code = $res['ok'] ? $this->extractCode($res['json'], ['beneficiaryCode', 'code', 'reference', 'codeBeneficiary']) : null;

        if (!$code) {
            return ['success' => false, 'error' => $res['error'] ?? 'Code bénéficiaire absent de la réponse WacePay', 'data' => $res['json']];
        }

        Cache::put($cacheKey, $code, now()->addDays(30));
        return ['success' => true, 'code' => $code];
    }

    /** Liste des services de décaissement d'un pays (POST /transaction/payoutservice). */
    public function payoutServices(string $country, string $currency = 'XAF'): array
    {
        return $this->business('POST', 'transaction/payoutservice', [
            'payoutCountry'  => strtoupper($country),
            'payoutCurrency' => $currency,
        ]);
    }

    /** Code du service (wallet) WacePay pour un opérateur, depuis la config ou l'API. */
    public function resolvePayoutService(string $operator, string $country, string $currency = 'XAF'): ?string
    {
        $base = $this->resolveOperator($operator, $country);
        $map  = (array) $this->c('payout.services', []);
        if ($code = $map[strtoupper($country) . '.' . $base] ?? $map[$base] ?? null) {
            return $code;
        }

        return Cache::remember('wacepay.service.' . $country . '.' . $base . '.' . $currency, now()->addHours(12), function () use ($base, $country, $currency) {
            $res = $this->payoutServices($country, $currency);
            if (!$res['ok']) {
                return null;
            }
            $list = $res['json']['data'] ?? $res['json']['services'] ?? $res['json']['service'] ?? $res['json']['transaction'] ?? $res['json'];
            foreach ((array) $list as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $haystack = strtoupper(json_encode($item));
                if (str_contains($haystack, $base)) {
                    return (string) ($item['code'] ?? $item['service'] ?? $item['serviceCode'] ?? $item['name'] ?? '') ?: null;
                }
            }
            return null;
        });
    }

    public function resolvePayerCode(string $service, string $country, string $currency = 'XAF'): ?string
    {
        return Cache::remember('wacepay.payercode.' . $country . '.' . $service . '.' . $currency, now()->addHours(12), function () use ($service, $country, $currency) {
            $res = $this->business('POST', 'transaction/payercode', [
                'payoutCountry'  => strtoupper($country),
                'payoutService'  => $service,
                'CurrencyPayout' => $currency,
            ]);
            if (!$res['ok']) {
                return null;
            }
            $list = $res['json']['transaction'] ?? $res['json']['data'] ?? [];
            $first = is_array($list) ? (array_is_list($list) ? ($list[0] ?? []) : $list) : [];
            $code = $first['payerCode'] ?? $first['payercode'] ?? $first['code'] ?? null;
            return $code !== null ? (string) $code : null;
        });
    }

    /**
     * Décaissement Mobile Money.
     *
     * $data : reference (interne), phone, amount, currency, country, city, operator,
     *         first_name, last_name, beneficiary_key, id_number, id_type,
     *         date_of_birth, date_expire_id, email, address
     */
    public function payout(array $data): array
    {
        if (!$this->isPayoutConfigured()) {
            return ['success' => false, 'error' => 'Décaissement WacePay non configuré (WACEPAY_PAYOUT_* / WACEPAY_SENDER_CODE).'];
        }

        $country  = strtoupper($data['country'] ?? 'CG');
        // Devise reçue = devise du pays (XAF/XOF) ; devise envoyée = celle du compte TopTopGo.
        $currency     = $this->currencyFor($country) ?? ($data['currency'] ?? 'XAF');
        $fromCurrency = (string) $this->c('payout.from_currency', 'XAF');
        $city     = ($data['city'] ?? null) ?: (string) data_get($this->cfg, 'payout.default_city.' . $country, 'Brazzaville');
        $phone    = $this->msisdn($data['phone'] ?? '', $country, (string) $this->c('payout.msisdn_format', 'international'));

        $service = $this->resolvePayoutService($data['operator'] ?? 'MTN', $country, $currency);
        if (!$service) {
            return ['success' => false, 'error' => "Aucun service WacePay trouvé pour l'opérateur " . ($data['operator'] ?? '?') . " ($country). Renseignez WACEPAY_PAYOUT_SERVICES."];
        }

        $benef = $this->beneficiaryCode([
            'key'            => $data['beneficiary_key'] ?? null,
            'first_name'     => $data['first_name'] ?? null,
            'last_name'      => $data['last_name'] ?? null,
            'address'        => $data['address'] ?? null,
            'phone'          => $phone,
            'country'        => $country,
            'city'           => $city,
            'email'          => $data['email'] ?? null,
            'id_number'      => $data['id_number'] ?? null,
            'id_type'        => $data['id_type'] ?? null,
            'date_of_birth'  => $data['date_of_birth'] ?? null,
            'date_expire_id' => $data['date_expire_id'] ?? null,
        ]);
        if (!$benef['success']) {
            return ['success' => false, 'error' => 'Bénéficiaire WacePay : ' . $benef['error']];
        }

        $payload = [
            'payoutCountry'       => $country,
            'payoutCity'          => $city,
            'receiveCurrency'     => $currency,
            'amountToPaid'        => (string) (int) $data['amount'],
            'service'             => $service,
            'senderCode'          => (string) $this->c('payout.sender_code'),
            'beneficiaryCode'     => $benef['code'],
            'sendingCurrency'     => $fromCurrency,
            'mobileReceiveNumber' => $phone,
            'fromCountry'         => strtoupper((string) $this->c('payout.from_country', 'CG')),
            'originFund'          => (string) $this->c('payout.origin_fund'),
            'reason'              => (string) $this->c('payout.reason'),
            'relation'            => (string) $this->c('payout.relation'),
        ];
        if ($payerCode = $this->resolvePayerCode($service, $country, $currency)) {
            $payload['payerCode'] = $payerCode;
        }

        $create = $this->business('POST', 'transaction/wallet/create', $payload);
        $wpRef = $create['ok'] ? $this->extractCode($create['json'], ['reference', 'transactionReference', 'code', 'transactionCode']) : null;

        if (!$wpRef) {
            return [
                'success'      => false,
                'error'        => $create['error'] ?? 'Référence de transaction absente de la réponse WacePay',
                'data'         => $create['json'],
                'request_sent' => array_merge($payload, ['mobileReceiveNumber' => '***']),
            ];
        }

        // Confirmation (sinon la transaction reste en WAITING CONFIRMATION)
        $confirm = $this->business('POST', 'transaction/confirm', ['reference' => $wpRef]);
        if (!$confirm['ok']) {
            Log::error('WacePay confirm échoué', ['reference' => $wpRef, 'internal' => $data['reference'] ?? null, 'error' => $confirm['error']]);
            return [
                'success'      => false,
                'error'        => 'Transaction WacePay ' . $wpRef . ' créée mais non confirmée : ' . $confirm['error'],
                'reference'    => $wpRef,
                'needs_review' => true,
            ];
        }

        Log::info('WacePay payout confirmé', ['reference' => $wpRef, 'internal' => $data['reference'] ?? null, 'amount' => $data['amount']]);

        return [
            'success'        => true,
            'reference'      => $wpRef,
            'transaction_id' => $wpRef,
            'status'         => 'pending',
            'data'           => ['create' => $create['json'], 'confirm' => $confirm['json']],
        ];
    }

    /** Statut d'un décaissement (GET /transaction/status/{reference}). */
    public function getPayoutStatus(string $reference): array
    {
        $res = $this->business('GET', 'transaction/status/' . urlencode($reference));
        if (!$res['ok']) {
            return ['success' => false, 'error' => $res['error']];
        }
        $raw = strtoupper((string) (data_get($res['json'], 'transaction.status') ?? data_get($res['json'], 'data.status') ?? 'PENDING'));

        return [
            'success'    => true,
            'raw_status' => $raw,                              // CANCEL|PENDING|WAITING CONFIRMATION|PROCESSING|LOCKED|PAID
            'status'     => match (true) {
                $raw === 'PAID'                  => 'success',
                str_starts_with($raw, 'CANCEL')  => 'failed',
                default                          => 'pending',
            },
            'data'       => $res['json'],
        ];
    }

    public function balance(): array
    {
        return $this->business('GET', 'account/balance');
    }

    // =====================================================================

    /** Date quelconque (Carbon, string…) → Y-m-d, ou null. */
    protected function ymd($value): ?string
    {
        if (!$value) {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function callingCode(string $country): string
    {
        return match (strtoupper($country)) {
            'CM' => '237', 'CG' => '242', 'GA' => '241', 'TD' => '235',
            'CF' => '236', 'GQ' => '240', 'CI' => '225', 'SN' => '221',
            'NG' => '234', 'CD' => '243', 'BJ' => '229', 'TG' => '228',
            'BF' => '226', 'ML' => '223', 'NE' => '227', 'GN' => '224',
            'GW' => '245', 'GH' => '233', 'SL' => '232', 'LR' => '231',
            'GM' => '220', 'MR' => '222', 'CV' => '238',
            default => '242',
        };
    }

    /**
     * Numéro en chiffres : 'international' → 242061234567 ; 'local' → 061234567.
     * Le 0 initial des numéros congolais (9 chiffres) est conservé.
     */
    public function msisdn(string $phone, string $country, string $format = 'international'): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        $cc = $this->callingCode($country);
        $local = (str_starts_with($digits, $cc) && strlen($digits) > 9) ? substr($digits, strlen($cc)) : $digits;

        return $format === 'local' ? $local : $cc . $local;
    }
}
