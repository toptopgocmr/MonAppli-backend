<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Notch Pay (notchpay.co) – passerelle Mobile Money / cartes – ToptopGo
 *
 * Doc : https://developer.notchpay.co
 *
 * Flux "Direct API" (paiement sans redirection, push USSD sur le téléphone) :
 *   1. POST /payments              → initialise la transaction (montant, client, reference)
 *   2. POST /payments/{reference}  → déclenche la charge sur le canal choisi
 *                                     (ex: cg.mtn, cg.airtel, cm.mtn, cm.orange)
 *   3. GET  /payments/{reference}  → statut (pending|processing|complete|failed|canceled|expired)
 *   4. Webhook POST /api/webhooks/notchpay, signé HMAC-SHA256 (header x-notch-signature)
 *      avec la "Hash Key" du webhook (≠ clés API).
 *
 * Authentification : header `Authorization: <clé publique>` (pk_test_… en sandbox).
 * La clé privée + header `X-Grant` ne servent qu'aux opérations sensibles (transferts).
 * Même URL de base pour sandbox et production — c'est la clé qui détermine l'environnement.
 */
class NotchPayService implements PaymentProviderInterface
{
    protected string $baseUrl;
    protected ?string $publicKey;
    protected ?string $privateKey;
    protected ?string $webhookHash;

    public function __construct()
    {
        $this->baseUrl     = rtrim((string) config('payments.notchpay.base_url', 'https://api.notchpay.co'), '/');
        $this->publicKey   = config('payments.notchpay.public_key') ?: null;
        $this->privateKey  = config('payments.notchpay.private_key') ?: null;
        $this->webhookHash = config('payments.notchpay.webhook_hash') ?: null;
    }

    protected function client(bool $withGrant = false)
    {
        $headers = [
            'Authorization' => (string) $this->publicKey,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];

        if ($withGrant && $this->privateKey) {
            $headers['X-Grant'] = $this->privateKey;
        }

        return Http::withHeaders($headers)->timeout((int) config('payments.notchpay.timeout', 30));
    }

    protected function errorMessage($response, string $fallback): string
    {
        $json = $response->json();

        if (is_array($json)) {
            // Erreurs de validation : { errors: { field: ["msg"] } }
            if (!empty($json['errors']) && is_array($json['errors'])) {
                $first = collect($json['errors'])->flatten()->first();
                if ($first) {
                    return (string) $first;
                }
            }
            if (!empty($json['message'])) {
                return (string) $json['message'];
            }
        }

        return $fallback;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Couverture
    // ─────────────────────────────────────────────────────────────────────

    public function supportedCollectCountries(): array
    {
        return array_map('strtoupper', (array) config('payments.notchpay.collect_countries', []));
    }

    public function supportsCollectFor(?string $countryCode): bool
    {
        return $this->isAvailable()
            && $countryCode
            && in_array(strtoupper($countryCode), $this->supportedCollectCountries(), true);
    }

    /**
     * Canal Notch Pay au format {pays}.{opérateur} (ex: cg.mtn, cm.orange).
     * Les surcharges éventuelles se règlent dans config('payments.notchpay.channel_overrides').
     */
    public function resolveChannel(string $operator, string $country): string
    {
        $country = strtolower($country);
        $op = strtoupper($operator);

        $provider = match (true) {
            str_contains($op, 'MTN')        => 'mtn',
            str_contains($op, 'ORANGE')     => 'orange',
            str_contains($op, 'AIRTELTIGO') => 'airteltigo',
            str_contains($op, 'AIRTEL')     => 'airtel',
            str_contains($op, 'MOOV')       => 'moov',
            str_contains($op, 'WAVE')       => 'wave',
            str_contains($op, 'MPESA')      => 'mpesa',
            str_contains($op, 'EXPRESS') || $op === 'EU' => 'eu',
            default                         => strtolower(preg_replace('/[^A-Z]/', '', $op)) ?: 'mtn',
        };

        $channel = $country . '.' . $provider;
        $overrides = (array) config('payments.notchpay.channel_overrides', []);

        return $overrides[$channel] ?? $channel;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Collecte
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Initialise ET déclenche un paiement Mobile Money.
     *
     * $data :
     *   reference (obligatoire) – notre transaction_ref (TXN-…)
     *   amount, currency (XAF), phone, country (CG|CM…), operator (MTN|AIRTEL|ORANGE…)
     *   customer_name, email, description
     */
    public function collect(array $data): array
    {
        $reference = $data['reference'] ?? null;
        $country   = strtoupper($data['country'] ?? 'CG');
        $phone     = $this->normalizePhone($data['phone'] ?? '', $country);
        $channel   = $data['channel'] ?? $this->resolveChannel($data['operator'] ?? 'MTN', $country);

        if (!$this->isAvailable()) {
            return ['success' => false, 'error' => 'Notch Pay non configuré (NOTCHPAY_PUBLIC_KEY manquante).'];
        }

        try {
            // 1. Initialisation
            $initPayload = array_filter([
                'amount'         => (int) $data['amount'],
                'currency'       => $data['currency'] ?? 'XAF',
                'reference'      => $reference,
                'description'    => $data['description'] ?? 'TopTopGo - Paiement',
                'callback'       => config('payments.notchpay.callback_url') ?: null,
                'locked_country' => $country,
                'locked_channel' => $channel,
                'customer'       => array_filter([
                    'name'  => $data['customer_name'] ?? 'Client TopTopGo',
                    'email' => $data['email'] ?? null,
                    'phone' => $phone,
                ]),
            ], fn ($v) => $v !== null && $v !== '');

            $init = $this->client()->post($this->baseUrl . '/payments', $initPayload);

            if (!$init->successful()) {
                return [
                    'success'      => false,
                    'error'        => $this->errorMessage($init, 'Initialisation Notch Pay échouée'),
                    'data'         => $init->json(),
                    'http_status'  => $init->status(),
                    'raw_body'     => $init->body(),
                    'endpoint'     => $this->baseUrl . '/payments',
                ];
            }

            $initBody   = $init->json() ?? [];
            $notchRef   = $initBody['transaction']['reference'] ?? $reference;

            // 2. Déclenchement de la charge (push USSD sur le téléphone du client)
            $processPayload = [
                'channel' => $channel,
                'data'    => [
                    'phone'   => $phone,
                    'country' => $country,
                ],
            ];
            if (!empty($data['client_ip'])) {
                $processPayload['client_ip'] = $data['client_ip'];
            }

            $process = $this->client()->post($this->baseUrl . '/payments/' . urlencode($notchRef), $processPayload);

            if (!$process->successful()) {
                return [
                    'success'      => false,
                    'error'        => $this->errorMessage($process, 'Déclenchement du paiement Notch Pay échoué'),
                    'data'         => $process->json(),
                    'http_status'  => $process->status(),
                    'raw_body'     => $process->body(),
                    'endpoint'     => $this->baseUrl . '/payments/' . $notchRef,
                    'request_sent' => ['channel' => $channel, 'phone' => $phone],
                ];
            }

            $processBody = $process->json() ?? [];

            return [
                'success'          => true,
                'transaction_id'   => $notchRef,
                'reference'        => $reference,
                'channel'          => $channel,
                'status'           => $this->mapStatus($processBody['transaction']['status'] ?? 'processing'),
                'authorization_url'=> $initBody['authorization_url'] ?? null,
                'instruction'      => $processBody['message'] ?? 'Autorisez le paiement sur votre téléphone Mobile Money.',
                'data'             => ['init' => $initBody, 'process' => $processBody],
            ];
        } catch (Exception $e) {
            Log::error('NotchPay collect error: ' . $e->getMessage(), ['reference' => $reference]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * GET /payments/{reference} — accepte la référence Notch Pay (trx.…)
     * ou notre propre référence marchande.
     */
    public function getTransactionStatus(string $transactionId): array
    {
        try {
            $response = $this->client()->get($this->baseUrl . '/payments/' . urlencode($transactionId));

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error'   => $this->errorMessage($response, 'Transaction introuvable'),
                    'http_status' => $response->status(),
                ];
            }

            $trx = $response->json('transaction') ?? [];
            $raw = (string) ($trx['status'] ?? 'pending');

            return [
                'success'    => true,
                'status'     => $this->mapStatus($raw),
                'raw_status' => $raw,
                'reason'     => $this->failureReason($trx),
                'data'       => $trx,
            ];
        } catch (Exception $e) {
            Log::error('NotchPay status error: ' . $e->getMessage(), ['reference' => $transactionId]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function payout(array $data): array
    {
        // Les retraits chauffeurs/sociétés restent sur le circuit actuel
        // (Peex / traitement manuel). L'API Transferts Notch Pay (X-Grant)
        // pourra être branchée ici plus tard.
        return ['success' => false, 'error' => 'Payout Notch Pay non activé.'];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Webhook
    // ─────────────────────────────────────────────────────────────────────

    public function verifyWebhookSignature(string $rawBody, ?string $signature): bool
    {
        if (!$this->webhookHash) {
            Log::error('NotchPay webhook: NOTCHPAY_WEBHOOK_HASH non configuré');
            return false; // fail closed
        }
        if (!$signature) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $this->webhookHash), trim($signature));
    }

    /**
     * Normalise un évènement { type: "payment.complete", data: {...} }.
     */
    public function handleWebhook(array $payload): array
    {
        $type = (string) ($payload['type'] ?? $payload['event'] ?? '');
        $data = (array) ($payload['data'] ?? []);

        if (!str_starts_with($type, 'payment.')) {
            return ['success' => false, 'ignored' => true, 'error' => 'Évènement non géré: ' . $type];
        }

        $status = match ($type) {
            'payment.complete' => 'success',
            'payment.failed'   => 'failed',
            'payment.canceled', 'payment.cancelled', 'payment.expired' => 'cancelled',
            default            => $this->mapStatus((string) ($data['status'] ?? 'pending')),
        };

        // Toutes les clés pouvant porter la référence — on matche sur l'une d'elles.
        $references = array_values(array_unique(array_filter([
            $data['merchant_reference'] ?? null,
            $data['reference'] ?? null,
            $data['trxref'] ?? null,
            $data['id'] ?? null,
        ])));

        if (empty($references)) {
            return ['success' => false, 'error' => 'Référence absente du webhook'];
        }

        return [
            'success'    => true,
            'type'       => $type,
            'status'     => $status,
            'references' => $references,
            'reason'     => $this->failureReason($data),
            'data'       => $data,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────

    /** pending|processing → pending ; complete → success ; failed → failed ; canceled|expired → cancelled */
    public function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'complete', 'completed', 'success', 'successful' => 'success',
            'failed', 'rejected', 'error'                    => 'failed',
            'canceled', 'cancelled', 'expired', 'abandoned'  => 'cancelled',
            default                                          => 'pending',
        };
    }

    protected function failureReason(array $trx): ?string
    {
        $reason = $trx['failure_reason'] ?? $trx['reason'] ?? $trx['status_reason'] ?? $trx['message'] ?? null;
        return $reason ? $this->humanizeFailureReason((string) $reason) : null;
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
            str_contains($r, 'timeout') || str_contains($r, 'expired')          => 'Délai de confirmation dépassé. Veuillez réessayer.',
            str_contains($r, 'phone') || str_contains($r, 'numéro')             => 'Numéro de téléphone invalide pour ce moyen de paiement.',
            str_contains($r, 'channel')                                         => 'Ce moyen de paiement n\'est pas disponible pour ce pays.',
            default                                                             => $raw,
        };
    }

    public function getProviderName(): string
    {
        return 'notchpay';
    }

    public function isAvailable(): bool
    {
        return !empty($this->publicKey);
    }

    protected function callingCode(string $country): string
    {
        return match (strtoupper($country)) {
            'CM' => '237', 'CG' => '242', 'GA' => '241', 'TD' => '235',
            'CF' => '236', 'GQ' => '240', 'CI' => '225', 'SN' => '221',
            'NG' => '234', 'CD' => '243',
            default => '242',
        };
    }

    /** E.164 (+242061234567). Le 0 initial des numéros congolais (9 chiffres) est conservé. */
    public function normalizePhone(string $phone, ?string $country = null): string
    {
        $digits = preg_replace('/[^0-9+]/', '', trim($phone));

        if (str_starts_with($digits, '+')) {
            return $digits;
        }
        if (str_starts_with($digits, '00')) {
            return '+' . substr($digits, 2);
        }

        $cc = $this->callingCode($country ?: 'CG');
        if (str_starts_with($digits, $cc) && strlen($digits) > 9) {
            return '+' . $digits;
        }

        return '+' . $cc . $digits;
    }
}
