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
 * Flux collect (Mobile Money, Congo CG) :
 *   1. Create / retrieve Flutterwave customer  →  cus_xxx
 *   2. Create payment method (mobile_money)    →  pmd_xxx
 *   3. Create charge                           →  chg_xxx  (status: pending)
 *   4. Customer autorise sur son téléphone
 *   5. Webhook « charge.completed »            →  handleWebhook()
 *
 * Flux payout (retrait chauffeur) :
 *   POST /transfers → mobile money transfer
 */
class FlutterwaveService implements PaymentProviderInterface
{
    protected string $baseUrl;
    protected string $secretKey;
    protected string $secretHash;   // pour vérifier la signature webhook
    protected string $currency;
    protected string $countryCode;  // ex: "242"

    public function __construct()
    {
        $env           = config('flutterwave.env', 'sandbox');
        $this->baseUrl = rtrim(config("flutterwave.base_url.{$env}"), '/');
        $this->secretKey   = config('flutterwave.secret_key');
        $this->secretHash  = config('flutterwave.secret_hash');
        $this->currency    = config('flutterwave.currency', 'XAF');
        $this->countryCode = config('flutterwave.country_code', '242');
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
     * Collecte un paiement Mobile Money depuis le client.
     *
     * Paramètres attendus dans $data :
     *   - phone       : numéro local (ex: 06XXXXXXXX) ou international (242…)
     *   - amount      : montant en XAF
     *   - operator    : 'MTN' ou 'AIRTEL'
     *   - reference   : référence interne unique (ex: RIDE-42-XXXXX)
     *   - description : libellé
     *   - user_id     : ID utilisateur (pour mise en cache du customer FLW)
     *   - email       : email du client (requis pour créer le customer FLW)
     *
     * @return array{success: bool, transaction_id?: string, status?: string, data?: array, error?: string}
     */
    public function collect(array $data): array
    {
        try {
            // --- 1. Customer FLW (mise en cache par user_id) -----------------
            $customerId = $this->getOrCreateCustomer(
                userId:    $data['user_id'],
                email:     $data['email']     ?? "user_{$data['user_id']}@toptopgo.cg",
                phone:     $data['phone'],
                firstName: $data['first_name'] ?? null,
                lastName:  $data['last_name']  ?? null,
            );

            if (!$customerId) {
                return ['success' => false, 'error' => 'Impossible de créer le profil client Flutterwave'];
            }

            // --- 2. Payment method -------------------------------------------
            $phoneNormalized = $this->normalizePhone($data['phone']);
            $network         = strtoupper($data['operator'] ?? 'MTN');

            $pmResponse = $this->client()->post("{$this->baseUrl}/payment-methods", [
                'type'         => 'mobile_money',
                'mobile_money' => [
                    'country_code' => $this->countryCode,
                    'network'      => $network,
                    'phone_number' => $phoneNormalized,
                ],
            ]);

            if (!$pmResponse->successful()) {
                Log::error('FLW payment-method error', ['body' => $pmResponse->body()]);
                return ['success' => false, 'error' => 'Erreur création méthode de paiement: ' . $pmResponse->body()];
            }

            $paymentMethodId = $pmResponse->json('data.id');

            // --- 3. Charge ---------------------------------------------------
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

            $charge     = $chargeResponse->json('data');
            $chargeId   = $charge['id'];       // chg_xxx
            $chargeStatus = $charge['status']; // pending

            // L'instruction affichée au client (ex: "Autorisez le paiement sur votre téléphone")
            $instruction = $charge['next_action']['payment_instruction']['note']
                        ?? $charge['next_action']['redirect_url']['url']
                        ?? 'Veuillez autoriser le paiement sur votre téléphone Mobile Money.';

            return [
                'success'        => true,
                'transaction_id' => $chargeId,
                'status'         => $this->mapStatus($chargeStatus),
                'instruction'    => $instruction,
                'data'           => [
                    'charge_id'          => $chargeId,
                    'customer_id'        => $customerId,
                    'payment_method_id'  => $paymentMethodId,
                    'amount'             => $charge['amount'],
                    'currency'           => $charge['currency'],
                    'status'             => $chargeStatus,
                    'next_action'        => $charge['next_action'] ?? null,
                    'reference'          => $charge['reference'],
                ],
            ];

        } catch (Exception $e) {
            Log::error('FlutterwaveService::collect exception: ' . $e->getMessage(), [
                'data' => $data,
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // 2. PAYOUT – virer des fonds vers un chauffeur (Mobile Money transfer)
    // =========================================================================

    /**
     * Initie un virement Mobile Money vers le chauffeur.
     *
     * Paramètres attendus dans $data :
     *   - phone       : numéro local du chauffeur
     *   - amount      : montant en XAF
     *   - operator    : 'MTN' ou 'AIRTEL'
     *   - reference   : référence unique (ex: WD-5-XXXXX)
     *   - description : libellé
     *
     * @return array{success: bool, transaction_id?: string, status?: string, data?: array, error?: string}
     */
    public function payout(array $data): array
    {
        try {
            $phoneNormalized = $this->normalizePhone($data['phone']);
            $network         = strtoupper($data['operator'] ?? 'MTN');

            // Code banque mobile money Flutterwave par réseau
            // À vérifier dans votre dashboard FLW → Settings → Transfer Banks
            $bankCode = match ($network) {
                'MTN'    => 'FMM',    // Mobile Money MTN Congo
                'AIRTEL' => 'FAM',    // Airtel Money Congo
                default  => 'FMM',
            };

            $response = $this->client()
                ->withHeaders(['X-Idempotency-Key' => $data['reference']])
                ->post("{$this->baseUrl}/transfers", [
                    'account_bank'   => $bankCode,
                    'account_number' => $phoneNormalized,
                    'amount'         => (int) $data['amount'],
                    'narration'      => $data['description'] ?? 'ToptopGo – Paiement chauffeur',
                    'currency'       => $this->currency,
                    'reference'      => $data['reference'],
                    'debit_currency' => $this->currency,
                    'meta'           => [
                        ['sender_details' => 'ToptopGo Platform'],
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
    // 3. GET TRANSACTION STATUS – vérification manuelle d'une charge
    // =========================================================================

    /**
     * @param string $transactionId  ID FLW de la charge : chg_xxx
     */
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
    // 4. HANDLE WEBHOOK – traiter un événement envoyé par Flutterwave
    // =========================================================================

    /**
     * Traite le payload d'un webhook Flutterwave.
     * La vérification de signature DOIT être faite AVANT d'appeler cette méthode
     * (voir WebhookController::handleFlutterwave).
     *
     * @param array $payload  Corps JSON du webhook
     * @return array{success: bool, status?: string, transaction?: Transaction, error?: string}
     */
    public function handleWebhook(array $payload): array
    {
        $eventType = $payload['type'] ?? '';

        Log::info("FLW webhook reçu : {$eventType}", [
            'webhook_id' => $payload['id'] ?? null,
            'charge_id'  => $payload['data']['id'] ?? null,
            'reference'  => $payload['data']['reference'] ?? null,
        ]);

        // On ne traite que les paiements complétés
        if ($eventType !== 'charge.completed') {
            return ['success' => true, 'status' => 'ignored', 'message' => "Événement {$eventType} ignoré"];
        }

        $chargeData = $payload['data'] ?? [];
        $reference  = $chargeData['reference']  ?? null;
        $chargeId   = $chargeData['id']         ?? null;
        $chargeStatus = $chargeData['status']   ?? '';
        $webhookId  = $payload['id']            ?? null;

        if (!$reference) {
            Log::warning('FLW webhook : référence manquante', $payload);
            return ['success' => false, 'error' => 'Référence manquante'];
        }

        // Trouver la transaction par référence
        $transaction = Transaction::where('reference', $reference)->first();

        if (!$transaction) {
            Log::warning("FLW webhook : transaction introuvable pour référence {$reference}");
            return ['success' => false, 'error' => 'Transaction introuvable'];
        }

        // --- Idempotence : si déjà traitée, on répond 200 sans rien faire ----
        if ($transaction->provider_response && isset($transaction->provider_response['webhook_id'])
            && $transaction->provider_response['webhook_id'] === $webhookId) {
            Log::info("FLW webhook : événement {$webhookId} déjà traité (idempotence)");
            return ['success' => true, 'status' => $transaction->status, 'transaction' => $transaction];
        }

        // --- Vérification complémentaire via API FLW (best practice) ---------
        if ($chargeId) {
            $verified = $this->getTransactionStatus($chargeId);
            if ($verified['success']) {
                $chargeStatus = $verified['data']['status'] ?? $chargeStatus;
            }
        }

        $newStatus = $this->mapStatus($chargeStatus);

        // Mettre à jour la transaction
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
    // Interface – getProviderName / isAvailable
    // =========================================================================

    public function getProviderName(): string
    {
        return 'Flutterwave';
    }

    public function isAvailable(): bool
    {
        return !empty($this->secretKey) && !empty($this->secretHash);
    }

    // =========================================================================
    // Vérification de signature webhook (appelée par le controller)
    // =========================================================================

    /**
     * Vérifie que le webhook provient bien de Flutterwave.
     * Flutterwave envoie le corps hashé en HMAC-SHA256 dans le header
     * « flutterwave-signature ».
     */
    public function verifyWebhookSignature(string $rawBody, string $signature): bool
    {
        $expected = hash_hmac('sha256', $rawBody, $this->secretHash);
        return hash_equals($expected, $signature);
    }

    // =========================================================================
    // Helpers privés
    // =========================================================================

    /**
     * Récupère ou crée un customer FLW pour un utilisateur ToptopGo.
     * L'ID est mis en cache pour éviter de recréer à chaque paiement.
     */
    protected function getOrCreateCustomer(
        int $userId,
        string $email,
        string $phone,
        ?string $firstName = null,
        ?string $lastName  = null,
    ): ?string {
        $cacheKey = "flw_customer_{$userId}";

        return Cache::remember($cacheKey, now()->addDays(30), function () use (
            $email, $phone, $firstName, $lastName
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
                        'country_code' => $this->countryCode,
                        'number'       => $this->normalizePhone($phone),
                    ];
                }

                $response = $this->client()->post("{$this->baseUrl}/customers", $body);

                if ($response->successful()) {
                    return $response->json('data.id');
                }

                // Si le customer existe déjà (409), tenter de le récupérer par email
                if ($response->status() === 409) {
                    $search = $this->client()->get("{$this->baseUrl}/customers", ['email' => $email]);
                    if ($search->successful()) {
                        $customers = $search->json('data');
                        return $customers[0]['id'] ?? null;
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
     * Normalise un numéro de téléphone.
     * Entrée : "0612345678" ou "+242 06 123 45 678" → "0612345678" (local)
     * Flutterwave attend le numéro LOCAL (sans indicatif) dans mobile_money.
     */
    protected function normalizePhone(string $phone): string
    {
        // Ne garder que les chiffres
        $digits = preg_replace('/\D/', '', $phone);

        // Retirer l'indicatif pays 242 si présent
        if (str_starts_with($digits, '242') && strlen($digits) > 9) {
            $digits = substr($digits, 3);
        }

        return $digits;
    }

    /**
     * Mappe les statuts Flutterwave v4 vers les statuts internes ToptopGo.
     *
     * FLW statuts charge : pending | succeeded | failed | cancelled
     */
    protected function mapStatus(string $flwStatus): string
    {
        return match (strtolower($flwStatus)) {
            'succeeded', 'successful', 'approved' => 'completed',
            'pending', 'processing'               => 'processing',
            'failed', 'cancelled', 'rejected'     => 'failed',
            default                               => 'processing',
        };
    }

    /**
     * Mappe les statuts de transfer Flutterwave (payout).
     * FLW statuts transfer : NEW | PENDING | FAILED | SUCCESSFUL
     */
    protected function mapTransferStatus(string $flwStatus): string
    {
        return match (strtoupper($flwStatus)) {
            'SUCCESSFUL' => 'completed',
            'FAILED'     => 'failed',
            default      => 'processing',
        };
    }
}
