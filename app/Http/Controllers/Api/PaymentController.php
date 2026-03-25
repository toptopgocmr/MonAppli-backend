<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentService;
use App\Models\Ride;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Get available payment methods for the user's country
     *
     * @return JsonResponse
     */
    public function getPaymentMethods(): JsonResponse
    {
        $user = Auth::user();
        $countryCode = $user->country_code ?? 'CG';

        $providers = $this->paymentService->getAvailableProviders($countryCode);

        $methods = [];
        foreach ($providers as $provider) {
            $methods[] = [
                'id' => $provider,
                'name' => $this->getProviderDisplayName($provider),
                'icon' => $this->getProviderIcon($provider),
                'type' => $this->getProviderType($provider),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'methods' => $methods,
                'default' => $user->preferred_payment_method ?? config('payments.default'),
            ],
        ]);
    }

    /**
     * Initiate payment for a ride
     *
     * @param Request $request
     * @param int $rideId
     * @return JsonResponse
     */
    public function initiateRidePayment(Request $request, int $rideId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|string|in:peex,mtn_momo,airtel_money,stripe',
            'phone' => 'required_unless:provider,stripe|string',
            'email' => 'required_if:provider,stripe|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $ride = Ride::find($rideId);

        if (!$ride) {
            return response()->json([
                'success' => false,
                'message' => 'Trajet non trouvé',
            ], 404);
        }

        if ($ride->passenger_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé',
            ], 403);
        }

        if ($ride->payment_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Ce trajet a déjà été payé ou est en cours de paiement',
            ], 400);
        }

        $result = $this->paymentService->initiateRidePayment(
            $ride,
            $request->provider,
            $request->all()
        );

        if ($result['success']) {
            $response = [
                'success' => true,
                'message' => 'Paiement initié avec succès',
                'data' => [
                    'transaction_id' => $result['transaction']->id,
                    'reference' => $result['transaction']->reference,
                    'amount' => $result['transaction']->amount,
                    'status' => $result['transaction']->status,
                ],
            ];

            // For Stripe, include client_secret for frontend
            if ($request->provider === 'stripe' && isset($result['provider_data']['client_secret'])) {
                $response['data']['client_secret'] = $result['provider_data']['client_secret'];
            }

            return response()->json($response);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error'] ?? 'Erreur lors de l\'initiation du paiement',
        ], 400);
    }

    /**
     * Get payment status for a transaction
     *
     * @param string $reference
     * @return JsonResponse
     */
    public function getPaymentStatus(string $reference): JsonResponse
    {
        $transaction = Transaction::where('reference', $reference)
            ->where('user_id', Auth::id())
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction non trouvée',
            ], 404);
        }

        // If still processing, check with provider
        if ($transaction->isPending() && $transaction->provider_transaction_id) {
            $provider = $this->paymentService->provider($transaction->provider);
            $status = $provider->getTransactionStatus($transaction->provider_transaction_id);

            if ($status['success'] && isset($status['status'])) {
                $transaction->update(['status' => $status['status']]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'reference' => $transaction->reference,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'status' => $transaction->status,
                'status_label' => $transaction->getStatusName(),
                'provider' => $transaction->getProviderName(),
                'created_at' => $transaction->created_at,
                'completed_at' => $transaction->completed_at,
            ],
        ]);
    }

    /**
     * Estimate payment breakdown for a ride
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function estimateBreakdown(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $breakdown = $this->paymentService->estimatePaymentBreakdown($request->amount);

        return response()->json([
            'success' => true,
            'data' => $breakdown,
        ]);
    }

    /**
     * Get user's wallet balance (for drivers)
     *
     * @return JsonResponse
     */
    public function getWalletBalance(): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isDriver()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux chauffeurs',
            ], 403);
        }

        $wallet = $user->wallet;

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $wallet?->balance ?? 0,
                'pending_balance' => $wallet?->pending_balance ?? 0,
                'total_earned' => $wallet?->total_earned ?? 0,
                'total_withdrawn' => $wallet?->total_withdrawn ?? 0,
                'currency' => $wallet?->currency ?? 'XAF',
                'formatted_balance' => $wallet?->getFormattedBalance() ?? '0 XAF',
            ],
        ]);
    }

    /**
     * Request a withdrawal (for drivers)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function requestWithdrawal(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isDriver()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux chauffeurs',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1000',
            'provider' => 'required|string|in:peex,mtn_momo,airtel_money',
            'phone' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $wallet = $user->wallet;

        if (!$wallet || !$wallet->canWithdraw($request->amount)) {
            return response()->json([
                'success' => false,
                'message' => 'Solde insuffisant ou montant minimum non atteint',
            ], 400);
        }

        $result = $this->paymentService->processDriverWithdrawal(
            $user,
            $request->amount,
            $request->provider,
            $request->all()
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Demande de retrait initiée',
                'data' => [
                    'transaction_id' => $result['transaction']->id,
                    'reference' => $result['transaction']->reference,
                    'amount' => $result['transaction']->amount,
                    'status' => $result['transaction']->status,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error'] ?? 'Erreur lors du retrait',
        ], 400);
    }

    /**
     * Get transaction history
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTransactionHistory(Request $request): JsonResponse
    {
        $filters = [
            'type' => $request->type,
            'status' => $request->status,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
            'per_page' => $request->per_page ?? 20,
        ];

        $transactions = $this->paymentService->getUserTransactions(Auth::id(), $filters);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Refund a ride payment (admin or auto-refund on cancellation)
     *
     * @param Request $request
     * @param int $rideId
     * @return JsonResponse
     */
    public function refundPayment(Request $request, int $rideId): JsonResponse
    {
        $ride = Ride::find($rideId);

        if (!$ride) {
            return response()->json([
                'success' => false,
                'message' => 'Trajet non trouvé',
            ], 404);
        }

        // Only allow refund if user is the passenger or admin
        $user = Auth::user();
        if ($ride->passenger_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé',
            ], 403);
        }

        $result = $this->paymentService->refundRidePayment(
            $ride,
            $request->amount,
            $request->reason ?? 'requested_by_customer'
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Remboursement effectué',
                'data' => [
                    'refund_amount' => $result['refund_amount'],
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error'] ?? 'Erreur lors du remboursement',
        ], 400);
    }

    /**
     * Verify phone number with Peex
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyPhone(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'operator' => 'nullable|string|in:MTN,AIRTEL',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $operator = $request->operator ?? $this->paymentService->detectOperator($request->phone);

        $peex = $this->paymentService->provider('peex');
        $result = $peex->verifyPhone($request->phone, $operator);

        return response()->json($result);
    }

    /**
     * Detect mobile operator from phone number
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function detectOperator(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $operator = $this->paymentService->detectOperator($request->phone);

        return response()->json([
            'success' => true,
            'data' => [
                'operator' => $operator,
                'operator_name' => $operator === 'MTN' ? 'MTN Mobile Money' : 'Airtel Money',
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    protected function getProviderDisplayName(string $provider): string
    {
        return match ($provider) {
            'peex' => 'Peex (Mobile Money)',
            'mtn_momo' => 'MTN Mobile Money',
            'airtel_money' => 'Airtel Money',
            'stripe' => 'Carte bancaire',
            default => $provider,
        };
    }

    protected function getProviderIcon(string $provider): string
    {
        return match ($provider) {
            'peex' => 'peex_logo.png',
            'mtn_momo' => 'mtn_momo_logo.png',
            'airtel_money' => 'airtel_money_logo.png',
            'stripe' => 'card_icon.png',
            default => 'default_payment.png',
        };
    }

    protected function getProviderType(string $provider): string
    {
        return match ($provider) {
            'stripe' => 'card',
            default => 'mobile_money',
        };
    }
}
