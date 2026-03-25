<?php

namespace App\Services\Payment;

use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Exception;

class AirtelMoneyService implements PaymentProviderInterface
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $environment;
    protected string $currency;
    protected string $country;
    protected ?string $callbackUrl;

    public function __construct()
    {
        $this->baseUrl = config('payments.airtel_money.base_url');
        $this->clientId = config('payments.airtel_money.client_id');
        $this->clientSecret = config('payments.airtel_money.client_secret');
        $this->environment = config('payments.airtel_money.environment', 'sandbox');
        $this->currency = config('payments.airtel_money.currency', 'XAF');
        $this->country = config('payments.airtel_money.country', 'CG');
        $this->callbackUrl = config('payments.airtel_money.callback_url');
    }

    /**
     * Get OAuth access token
     */
    protected function getAccessToken(): ?string
    {
        $cacheKey = 'airtel_money_access_token';

        return Cache::remember($cacheKey, 3500, function () {
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => '*/*',
                ])->post($this->baseUrl . '/auth/oauth2/token', [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'grant_type' => 'client_credentials',
                ]);

                if ($response->successful()) {
                    return $response->json()['access_token'] ?? null;
                }

                Log::error('Airtel Money token error: ' . $response->body());
                return null;
            } catch (Exception $e) {
                Log::error('Airtel Money token exception: ' . $e->getMessage());
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
            'X-Country' => $this->country,
            'X-Currency' => $this->currency,
            'Content-Type' => 'application/json',
            'Accept' => '*/*',
        ])->timeout(30);
    }

    /**
     * Collect payment from customer (USSD Push)
     */
    public function collect(array $data): array
    {
        try {
            $reference = $data['reference'] ?? Str::uuid()->toString();

            $payload = [
                'reference' => $reference,
                'subscriber' => [
                    'country' => $this->country,
                    'currency' => $this->currency,
                    'msisdn' => $this->formatPhone($data['phone']),
                ],
                'transaction' => [
                    'amount' => $data['amount'],
                    'country' => $this->country,
                    'currency' => $this->currency,
                    'id' => $reference,
                ],
            ];

            $response = $this->client()
                ->post($this->baseUrl . '/merchant/v1/payments/', $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $status = $responseData['status']['code'] ?? null;

                return [
                    'success' => $status === '200' || $status === 'TS',
                    'transaction_id' => $responseData['data']['transaction']['id'] ?? $reference,
                    'status' => $this->mapStatus($responseData['data']['transaction']['status'] ?? 'PENDING'),
                    'data' => $responseData,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['status']['message'] ?? 'Collection request failed',
                'data' => $response->json(),
            ];
        } catch (Exception $e) {
            Log::error('Airtel Money collect error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send payout to driver (Disbursement)
     */
    public function payout(array $data): array
    {
        try {
            $reference = $data['reference'] ?? Str::uuid()->toString();

            $payload = [
                'payee' => [
                    'msisdn' => $this->formatPhone($data['phone']),
                    'wallet_type' => 'NORMAL',
                ],
                'reference' => $reference,
                'pin' => $data['pin'] ?? config('payments.airtel_money.disbursement_pin'),
                'transaction' => [
                    'amount' => $data['amount'],
                    'id' => $reference,
                    'type' => 'B2C',
                ],
            ];

            $response = $this->client()
                ->post($this->baseUrl . '/standard/v1/disbursements/', $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $status = $responseData['status']['code'] ?? null;

                return [
                    'success' => $status === '200' || $status === 'TS',
                    'transaction_id' => $responseData['data']['transaction']['id'] ?? $reference,
                    'status' => $this->mapStatus($responseData['data']['transaction']['status'] ?? 'PENDING'),
                    'data' => $responseData,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['status']['message'] ?? 'Disbursement failed',
                'data' => $response->json(),
            ];
        } catch (Exception $e) {
            Log::error('Airtel Money payout error: ' . $e->getMessage());
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
                ->get($this->baseUrl . '/standard/v1/payments/' . $transactionId);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'status' => $this->mapStatus($data['data']['transaction']['status'] ?? 'PENDING'),
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get transaction status',
            ];
        } catch (Exception $e) {
            Log::error('Airtel Money getTransactionStatus error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check user KYC status
     */
    public function checkKyc(string $phone): array
    {
        try {
            $response = $this->client()
                ->get($this->baseUrl . '/standard/v1/users/' . $this->formatPhone($phone));

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'is_registered' => ($data['data']['is_registered'] ?? false),
                    'is_barred' => ($data['data']['is_barred'] ?? false),
                    'first_name' => $data['data']['first_name'] ?? null,
                    'last_name' => $data['data']['last_name'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => 'KYC check failed',
            ];
        } catch (Exception $e) {
            Log::error('Airtel Money checkKyc error: ' . $e->getMessage());
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
        $transactionId = $payload['transaction']['id'] ?? null;
        $status = $payload['transaction']['status_code'] ?? $payload['transaction']['status'] ?? null;

        if (!$transactionId || !$status) {
            return [
                'success' => false,
                'error' => 'Invalid webhook payload',
            ];
        }

        $transaction = Transaction::where('provider_transaction_id', $transactionId)
            ->orWhere('reference', $transactionId)
            ->first();

        if (!$transaction) {
            Log::warning('Airtel Money webhook: Transaction not found', $payload);
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
     * Format phone number
     */
    protected function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove country code if present
        if (str_starts_with($phone, '242')) {
            $phone = substr($phone, 3);
        }

        return $phone;
    }

    /**
     * Map Airtel status to internal status
     */
    protected function mapStatus(string $airtelStatus): string
    {
        return match (strtoupper($airtelStatus)) {
            'TS', 'TIP', 'SUCCESS', 'SUCCESSFUL' => 'completed',
            'TF', 'FAILED', 'REJECTED' => 'failed',
            'TA', 'PENDING', 'AMBIGUOUS' => 'pending',
            default => 'pending',
        };
    }

    /**
     * Get provider name
     */
    public function getProviderName(): string
    {
        return 'airtel_money';
    }

    /**
     * Check if provider is available
     */
    public function isAvailable(): bool
    {
        return $this->getAccessToken() !== null;
    }
}
