<?php

namespace App\Services\Payment;

use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Exception;

class MtnMomoService implements PaymentProviderInterface
{
    protected string $baseUrl;
    protected string $subscriptionKey;
    protected string $apiUser;
    protected string $apiKey;
    protected string $environment;
    protected string $currency;
    protected ?string $callbackUrl;

    public function __construct()
    {
        $this->baseUrl = config('payments.mtn_momo.base_url');
        $this->subscriptionKey = config('payments.mtn_momo.subscription_key');
        $this->apiUser = config('payments.mtn_momo.api_user');
        $this->apiKey = config('payments.mtn_momo.api_key');
        $this->environment = config('payments.mtn_momo.environment', 'sandbox');
        $this->currency = config('payments.mtn_momo.currency', 'XAF');
        $this->callbackUrl = config('payments.mtn_momo.callback_url');
    }

    /**
     * Get access token from MTN MoMo API
     */
    protected function getAccessToken(): ?string
    {
        $cacheKey = 'mtn_momo_access_token';

        return Cache::remember($cacheKey, 3500, function () {
            try {
                $response = Http::withHeaders([
                    'Ocp-Apim-Subscription-Key' => $this->subscriptionKey,
                    'Authorization' => 'Basic ' . base64_encode($this->apiUser . ':' . $this->apiKey),
                ])->post($this->baseUrl . '/collection/token/');

                if ($response->successful()) {
                    return $response->json()['access_token'] ?? null;
                }

                Log::error('MTN MoMo token error: ' . $response->body());
                return null;
            } catch (Exception $e) {
                Log::error('MTN MoMo token exception: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Get HTTP client with authentication
     */
    protected function client()
    {
        $token = $this->getAccessToken();

        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Target-Environment' => $this->environment,
            'Ocp-Apim-Subscription-Key' => $this->subscriptionKey,
            'Content-Type' => 'application/json',
        ])->timeout(30);
    }

    /**
     * Collect payment from customer (Request to Pay)
     */
    public function collect(array $data): array
    {
        try {
            $referenceId = $data['reference'] ?? Str::uuid()->toString();

            $payload = [
                'amount' => (string) $data['amount'],
                'currency' => $this->currency,
                'externalId' => $referenceId,
                'payer' => [
                    'partyIdType' => 'MSISDN',
                    'partyId' => $this->formatPhone($data['phone']),
                ],
                'payerMessage' => $data['description'] ?? 'TopTopGo - Paiement trajet',
                'payeeNote' => 'Payment for ride ' . $referenceId,
            ];

            $response = $this->client()
                ->withHeaders(['X-Reference-Id' => $referenceId])
                ->post($this->baseUrl . '/collection/v1_0/requesttopay', $payload);

            if ($response->status() === 202) {
                return [
                    'success' => true,
                    'transaction_id' => $referenceId,
                    'status' => 'PENDING',
                    'data' => ['reference_id' => $referenceId],
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Request to pay failed',
                'data' => $response->json(),
            ];
        } catch (Exception $e) {
            Log::error('MTN MoMo collect error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send payout to driver (Disbursement/Transfer)
     */
    public function payout(array $data): array
    {
        try {
            $referenceId = $data['reference'] ?? Str::uuid()->toString();

            $payload = [
                'amount' => (string) $data['amount'],
                'currency' => $this->currency,
                'externalId' => $referenceId,
                'payee' => [
                    'partyIdType' => 'MSISDN',
                    'partyId' => $this->formatPhone($data['phone']),
                ],
                'payerMessage' => $data['description'] ?? 'TopTopGo - Paiement chauffeur',
                'payeeNote' => 'Driver earnings payout',
            ];

            $response = $this->client()
                ->withHeaders(['X-Reference-Id' => $referenceId])
                ->post($this->baseUrl . '/disbursement/v1_0/transfer', $payload);

            if ($response->status() === 202) {
                return [
                    'success' => true,
                    'transaction_id' => $referenceId,
                    'status' => 'PENDING',
                    'data' => ['reference_id' => $referenceId],
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Transfer failed',
                'data' => $response->json(),
            ];
        } catch (Exception $e) {
            Log::error('MTN MoMo payout error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get transaction status
     */
    public function getTransactionStatus(string $transactionId): array
    {
        try {
            $response = $this->client()
                ->get($this->baseUrl . '/collection/v1_0/requesttopay/' . $transactionId);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'status' => $this->mapStatus($data['status'] ?? 'PENDING'),
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get transaction status',
            ];
        } catch (Exception $e) {
            Log::error('MTN MoMo getTransactionStatus error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check account balance
     */
    public function getBalance(): array
    {
        try {
            $response = $this->client()
                ->get($this->baseUrl . '/collection/v1_0/account/balance');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get balance',
            ];
        } catch (Exception $e) {
            Log::error('MTN MoMo getBalance error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate account holder
     */
    public function validateAccountHolder(string $phone): array
    {
        try {
            $response = $this->client()
                ->get($this->baseUrl . '/collection/v1_0/accountholder/msisdn/' . $this->formatPhone($phone) . '/active');

            return [
                'success' => $response->successful(),
                'is_active' => $response->successful(),
            ];
        } catch (Exception $e) {
            Log::error('MTN MoMo validateAccountHolder error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle webhook callback
     */
    public function handleWebhook(array $payload): array
    {
        $referenceId = $payload['externalId'] ?? $payload['referenceId'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$referenceId || !$status) {
            return [
                'success' => false,
                'error' => 'Invalid webhook payload',
            ];
        }

        $transaction = Transaction::where('provider_transaction_id', $referenceId)
            ->orWhere('reference', $referenceId)
            ->first();

        if (!$transaction) {
            Log::warning('MTN MoMo webhook: Transaction not found', $payload);
            return [
                'success' => false,
                'error' => 'Transaction not found',
            ];
        }

        $mappedStatus = $this->mapStatus($status);

        $transaction->update([
            'status' => $mappedStatus,
            'provider_response' => $payload,
            'completed_at' => $mappedStatus === 'completed' ? now() : null,
            'failed_at' => $mappedStatus === 'failed' ? now() : null,
        ]);

        return [
            'success' => true,
            'transaction' => $transaction,
            'status' => $mappedStatus,
        ];
    }

    /**
     * Format phone number to E.164 format
     */
    protected function formatPhone(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Add Congo country code if not present
        if (strlen($phone) === 9) {
            $phone = '242' . $phone;
        }

        return $phone;
    }

    /**
     * Map MTN status to internal status
     */
    protected function mapStatus(string $mtnStatus): string
    {
        return match (strtoupper($mtnStatus)) {
            'SUCCESSFUL' => 'completed',
            'PENDING' => 'pending',
            'FAILED', 'REJECTED', 'TIMEOUT', 'CANCELLED' => 'failed',
            default => 'pending',
        };
    }

    /**
     * Get provider name
     */
    public function getProviderName(): string
    {
        return 'mtn_momo';
    }

    /**
     * Check if provider is available
     */
    public function isAvailable(): bool
    {
        return $this->getAccessToken() !== null;
    }
}
