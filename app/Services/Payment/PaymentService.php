<?php

namespace App\Services\Payment;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Ride;
use App\Models\User;
use App\Events\PaymentCompleted;
use App\Events\PaymentFailed;
use App\Events\PayoutCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class PaymentService
{
    protected array $providers = [];

    public function __construct(
        protected PeexService $peex,
        protected MtnMomoService $mtnMomo,
        protected AirtelMoneyService $airtelMoney,
        protected StripeService $stripe
    ) {
        $this->providers = [
            'peex' => $peex,
            'mtn_momo' => $mtnMomo,
            'airtel_money' => $airtelMoney,
            'stripe' => $stripe,
        ];
    }

    /**
     * Get a payment provider instance
     */
    public function provider(string $name): PaymentProviderInterface
    {
        if (!isset($this->providers[$name])) {
            throw new Exception("Payment provider '{$name}' not found");
        }

        return $this->providers[$name];
    }

    /**
     * Get available payment providers for a country
     */
    public function getAvailableProviders(string $countryCode = 'CG'): array
    {
        $configured = config('payments.providers_by_country.' . $countryCode, []);

        return array_filter($configured, function ($provider) {
            return isset($this->providers[$provider]) && $this->providers[$provider]->isAvailable();
        });
    }

    /**
     * Initiate ride payment (escrow)
     * Collects payment from passenger and holds it until ride completion
     */
    public function initiateRidePayment(Ride $ride, string $provider, array $paymentData): array
    {
        $reference = 'RIDE-' . $ride->id . '-' . Str::random(8);

        DB::beginTransaction();

        try {
            // Calculate amounts
            $rideAmount = $ride->price;
            $commission = $this->calculateCommission($rideAmount);
            $driverAmount = $rideAmount - $commission;

            // Create transaction record
            $transaction = Transaction::create([
                'reference' => $reference,
                'user_id' => $ride->passenger_id,
                'ride_id' => $ride->id,
                'type' => 'ride_payment',
                'provider' => $provider,
                'amount' => $rideAmount,
                'commission' => $commission,
                'driver_amount' => $driverAmount,
                'currency' => 'XAF',
                'status' => 'pending',
                'metadata' => [
                    'phone' => $paymentData['phone'] ?? null,
                    'operator' => $paymentData['operator'] ?? null,
                ],
            ]);

            // Initiate payment with provider
            $providerInstance = $this->provider($provider);

            $result = $providerInstance->collect([
                'phone' => $paymentData['phone'],
                'amount' => $rideAmount,
                'operator' => $paymentData['operator'] ?? $this->detectOperator($paymentData['phone']),
                'reference' => $reference,
                'description' => "TopTopGo - Trajet #{$ride->id}",
                'email' => $paymentData['email'] ?? null,
                'user_id' => $ride->passenger_id,
                'ride_id' => $ride->id,
            ]);

            if ($result['success']) {
                $transaction->update([
                    'provider_transaction_id' => $result['transaction_id'],
                    'status' => 'processing',
                    'provider_response' => $result['data'] ?? null,
                ]);

                // For Stripe, return client_secret for frontend
                if ($provider === 'stripe' && isset($result['client_secret'])) {
                    $transaction->update([
                        'metadata' => array_merge($transaction->metadata ?? [], [
                            'client_secret' => $result['client_secret'],
                        ]),
                    ]);
                }

                DB::commit();

                return [
                    'success' => true,
                    'transaction' => $transaction,
                    'provider_data' => $result,
                ];
            }

            // Payment initiation failed
            $transaction->update([
                'status' => 'failed',
                'failed_at' => now(),
                'provider_response' => $result,
            ]);

            DB::commit();

            return [
                'success' => false,
                'error' => $result['error'] ?? 'Payment initiation failed',
                'transaction' => $transaction,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('initiateRidePayment error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Complete ride payment and release funds to driver
     * Called when ride is completed successfully
     */
    public function completeRidePayment(Ride $ride): array
    {
        $transaction = Transaction::where('ride_id', $ride->id)
            ->where('type', 'ride_payment')
            ->where('status', 'completed')
            ->first();

        if (!$transaction) {
            return [
                'success' => false,
                'error' => 'No completed payment found for this ride',
            ];
        }

        DB::beginTransaction();

        try {
            // Credit driver's wallet
            $driver = User::find($ride->driver_id);
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $driver->id],
                ['balance' => 0, 'pending_balance' => 0, 'currency' => 'XAF']
            );

            $wallet->increment('balance', $transaction->driver_amount);

            // Create driver credit transaction
            Transaction::create([
                'reference' => 'CREDIT-' . $transaction->reference,
                'user_id' => $driver->id,
                'ride_id' => $ride->id,
                'type' => 'driver_credit',
                'provider' => 'internal',
                'amount' => $transaction->driver_amount,
                'currency' => 'XAF',
                'status' => 'completed',
                'completed_at' => now(),
                'metadata' => [
                    'original_transaction_id' => $transaction->id,
                    'ride_amount' => $transaction->amount,
                    'commission' => $transaction->commission,
                ],
            ]);

            // Update ride status
            $ride->update([
                'payment_status' => 'completed',
                'payment_released_at' => now(),
            ]);

            DB::commit();

            event(new PaymentCompleted($transaction, $ride));

            return [
                'success' => true,
                'driver_credited' => $transaction->driver_amount,
                'wallet_balance' => $wallet->fresh()->balance,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('completeRidePayment error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process driver withdrawal from wallet
     */
    public function processDriverWithdrawal(User $driver, float $amount, string $provider, array $withdrawalData): array
    {
        $wallet = Wallet::where('user_id', $driver->id)->first();

        if (!$wallet || $wallet->balance < $amount) {
            return [
                'success' => false,
                'error' => 'Insufficient wallet balance',
            ];
        }

        $reference = 'WD-' . $driver->id . '-' . Str::random(8);

        DB::beginTransaction();

        try {
            // Deduct from wallet and hold in pending
            $wallet->decrement('balance', $amount);
            $wallet->increment('pending_balance', $amount);

            // Create withdrawal transaction
            $transaction = Transaction::create([
                'reference' => $reference,
                'user_id' => $driver->id,
                'type' => 'withdrawal',
                'provider' => $provider,
                'amount' => $amount,
                'currency' => 'XAF',
                'status' => 'pending',
                'metadata' => [
                    'phone' => $withdrawalData['phone'] ?? $driver->phone,
                    'operator' => $withdrawalData['operator'] ?? null,
                ],
            ]);

            // Initiate payout with provider
            $providerInstance = $this->provider($provider);

            $result = $providerInstance->payout([
                'phone' => $withdrawalData['phone'] ?? $driver->phone,
                'amount' => $amount,
                'operator' => $withdrawalData['operator'] ?? $this->detectOperator($driver->phone),
                'reference' => $reference,
                'description' => "TopTopGo - Retrait chauffeur #{$driver->id}",
                'driver_id' => $driver->id,
                'stripe_account_id' => $driver->stripe_account_id ?? null,
            ]);

            if ($result['success']) {
                $transaction->update([
                    'provider_transaction_id' => $result['transaction_id'],
                    'status' => 'processing',
                    'provider_response' => $result['data'] ?? null,
                ]);

                DB::commit();

                return [
                    'success' => true,
                    'transaction' => $transaction,
                    'message' => 'Withdrawal initiated successfully',
                ];
            }

            // Payout failed - reverse wallet changes
            $wallet->increment('balance', $amount);
            $wallet->decrement('pending_balance', $amount);

            $transaction->update([
                'status' => 'failed',
                'failed_at' => now(),
                'provider_response' => $result,
            ]);

            DB::commit();

            return [
                'success' => false,
                'error' => $result['error'] ?? 'Withdrawal failed',
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('processDriverWithdrawal error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle payment webhook callback
     */
    public function handleWebhook(string $provider, array $payload): array
    {
        $providerInstance = $this->provider($provider);
        $result = $providerInstance->handleWebhook($payload);

        if ($result['success'] && isset($result['transaction'])) {
            $transaction = $result['transaction'];

            // Handle different transaction statuses
            if ($result['status'] === 'completed') {
                $this->onPaymentCompleted($transaction);
            } elseif ($result['status'] === 'failed') {
                $this->onPaymentFailed($transaction);
            }
        }

        return $result;
    }

    /**
     * Handle successful payment
     */
    protected function onPaymentCompleted(Transaction $transaction): void
    {
        if ($transaction->type === 'ride_payment') {
            // Update ride payment status to escrowed
            $ride = Ride::find($transaction->ride_id);
            if ($ride) {
                $ride->update(['payment_status' => 'escrowed']);
                event(new PaymentCompleted($transaction, $ride));
            }
        } elseif ($transaction->type === 'withdrawal') {
            // Finalize withdrawal
            $wallet = Wallet::where('user_id', $transaction->user_id)->first();
            if ($wallet) {
                $wallet->decrement('pending_balance', $transaction->amount);
            }
            event(new PayoutCompleted($transaction));
        }
    }

    /**
     * Handle failed payment
     */
    protected function onPaymentFailed(Transaction $transaction): void
    {
        if ($transaction->type === 'ride_payment') {
            $ride = Ride::find($transaction->ride_id);
            if ($ride) {
                $ride->update(['payment_status' => 'failed']);
                event(new PaymentFailed($transaction, $ride));
            }
        } elseif ($transaction->type === 'withdrawal') {
            // Return funds to wallet
            $wallet = Wallet::where('user_id', $transaction->user_id)->first();
            if ($wallet) {
                $wallet->increment('balance', $transaction->amount);
                $wallet->decrement('pending_balance', $transaction->amount);
            }
        }
    }

    /**
     * Refund a ride payment
     */
    public function refundRidePayment(Ride $ride, ?float $amount = null, string $reason = 'requested_by_customer'): array
    {
        $transaction = Transaction::where('ride_id', $ride->id)
            ->where('type', 'ride_payment')
            ->whereIn('status', ['completed', 'escrowed'])
            ->first();

        if (!$transaction) {
            return [
                'success' => false,
                'error' => 'No refundable payment found',
            ];
        }

        $refundAmount = $amount ?? $transaction->amount;

        DB::beginTransaction();

        try {
            // For Stripe, use native refund
            if ($transaction->provider === 'stripe') {
                $result = $this->stripe->refund(
                    $transaction->provider_transaction_id,
                    $refundAmount,
                    $reason
                );
            } else {
                // For mobile money, initiate a payout back to customer
                $result = $this->provider($transaction->provider)->payout([
                    'phone' => $transaction->metadata['phone'],
                    'amount' => $refundAmount,
                    'operator' => $transaction->metadata['operator'],
                    'reference' => 'REFUND-' . $transaction->reference,
                    'description' => 'TopTopGo - Remboursement trajet',
                ]);
            }

            if ($result['success']) {
                // Create refund transaction
                Transaction::create([
                    'reference' => 'REFUND-' . $transaction->reference,
                    'user_id' => $transaction->user_id,
                    'ride_id' => $ride->id,
                    'type' => 'refund',
                    'provider' => $transaction->provider,
                    'amount' => $refundAmount,
                    'currency' => 'XAF',
                    'status' => 'completed',
                    'completed_at' => now(),
                    'metadata' => [
                        'original_transaction_id' => $transaction->id,
                        'reason' => $reason,
                    ],
                ]);

                $transaction->update([
                    'status' => 'refunded',
                    'refunded_at' => now(),
                ]);

                $ride->update(['payment_status' => 'refunded']);

                DB::commit();

                return [
                    'success' => true,
                    'refund_amount' => $refundAmount,
                ];
            }

            DB::rollBack();

            return [
                'success' => false,
                'error' => $result['error'] ?? 'Refund failed',
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('refundRidePayment error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate platform commission
     */
    public function calculateCommission(float $amount): float
    {
        $percent = config('payments.fees.platform_commission', 15);
        $minimum = config('payments.fees.minimum_commission', 100);

        $commission = ($amount * $percent) / 100;

        return max($commission, $minimum);
    }

    /**
     * Estimate ride payment breakdown
     */
    public function estimatePaymentBreakdown(float $ridePrice): array
    {
        $commission = $this->calculateCommission($ridePrice);
        $driverAmount = $ridePrice - $commission;

        return [
            'ride_price' => $ridePrice,
            'platform_commission' => $commission,
            'commission_percent' => config('payments.fees.platform_commission', 15),
            'driver_earnings' => $driverAmount,
            'currency' => 'XAF',
        ];
    }

    /**
     * Detect mobile money operator from phone number
     */
    public function detectOperator(string $phone): string
    {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove country code if present
        if (str_starts_with($phone, '242')) {
            $phone = substr($phone, 3);
        }

        // Congo Brazzaville prefixes
        $mtnPrefixes = ['04', '05', '06'];
        $airtelPrefixes = ['01', '02', '03'];

        $prefix = substr($phone, 0, 2);

        if (in_array($prefix, $mtnPrefixes)) {
            return 'MTN';
        }

        if (in_array($prefix, $airtelPrefixes)) {
            return 'AIRTEL';
        }

        return 'MTN'; // Default
    }

    /**
     * Get transaction history for a user
     */
    public function getUserTransactions(int $userId, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Transaction::where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }
}
