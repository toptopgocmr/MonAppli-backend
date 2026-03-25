<?php

namespace App\Services\Payment;

use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Transfer;
use Stripe\Account;
use Stripe\Refund;
use Stripe\Exception\ApiErrorException;
use Exception;

class StripeService implements PaymentProviderInterface
{
    protected string $secretKey;
    protected string $currency;

    public function __construct()
    {
        $this->secretKey = config('payments.stripe.secret_key');
        $this->currency = strtolower(config('payments.stripe.currency', 'xaf'));

        Stripe::setApiKey($this->secretKey);
    }

    /**
     * Collect payment from customer (Create Payment Intent)
     */
    public function collect(array $data): array
    {
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $data['amount'], // Amount in smallest currency unit
                'currency' => $this->currency,
                'payment_method_types' => ['card'],
                'metadata' => [
                    'reference' => $data['reference'] ?? null,
                    'ride_id' => $data['ride_id'] ?? null,
                    'user_id' => $data['user_id'] ?? null,
                    'description' => $data['description'] ?? 'TopTopGo - Paiement trajet',
                ],
                'description' => $data['description'] ?? 'TopTopGo - Paiement trajet',
                'receipt_email' => $data['email'] ?? null,
                'capture_method' => 'automatic', // or 'manual' for escrow-like behavior
            ]);

            return [
                'success' => true,
                'transaction_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'status' => $this->mapStatus($paymentIntent->status),
                'data' => [
                    'id' => $paymentIntent->id,
                    'amount' => $paymentIntent->amount,
                    'currency' => $paymentIntent->currency,
                    'status' => $paymentIntent->status,
                ],
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe collect error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create Payment Intent with manual capture (for escrow)
     */
    public function createEscrowPayment(array $data): array
    {
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $data['amount'],
                'currency' => $this->currency,
                'payment_method_types' => ['card'],
                'capture_method' => 'manual', // Hold funds without capturing
                'metadata' => [
                    'reference' => $data['reference'] ?? null,
                    'ride_id' => $data['ride_id'] ?? null,
                    'type' => 'escrow',
                ],
                'description' => $data['description'] ?? 'TopTopGo - Paiement séquestré',
            ]);

            return [
                'success' => true,
                'transaction_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'status' => 'requires_capture',
                'data' => [
                    'id' => $paymentIntent->id,
                    'amount' => $paymentIntent->amount,
                ],
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe createEscrowPayment error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Capture escrowed payment
     */
    public function capturePayment(string $paymentIntentId, ?int $amount = null): array
    {
        try {
            $params = [];
            if ($amount !== null) {
                $params['amount_to_capture'] = $amount;
            }

            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            $paymentIntent->capture($params);

            return [
                'success' => true,
                'transaction_id' => $paymentIntent->id,
                'status' => $this->mapStatus($paymentIntent->status),
                'data' => $paymentIntent->toArray(),
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe capturePayment error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel/Release escrowed payment
     */
    public function cancelPayment(string $paymentIntentId): array
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            $paymentIntent->cancel();

            return [
                'success' => true,
                'status' => 'cancelled',
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe cancelPayment error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send payout to driver (via Stripe Connect)
     */
    public function payout(array $data): array
    {
        try {
            // Create a transfer to the connected account (driver)
            $transfer = Transfer::create([
                'amount' => $data['amount'],
                'currency' => $this->currency,
                'destination' => $data['stripe_account_id'], // Driver's Stripe Connect account
                'metadata' => [
                    'reference' => $data['reference'] ?? null,
                    'ride_id' => $data['ride_id'] ?? null,
                    'driver_id' => $data['driver_id'] ?? null,
                ],
                'description' => $data['description'] ?? 'TopTopGo - Paiement chauffeur',
            ]);

            return [
                'success' => true,
                'transaction_id' => $transfer->id,
                'status' => 'completed',
                'data' => $transfer->toArray(),
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe payout error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create Stripe Connect account for driver
     */
    public function createConnectAccount(array $data): array
    {
        try {
            $account = Account::create([
                'type' => 'express', // or 'standard' or 'custom'
                'country' => $data['country'] ?? 'CG',
                'email' => $data['email'],
                'capabilities' => [
                    'transfers' => ['requested' => true],
                ],
                'business_type' => 'individual',
                'metadata' => [
                    'driver_id' => $data['driver_id'] ?? null,
                ],
            ]);

            return [
                'success' => true,
                'account_id' => $account->id,
                'data' => $account->toArray(),
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe createConnectAccount error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create refund
     */
    public function refund(string $paymentIntentId, ?int $amount = null, ?string $reason = null): array
    {
        try {
            $params = [
                'payment_intent' => $paymentIntentId,
            ];

            if ($amount !== null) {
                $params['amount'] = $amount;
            }

            if ($reason !== null) {
                $params['reason'] = $reason; // duplicate, fraudulent, requested_by_customer
            }

            $refund = Refund::create($params);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'status' => $refund->status,
                'data' => $refund->toArray(),
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe refund error: ' . $e->getMessage());
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
            $paymentIntent = PaymentIntent::retrieve($transactionId);

            return [
                'success' => true,
                'status' => $this->mapStatus($paymentIntent->status),
                'data' => $paymentIntent->toArray(),
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe getTransactionStatus error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle webhook from Stripe
     */
    public function handleWebhook(array $payload): array
    {
        $event = $payload;
        $eventType = $event['type'] ?? null;
        $data = $event['data']['object'] ?? null;

        if (!$eventType || !$data) {
            return [
                'success' => false,
                'error' => 'Invalid webhook payload',
            ];
        }

        $transactionId = $data['id'] ?? null;
        $reference = $data['metadata']['reference'] ?? null;

        $transaction = Transaction::where('provider_transaction_id', $transactionId)
            ->orWhere('reference', $reference)
            ->first();

        if (!$transaction) {
            Log::warning('Stripe webhook: Transaction not found', ['event' => $eventType, 'id' => $transactionId]);
            return [
                'success' => false,
                'error' => 'Transaction not found',
            ];
        }

        $status = match ($eventType) {
            'payment_intent.succeeded' => 'completed',
            'payment_intent.payment_failed' => 'failed',
            'payment_intent.canceled' => 'cancelled',
            'payment_intent.requires_capture' => 'escrowed',
            default => $transaction->status,
        };

        $transaction->update([
            'status' => $status,
            'provider_response' => $data,
            'completed_at' => $status === 'completed' ? now() : null,
            'failed_at' => $status === 'failed' ? now() : null,
        ]);

        return [
            'success' => true,
            'transaction' => $transaction,
            'status' => $status,
            'event_type' => $eventType,
        ];
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        try {
            $webhookSecret = config('payments.stripe.webhook_secret');
            \Stripe\Webhook::constructEvent($payload, $signature, $webhookSecret);
            return true;
        } catch (Exception $e) {
            Log::error('Stripe webhook signature verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Map Stripe status to internal status
     */
    protected function mapStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'succeeded' => 'completed',
            'processing' => 'pending',
            'requires_payment_method', 'requires_confirmation', 'requires_action' => 'pending',
            'requires_capture' => 'escrowed',
            'canceled' => 'cancelled',
            default => 'pending',
        };
    }

    /**
     * Get provider name
     */
    public function getProviderName(): string
    {
        return 'stripe';
    }

    /**
     * Check if provider is available
     */
    public function isAvailable(): bool
    {
        try {
            \Stripe\Balance::retrieve();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
