<?php

namespace App\Services\Payment;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PeexService implements PaymentProviderInterface
{
    protected string $baseUrl;
    protected string $secretKey;
    protected bool $sandbox;

    public function __construct()
    {
        $this->sandbox = config('payments.peex.sandbox', true);
        $this->baseUrl = $this->sandbox
            ? config('payments.peex.base_url')
            : config('payments.peex.production_url');
        $this->secretKey = config('payments.peex.secret_key');
    }

    /**
     * Get HTTP client with authentication headers
     */
    protected function client()
    {
        return Http::withHeaders([
            'SECRETKEY' => $this->secretKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('payments.peex.timeout', 30));
    }

    /**
     * Get partner information (balance, fees)
     */
    public function getPartnerInfo(): array
    {
        try {
            $response = $this->client()->get($this->baseUrl . 'partner/info');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to get partner info',
            ];
        } catch (Exception $e) {
            Log::error('Peex getPartnerInfo error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify phone number before payment
     */
    public function verifyPhone(string $phone, string $operator): array
    {
        try {
            $response = $this->client()->post($this->baseUrl . 'phone/verify', [
                'phone' => $phone,
                'operator' => $operator, // MTN, AIRTEL
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Phone verification failed',
            ];
        } catch (Exception $e) {
            Log::error('Peex verifyPhone error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Collect payment from customer (for ride payment)
     * This is used when a passenger pays for a ride
     */
    public function collect(array $data): array
    {
        try {
            $payload = [
                'phone' => $data['phone'],
                'amount' => $data['amount'],
                'operator' => $data['operator'], // MTN, AIRTEL
                'reference' => $data['reference'],
                'description' => $data['description'] ?? 'TopTopGo - Paiement trajet',
                'callback_url' => $data['callback_url'] ?? route('webhooks.peex.collect'),
            ];

            $response = $this->client()->post($this->baseUrl . 'collect/request', $payload);

            if ($response->successful()) {
                $responseData = $response->json();

                return [
                    'success' => true,
                    'transaction_id' => $responseData['transaction_id'] ?? null,
                    'status' => $responseData['status'] ?? 'PENDING',
                    'data' => $responseData,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Collection request failed',
                'data' => $response->json(),
            ];
        } catch (Exception $e) {
            Log::error('Peex collect error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Payout to driver (remittance)
     * This is used to pay the driver after ride completion
     */
    public function payout(array $data): array
    {
        try {
            $payload = [
                'phone' => $data['phone'],
                'amount' => $data['amount'],
                'operator' => $data['operator'], // MTN, AIRTEL
                'reference' => $data['reference'],
                'description' => $data['description'] ?? 'TopTopGo - Paiement chauffeur',
                'callback_url' => $data['callback_url'] ?? route('webhooks.peex.payout'),
            ];

            $response = $this->client()->post($this->baseUrl . 'remittance/request', $payload);

            if ($response->successful()) {
                $responseData = $response->json();

                return [
                    'success' => true,
                    'transaction_id' => $responseData['transaction_id'] ?? null,
                    'status' => $responseData['status'] ?? 'PENDING',
                    'data' => $responseData,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Payout request failed',
                'data' => $response->json(),
            ];
        } catch (Exception $e) {
            Log::error('Peex payout error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Bank payout (for larger withdrawals)
     */
    public function bankPayout(array $data): array
    {
        try {
            $payload = [
                'bank_code' => $data['bank_code'],
                'account_number' => $data['account_number'],
                'account_name' => $data['account_name'],
                'amount' => $data['amount'],
                'reference' => $data['reference'],
                'description' => $data['description'] ?? 'TopTopGo - Virement bancaire',
                'callback_url' => $data['callback_url'] ?? route('webhooks.peex.bank-payout'),
            ];

            $response = $this->client()->post($this->baseUrl . 'remittance/bank/request', $payload);

            if ($response->successful()) {
                $responseData = $response->json();

                return [
                    'success' => true,
                    'transaction_id' => $responseData['transaction_id'] ?? null,
                    'status' => $responseData['status'] ?? 'PENDING',
                    'data' => $responseData,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Bank payout request failed',
            ];
        } catch (Exception $e) {
            Log::error('Peex bankPayout error: ' . $e->getMessage());
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
            $response = $this->client()->get($this->baseUrl . 'transaction/' . $transactionId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to get transaction status',
            ];
        } catch (Exception $e) {
            Log::error('Peex getTransactionStatus error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all transactions with filters
     */
    public function getTransactions(array $filters = []): array
    {
        try {
            $response = $this->client()->get($this->baseUrl . 'transactions', $filters);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to get transactions',
            ];
        } catch (Exception $e) {
            Log::error('Peex getTransactions error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate fees for a given amount
     */
    public function calculateFees(int $amount, string $type = 'collect'): array
    {
        try {
            $response = $this->client()->post($this->baseUrl . 'fees/calculate', [
                'amount' => $amount,
                'type' => $type, // collect or payout
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to calculate fees',
            ];
        } catch (Exception $e) {
            Log::error('Peex calculateFees error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle webhook callback from Peex
     */
    public function handleWebhook(array $payload): array
    {
        $transactionId = $payload['transaction_id'] ?? null;
        $status = $payload['status'] ?? null;
        $reference = $payload['reference'] ?? null;

        if (!$transactionId || !$status) {
            return [
                'success' => false,
                'error' => 'Invalid webhook payload',
            ];
        }

        // Find and update the local transaction
        $transaction = Transaction::where('provider_transaction_id', $transactionId)
            ->orWhere('reference', $reference)
            ->first();

        if (!$transaction) {
            Log::warning('Peex webhook: Transaction not found', $payload);
            return [
                'success' => false,
                'error' => 'Transaction not found',
            ];
        }

        // Map Peex status to our status
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
     * Map Peex status to internal status
     */
    protected function mapStatus(string $peexStatus): string
    {
        return match (strtoupper($peexStatus)) {
            'SUCCESS', 'SUCCESSFUL', 'COMPLETED' => 'completed',
            'PENDING', 'PROCESSING' => 'pending',
            'FAILED', 'REJECTED', 'CANCELLED' => 'failed',
            default => 'pending',
        };
    }

    /**
     * Get provider name
     */
    public function getProviderName(): string
    {
        return 'peex';
    }

    /**
     * Check if provider is available
     */
    public function isAvailable(): bool
    {
        $info = $this->getPartnerInfo();
        return $info['success'] ?? false;
    }
}
