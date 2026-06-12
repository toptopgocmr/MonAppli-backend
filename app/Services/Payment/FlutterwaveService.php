<?php

namespace App\Services\Payment;

use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Flutterwave v4 – ToptopGo
 *
 * Flux collect (Mobile Money) :
 *   1. Create / retrieve Flutterwave customer  →  cus_xxx
 *   2. Create payment method (mobile_money)    →  pmd_xxx
 *   3. Create charge                           →  chg_xxx  (status: pending)
 *   4. Customer autorise sur son téléphone
 *   5. Webhook « charge.completed »            →  handleWebhook()
 *
 * Flux payout (retrait chauffeur) :
 *   POST /direct-transfers → mobile money transfer
 */
class FlutterwaveService implements PaymentProviderInterface
{
    protected string $baseUrl;
    protected ?string $secretKey = null;
    protected ?string $secretHash = null;
    protected string $currency;

    public function __construct()
    {
        $env           = config('flutterwave.env', 'sandbox');
        $this->baseUrl = rtrim(config("flutterwave.base_url.{$env}"), '/');
        $this->secretKey  = config('flutterwave.secret_key') ?: null;
        $this->secretHash = config('flutterwave.secret_hash');
        $this->currency   = config('flutterwave.currency', 'XAF');
    }

    // =========================================================================
    // HTTP client
    // =========================================================================

    protected function client()
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->timeout(30);
    }

    // =========================================================================
    // 1. COLLECT – encaisser un paiement Mobile Money
    // =========================================================================

    /**
     * Paramètres attendus dans $data :
     *   - phone       : numéro du client (avec ou sans indicatif)
     *   - country_code: indicatif pays (ex: "237" Cameroun, "233" Ghana…)
     *   - amount      : montant
     *   - operator    : 'MTN' ou 'AIRTEL'
     *   - reference   : référence interne unique
     *   - description : libellé
     *   - user_id     : ID utilisateur
     *   - email       : email du client
     *
     * @return array{success: bool, transaction_id?: string, status?: string, data?: array, error?: string}
     */
    public function collect(array $data): array
    {
        try {
            $countryCode = $data['country_code'] ?? config('flutterwave.country_code');

            // --- 1. Customer FLW (mis en cache par user_id) ------------------
            $customerId = $this->getOrCreateCustomer(
                userId:      $data['user_id'],
                email:       $data['email'] ?? "user_{$data['user_id']}@toptopgo.app",
                phone:       $data['phone'],
                countryCode: $countryCode,
                firstName:   $data['first_name'] ?? null,
                lastName:    $data['last_name']  ?? null,
            );

            if (!$customerId) {
                return ['success' => false, 'error' => 'Impossible de créer le profil client Flutterwave'];
            }

            // --- 2. Payment method ------------------------------------------
            $network = strtoupper($data['operator'] ?? 'MTN');

            $pmResponse = $this->client()->post("{$this->baseUrl}/payment-methods", [
                'type'         => 'mobile_money',
                'mobile_money' => [
                    'country_code' => $countryCode,
                    'network'      => $network,
                    'phone_number' => $this->normalizePhone($data['phone'], $countryCode),
                ],
            ]);

            if (!$pmResponse->successful()) {
                Log::error('FLW payment-method error', ['body' => $pmResponse->body()]);
                return ['success' => false, 'error' => 'Erreur création méthode de paiement: ' . $pmResponse->body()];
            }

            $paymentMethodId = $pmResponse->json('data.id');

            // --- 3. Charge --------------------------------------------------
            $chargeResponse = $this->client()
                ->withHeaders(['X-Idempotency-Key' => $data['reference']])
                ->post("{$this->baseUrl}/charges", [
                    'currency'          => $this->currency,
                    'customer_id'       => $customerId,
                    'payment_method_id' => $paymentMethodId,
                    'amount'            => (int) $data['amount'],
                    'reference'         => $data['reference'],
                    'description'       => $data['description'] ?? 'ToptopGo – Paiement course',
                ]);

            if (!$chargeResponse->successful()) {
                Log::error('FLW charge error', ['body' => $chargeResponse->body()]);
                return ['success' => false, 'error' => 'Erreur initiation charge: ' . $chargeResponse->body()];
            }

            $charge       = $chargeResponse->json('data');
            $chargeId     = $charge['id'];
            $chargeStatus = $charge['status'];

            $instruction = $charge['next_action']['payment_instruction']['note']
                        ?? $charge['next_action']['redirect_url']['url']
                        ?? 'Veuillez autoriser le paiement sur votre téléphone Mobile Money.';

            return [
                'success'        => true,
                'transaction_id' => $chargeId,
                'status'         => $this->mapStatus($chargeStatus),
                'instruction'    => $instruction,
                'data'           => [
                    'charge_id'         => $chargeId,
                    'customer_id'       => $customerId,
                    'payment_method_id' => $paymentMethodId,
                    'amount'            => $charge['amount'],
                    'currency'          => $charge['currency'],
                    'status'            => $chargeStatus,
                    'next_action'       => $charge['next_action'] ?? null,
                    'reference'         => $charge['reference'],
                ],
            ];

        } catch (Exception $e) {
            Log::error('FlutterwaveService::collect exception: ' . $e->getMessage(), ['data' => $data]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // 2. PAYOUT – virer des fonds vers un chauffeur (v4 direct-transfers)
    // =========================================================================

    /**
     * Paramètres attendus dans $data :
     *   - phone       : numéro du chauffeur (msisdn complet avec indicatif)
     *   - country_code: indicatif pays
     *   - amount      : montant
     *   - operator    : 'MTN' ou 'AIRTEL'
     *   - reference   : référence unique
     *   - description : libellé
     *   - first_name  : prénom du chauffeur
     *   - last_name   : nom du chauffeur
     *
     * @return array{success: bool, transaction_id?: string, status?: string, data?: array, error?: string}
     */
    public function payout(array $data): array
    {
        try {
            $network  = strtolower($data['operator'] ?? 'mtn');
            $msisdn   = $this->toMsisdn($data['phone'], $data['country_code'] ?? '');

            $response = $this->client()
                ->withHeaders(['X-Idempotency-Key' => $data['reference']])
                ->post("{$this->baseUrl}/direct-transfers", [
                    'action'   => 'instant',
                    'type'     => 'mobile_money',
                    'narration' => $data['description'] ?? 'ToptopGo – Paiement chauffeur',
                    'reference' => $data['reference'],
                    'payment_instruction' => [
                        'source_currency'      => $this->currency,
                        'destination_currency' => $this->currency,
                        'amount' => [
                            'applies_to' => 'destination_currency',
                            'value'      => (int) $data['amount'],
                        ],
                        'recipient' => [
                            'name' => [
                                'first' => $data['first_name'] ?? 'Driver',
                                'last'  => $data['last_name']  ?? '',
                            ],
                            'mobile_money' => [
                                'network' => $network,
                                'msisdn'  => $msisdn,
                            ],
                        ],
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('FLW payout error', ['body' => $response->body()]);
                return ['success' => false, 'error' => 'Erreur virement: ' . $response->body()];
            }

            $transfer = $response->json('data');

            return [
                'success'        => true,
                'transaction_id' => (string) ($transfer['id'] ?? ''),
                'status'         => $this->mapTransferStatus($transfer['status'] ?? ''),
                'data'           => $transfer,
            ];

        } catch (Exception $e) {
            Log::error('FlutterwaveService::payout exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // 3. GET TRANSACTION STATUS
    // =========================================================================

    public function getTransactionStatus(string $transactionId): array
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/charges/{$transactionId}");

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Charge introuvable'];
            }

            $charge = $response->json('data');

            return [
                'success' => true,
                'status'  => $this->mapStatus($charge['status']),
                'data'    => $charge,
            ];

        } catch (Exception $e) {
            Log::error('FlutterwaveService::getTransactionStatus exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // 4. HANDLE WEBHOOK
    // =========================================================================

    public function handleWebhook(array $payload): array
    {
        $eventType = $payload['type'] ?? '';

        Log::info("FLW webhook reçu : {$eventType}", [
            'webhook_id' => $payload['id'] ?? null,
            'charge_id'  => $payload['data']['id'] ?? null,
            'reference'  => $payload['data']['reference'] ?? null,
        ]);

        if ($eventType !== 'charge.completed') {
            return ['success' => true, 'status' => 'ignored', 'message' => "Événement {$eventType} ignoré"];
        }

        $chargeData   = $payload['data'] ?? [];
        $reference    = $chargeData['reference'] ?? null;
        $chargeId     = $chargeData['id']        ?? null;
        $chargeStatus = $chargeData['status']    ?? '';
        $webhookId    = $payload['id']           ?? null;

        if (!$reference) {
            Log::warning('FLW webhook : référence manquante', $payload);
            return ['success' => false, 'error' => 'Référence manquante'];
        }

        $transaction = Transaction::where('reference', $reference)->first();

        if (!$transaction) {
            Log::warning("FLW webhook : transaction introuvable pour référence {$reference}");
            return ['success' => false, 'error' => 'Transaction introuvable'];
        }

        // Idempotence
        if ($transaction->provider_response && isset($transaction->provider_response['webhook_id'])
            && $transaction->provider_response['webhook_id'] === $webhookId) {
            return ['success' => true, 'status' => $transaction->status, 'transaction' => $transaction];
        }

        // Vérification complémentaire
        if ($chargeId) {
            $verified = $this->getTransactionStatus($chargeId);
            if ($verified['success']) {
                $chargeStatus = $verified['data']['status'] ?? $chargeStatus;
            }
        }

        $newStatus  = $this->mapStatus($chargeStatus);
        $updateData = [
            'provider_transaction_id' => $chargeId,
            'provider_response'       => array_merge(
                $transaction->provider_response ?? [],
                ['charge' => $chargeData, 'webhook_id' => $webhookId]
            ),
        ];

        if ($newStatus === 'completed') {
            $updateData['status']       = 'completed';
            $updateData['completed_at'] = now();
        } elseif ($newStatus === 'failed') {
            $updateData['status']    = 'failed';
            $updateData['failed_at'] = now();
        }

        $transaction->update($updateData);

        return [
            'success'     => true,
            'status'      => $newStatus,
            'transaction' => $transaction->fresh(),
        ];
    }

    // =========================================================================
    // Interface
    // =========================================================================

    public function getProviderName(): string { return 'Flutterwave'; }

    public function isAvailable(): bool
    {
        return !empty($this->secretKey) && !empty($this->secretHash);
    }

    public function verifyWebhookSignature(string $rawBody, string $signature): bool
    {
        $expected = hash_hmac('sha256', $rawBody, $this->secretHash);
        return hash_equals($expected, $signature);
    }

    // =========================================================================
    // Helpers privés
    // =========================================================================

    protected function getOrCreateCustomer(
        int $userId,
        string $email,
        string $phone,
        string $countryCode,
        ?string $firstName = null,
        ?string $lastName  = null,
    ): ?string {
        $cacheKey = "flw_customer_{$userId}";

        return Cache::remember($cacheKey, now()->addDays(30), function () use (
            $email, $phone, $countryCode, $firstName, $lastName
        ) {
            try {
                $body = ['email' => $email];

                if ($firstName || $lastName) {
                    $body['name'] = [
                        'first' => $firstName ?? '',
                        'last'  => $lastName  ?? '',
                    ];
                }

                if ($phone) {
                    $body['phone'] = [
                        'country_code' => $countryCode,
                        'number'       => $this->normalizePhone($phone, $countryCode),
                    ];
                }

                $response = $this->client()->post("{$this->baseUrl}/customers", $body);

                if ($response->successful()) {
                    return $response->json('data.id');
                }

                if ($response->status() === 409) {
                    $search = $this->client()->get("{$this->baseUrl}/customers", ['email' => $email]);
                    if ($search->successful()) {
                        return $search->json('data.0.id');
                    }
                }

                Log::error('FLW create customer error', ['body' => $response->body()]);
                return null;

            } catch (Exception $e) {
                Log::error('FLW getOrCreateCustomer exception: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Retourne le numéro LOCAL (sans indicatif) pour le payment_method.
     */
    protected function normalizePhone(string $phone, string $countryCode): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if ($countryCode && str_starts_with($digits, $countryCode) && strlen($digits) > strlen($countryCode) + 5) {
            $digits = substr($digits, strlen($countryCode));
        }

        return $digits;
    }

    /**
     * Retourne le numéro INTERNATIONAL (msisdn) pour les transfers.
     */
    protected function toMsisdn(string $phone, string $countryCode): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if ($countryCode && !str_starts_with($digits, $countryCode)) {
            $digits = $countryCode . ltrim($digits, '0');
        }

        return $digits;
    }

    protected function mapStatus(string $flwStatus): string
    {
        return match (strtolower($flwStatus)) {
            'succeeded', 'successful', 'approved' => 'completed',
            'pending', 'processing'               => 'processing',
            'failed', 'cancelled', 'rejected'     => 'failed',
            default                               => 'processing',
        };
    }

    protected function mapTransferStatus(string $flwStatus): string
    {
        return match (strtoupper($flwStatus)) {
            'SUCCESSFUL' => 'completed',
            'FAILED'     => 'failed',
            default      => 'processing',
        };
    }
}
