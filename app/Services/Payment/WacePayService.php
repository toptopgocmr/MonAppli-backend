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
 * Une seule plateforme (WacePay PayIn — tableau de bord sandbox-payin.wacepay.io)
 * pour la COLLECTE (payin) et le DÉCAISSEMENT (payout). Ce n'est PAS l'API
 * WacePay Business (remittance avec expéditeur/bénéficiaire), non utilisée ici.
 *
 *   Base : WACEPAY_PAYIN_BASE_URL
 *          sandbox : https://sandbox-payinws.wacepay.io/api/v1
 *          prod    : https://payinws.wacepay.com/api/v1
 *   Auth : GET /payments/get-token   Authorization: Basic base64(public_key:private_key)
 *          → { token, expiresAt } puis Authorization: Bearer {token}
 *          + header wp-subscription-key. Les IP du serveur doivent être
 *          autorisées dans le tableau de bord (API Integration → IP).
 *
 * ── COLLECTE ─────────────────────────────────────────────────────────────
 *   POST /payments/create      currency, amount, referenceId, customer_msisdn, customer_name,
 *                              customer_email, countryCode, callback_url, operator
 *   GET  /payments/check-status  header referenceId
 *   Webhook (callback_url + webhook du tableau de bord, évènements transaction.*)
 *     → POST /api/webhooks/wacepay/collect?token=WACEPAY_WEBHOOK_SECRET
 *     Pas de signature documentée : jeton secret + reconfirmation via check-status.
 *
 * ── DÉCAISSEMENT ─────────────────────────────────────────────────────────
 *   GET  /payout/customer/account          soldes par devise (compte à approvisionner)
 *   GET  /payout/customer/services         services souscrits (subscriptionId, pays, devise, frais)
 *   POST /payout/customer/execute          { amount, recipientMsisdn, recipientName, payoutSubscriptionId }
 *                                          → status 2000/2001
 *   GET  /payout/customer/transactions/{id} statut PENDING|PROCESSING|SUCCESS|FAILED|CANCELLED
 *   Pas d'évènement webhook payout : la commande wacepay:sync-payouts suit les statuts.
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

    /** Une réponse WacePay est OK si HTTP 2xx et le champ "status" du corps (s'il existe) vaut 2xx, 2000 ou 2001. */
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
            // Plateforme PayIn : 2000 = OK, 2001 = opération initiée (ex. payout) — cf. tableau de bord.
            return ($s >= 200 && $s < 300) || $s === 2000 || $s === 2001;
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

    /** Codes d'erreur WacePay (doc Digitwace "List code system"). */
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
        // Même plateforme et mêmes clés que la collecte.
        return $this->isCollectConfigured() && (bool) $this->c('payout.enabled', true);
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
    // DÉCAISSEMENT (module Payout de la plateforme WacePay PayIn)
    // =====================================================================

    /** Requête authentifiée sur l'API PayIn (même jeton que la collecte). */
    protected function payin(string $method, string $path, array $body = [], array $query = []): array
    {
        try {
            $send = function (bool $fresh) use ($method, $path, $body, $query) {
                $token = $this->payinToken($fresh);
                if (!$token) {
                    return null;
                }
                $req = Http::withHeaders([
                    'Authorization'       => 'Bearer ' . $token,
                    'wp-subscription-key' => (string) $this->c('payin.subscription_key'),
                    'Accept'              => 'application/json',
                    'Content-Type'        => 'application/json',
                ])->timeout($this->timeout());

                return $method === 'GET'
                    ? $req->get($this->payinUrl($path), $query)
                    : $req->post($this->payinUrl($path), $body);
            };

            $response = $send(false);
            if ($response && $response->status() === 401) {
                $response = $send(true);
            }
            if (!$response) {
                return ['ok' => false, 'json' => [], 'error' => 'Authentification WacePay impossible (clés ou IP non autorisée ?).', 'http' => null];
            }

            $ok = $this->bodyOk($response);
            if (!$ok) {
                Log::warning('WacePay ' . $path . ' refusé', ['http' => $response->status(), 'body' => mb_substr($response->body(), 0, 1000)]);
            }

            return [
                'ok'    => $ok,
                'json'  => (array) $response->json(),
                'error' => $ok ? null : $this->errorMessage($response, 'Appel WacePay ' . $path . ' échoué'),
                'http'  => $response->status(),
            ];
        } catch (Exception $e) {
            Log::error('WacePay ' . $path . ' exception: ' . $e->getMessage());
            return ['ok' => false, 'json' => [], 'error' => $e->getMessage(), 'http' => null];
        }
    }

    /** Extrait une liste d'éléments d'une réponse { status, data: [...] } ou { data: { items|data|rows: [...] } }. */
    protected function listFrom(array $json): array
    {
        foreach (['data.items', 'data.data', 'data.rows', 'data.transactions', 'data.services', 'data', 'items', 'services'] as $k) {
            $v = data_get($json, $k);
            if (is_array($v) && array_is_list($v)) {
                return $v;
            }
        }
        return [];
    }

    /** GET payout/customer/services → services de décaissement souscrits (subscriptionId, name, countryCode, currency, fixedFee, rate, min/maxAmount, active). */
    public function payoutServices(): array
    {
        $res = $this->payin('GET', 'payout/customer/services');
        return $res['ok'] ? $this->listFrom($res['json']) : [];
    }

    /** GET payout/customer/account → comptes (solde disponible par devise). */
    public function payoutAccount(): array
    {
        return $this->payin('GET', 'payout/customer/account');
    }

    /**
     * Identifiant de souscription (payoutSubscriptionId) du service à utiliser
     * pour un opérateur/pays/devise. Config WACEPAY_PAYOUT_SUBSCRIPTIONS
     * prioritaire, sinon recherche dans payout/customer/services.
     */
    public function resolvePayoutSubscription(string $operator, string $country, string $currency): ?array
    {
        $base = $this->resolveOperator($operator, $country);
        $map  = (array) $this->c('payout.subscriptions', []);
        if ($id = $map[strtoupper($country) . '.' . $base] ?? null) {
            return ['subscriptionId' => (string) $id];
        }

        $services = Cache::remember('wacepay.payout.services', now()->addMinutes(30), fn () => $this->payoutServices());

        foreach ($services as $s) {
            if (!is_array($s) || (array_key_exists('active', $s) && !$s['active'])) {
                continue;
            }
            $sCountry  = strtoupper((string) ($s['countryCode'] ?? $s['country_code'] ?? ''));
            $sCurrency = strtoupper((string) ($s['currency'] ?? ''));
            $sName     = strtoupper((string) ($s['name'] ?? $s['serviceName'] ?? ''));

            if (($sCountry === '' || $sCountry === strtoupper($country))
                && ($sCurrency === '' || $sCurrency === strtoupper($currency))
                && str_contains(preg_replace('/[^A-Z]/', '', $sName), $base)
                && !empty($s['subscriptionId'])) {
                return $s;
            }
        }
        return null;
    }

    /**
     * Décaissement Mobile Money : POST payout/customer/execute
     *   { amount, recipientMsisdn, recipientName, payoutSubscriptionId }
     *   → status 2000 / 2001 = décaissement initié.
     *
     * $data : reference (interne, pour les logs), phone, amount, country, operator, recipient_name
     */
    public function payout(array $data): array
    {
        if (!$this->isPayoutConfigured()) {
            return ['success' => false, 'error' => 'Décaissement WacePay non configuré (clés WACEPAY_PAYIN_*).'];
        }

        $country  = strtoupper($data['country'] ?? 'CG');
        $currency = $this->currencyFor($country) ?? ($data['currency'] ?? 'XAF');
        $amount   = (int) $data['amount'];
        $msisdn   = $this->msisdn($data['phone'] ?? '', $country, (string) $this->c('payout.msisdn_format', 'international'));

        $service = $this->resolvePayoutSubscription($data['operator'] ?? 'MTN', $country, $currency);
        if (!$service) {
            return ['success' => false, 'error' => "Aucun service de décaissement WacePay actif pour " . ($data['operator'] ?? '?') . " ($country, $currency). Souscrivez le service dans le tableau de bord WacePay ou renseignez WACEPAY_PAYOUT_SUBSCRIPTIONS."];
        }

        if (isset($service['minAmount']) && $amount < (float) $service['minAmount']) {
            return ['success' => false, 'error' => "Montant inférieur au minimum WacePay ({$service['minAmount']} $currency)."];
        }
        if (!empty($service['maxAmount']) && $amount > (float) $service['maxAmount']) {
            return ['success' => false, 'error' => "Montant supérieur au maximum WacePay ({$service['maxAmount']} $currency)."];
        }

        $payload = [
            'amount'               => $amount,
            'recipientMsisdn'      => $msisdn,
            'recipientName'        => trim((string) ($data['recipient_name'] ?? '')) ?: 'Beneficiaire TopTopGo',
            'payoutSubscriptionId' => (string) $service['subscriptionId'],
        ];

        $res = $this->payin('POST', 'payout/customer/execute', $payload);

        if (!$res['ok']) {
            return [
                'success'      => false,
                'error'        => $res['error'] ?? 'Décaissement WacePay refusé',
                'data'         => $res['json'],
                'request_sent' => array_merge($payload, ['recipientMsisdn' => '***']),
            ];
        }

        $d   = (array) (data_get($res['json'], 'data.transaction') ?? data_get($res['json'], 'data') ?? []);
        $id  = $d['id'] ?? $d['_id'] ?? $d['transactionId'] ?? null;
        $ref = $d['transCode'] ?? $d['reference'] ?? $d['externalReference'] ?? $id;

        Log::info('WacePay payout initié', ['internal' => $data['reference'] ?? null, 'id' => $id, 'transCode' => $ref, 'amount' => $amount, 'currency' => $currency]);

        return [
            'success'        => true,
            // On garde de quoi relire la transaction : l'id (détail) sinon le transCode.
            'reference'      => (string) ($id ?? $ref ?? ''),
            'trans_code'     => $ref,
            'transaction_id' => $id,
            'status'         => $this->mapPayoutStatus((string) ($d['status'] ?? 'PENDING'))['status'],
            'raw_status'     => strtoupper((string) ($d['status'] ?? 'PENDING')),
            'message'        => $res['json']['message'] ?? null,
            'data'           => $d,
        ];
    }

    /** PENDING|PROCESSING → pending ; SUCCESS/COMPLETED → success ; FAILED/REJECTED/CANCELLED → failed */
    public function mapPayoutStatus(string $raw): array
    {
        $r = strtoupper(trim($raw));
        return [
            'raw_status' => $r,
            'status'     => match (true) {
                in_array($r, ['SUCCESS', 'SUCCESSFUL', 'COMPLETED', 'PAID'], true)                   => 'success',
                in_array($r, ['FAILED', 'REJECTED', 'ERROR'], true) || str_starts_with($r, 'CANCEL') => 'failed',
                default                                                                              => 'pending',
            },
        ];
    }

    /** Statut d'un décaissement : GET payout/customer/transactions/{id}, sinon recherche dans la liste par transCode. */
    public function getPayoutStatus(string $reference, ?string $msisdn = null): array
    {
        $res = $this->payin('GET', 'payout/customer/transactions/' . urlencode($reference));
        $tx  = $res['ok'] ? (array) (data_get($res['json'], 'data.transaction') ?? data_get($res['json'], 'data') ?? []) : [];

        if (empty($tx['status'])) {
            $list = $this->payin('GET', 'payout/customer/transactions', [], array_filter([
                'recipientMsisdn' => $msisdn, 'page' => 1, 'limit' => 100,
            ]));
            foreach ($list['ok'] ? $this->listFrom($list['json']) : [] as $row) {
                if (in_array($reference, array_map('strval', array_filter([
                    $row['id'] ?? null, $row['_id'] ?? null, $row['transCode'] ?? null, $row['externalReference'] ?? null,
                ])), true)) {
                    $tx = $row;
                    break;
                }
            }
        }

        if (empty($tx['status'])) {
            return ['success' => false, 'error' => $res['error'] ?? 'Transaction de décaissement introuvable'];
        }

        return ['success' => true, 'data' => $tx] + $this->mapPayoutStatus((string) $tx['status']);
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
