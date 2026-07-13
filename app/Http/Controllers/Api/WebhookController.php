<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\PaymentValidated;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\Payment\FlutterwaveService;
use App\Services\Payment\PaymentService;
use App\Services\Payment\PeexService;
use App\Services\Payment\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    public function __construct(
        protected PaymentService      $paymentService,
        protected StripeService       $stripeService,
        protected FlutterwaveService  $flutterwaveService,
        protected PeexService         $peexService,
    ) {}

    // =========================================================================
    // FLUTTERWAVE
    // =========================================================================

    /**
     * Webhook unique Flutterwave (charge.completed, transfer.completed…)
     * URL : POST /api/webhooks/flutterwave
     *
     * ⚠ Cette route DOIT être exclue du middleware CSRF (VerifyCsrfToken).
     */
    public function handleFlutterwave(Request $request): JsonResponse
    {
        // 1. Vérification de la signature HMAC-SHA256
        $rawBody   = $request->getContent();
        $signature = $request->header('flutterwave-signature', '');

        if (!$this->flutterwaveService->verifyWebhookSignature($rawBody, $signature)) {
            Log::warning('FLW webhook : signature invalide', [
                'ip'        => $request->ip(),
                'signature' => $signature,
            ]);
            return response()->json(['status' => 'error', 'message' => 'Signature invalide'], 401);
        }

        // 2. Traitement
        try {
            $result = $this->paymentService->handleWebhook('flutterwave', $request->all());

            if ($result['success']) {
                return response()->json(['status' => 'success'], 200);
            }

            Log::warning('FLW webhook : traitement échoué', $result);
            // On renvoie 200 quand même pour éviter les retries inutiles
            // (erreur fonctionnelle, pas technique)
            return response()->json(['status' => 'acknowledged'], 200);

        } catch (\Exception $e) {
            Log::error('FLW webhook exception : ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            // 500 déclenchera un retry de la part de Flutterwave
            return response()->json(['status' => 'error'], 500);
        }
    }

    // =========================================================================
    // PEEX
    //
    // Peex secures its callbacks with HTTP Basic Auth (not a signature header)
    // and posts an ARRAY of transaction objects — see
    // https://peex-api-docs.peexit.com/notifications
    //
    // These handlers update the `Payment` model directly (the model actually
    // used by the live booking/payment flow), not the disconnected
    // Transaction/Ride scaffold that PaymentService::handleWebhook() targets.
    // =========================================================================

    protected function verifyPeexBasicAuth(Request $request): bool
    {
        $expectedUser = config('payments.peex.callback_username');
        $expectedPass = config('payments.peex.callback_password');

        return $request->getUser() === $expectedUser && $request->getPassword() === $expectedPass;
    }

    public function handlePeexCollect(Request $request): JsonResponse
    {
        if (!$this->verifyPeexBasicAuth($request)) {
            Log::warning('Peex collect webhook: invalid Basic Auth credentials', ['ip' => $request->ip()]);
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $items = $request->all();
        // Peex sends a JSON array at the request root; Laravel's $request->all()
        // still returns it as an array either way (list or single object).
        if (!array_is_list($items)) {
            $items = [$items];
        }

        Log::info('Peex Collect Webhook received', ['count' => count($items)]);

        $processed = 0;
        $errors = [];

        foreach ($items as $item) {
            $normalized = $this->peexService->handleWebhook($item);

            if (!$normalized['success']) {
                $errors[] = $normalized['error'] ?? 'invalid item';
                continue;
            }

            $payment = Payment::where('transaction_ref', $normalized['reference'])
                ->where('provider', 'peex')
                ->first();

            if (!$payment) {
                Log::warning('Peex webhook: no matching payment for track_id', ['track_id' => $normalized['reference']]);
                $errors[] = 'unknown track_id: ' . $normalized['reference'];
                continue;
            }

            $this->applyPeexStatus($payment, $normalized['status'], $normalized['reason'] ?? null);
            $processed++;
        }

        if ($processed === 0 && !empty($errors)) {
            return response()->json(['status' => 'error', 'message' => implode('; ', $errors)], 200);
        }

        return response()->json(['status' => 'success', 'processed' => $processed], 200);
    }

    public function handlePeexPayout(Request $request): JsonResponse
    {
        if (!$this->verifyPeexBasicAuth($request)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        Log::info('Peex Payout Webhook received', $request->all());
        // Driver payouts are not wired into an automated flow yet (see
        // PeexService::payout doc block) — logged for now so nothing is lost.

        return response()->json(['status' => 'success'], 200);
    }

    public function handlePeexBankPayout(Request $request): JsonResponse
    {
        if (!$this->verifyPeexBasicAuth($request)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        Log::info('Peex Bank Payout Webhook received', $request->all());

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Apply a mapped Peex status ('success'|'pending'|'failed'|'cancelled')
     * to a Payment row, mirroring what UserPaymentController::status() does
     * when polling.
     */
    protected function applyPeexStatus(Payment $payment, string $status, ?string $reason = null): void
    {
        if ($payment->status === 'success') {
            return; // already finalized, avoid double-processing
        }

        if ($status === 'success') {
            $payment->update([
                'status'         => 'success',
                'paid_at'        => now(),
                'receipt_number' => $payment->receipt_number ?? ('RCP-' . strtoupper(Str::random(8)) . '-' . now()->format('YmdHis')),
            ]);

            $booking = Booking::find($payment->booking_id);
            if ($booking && $booking->status !== 'paid') {
                $booking->update(['status' => 'paid']);
                PaymentValidated::dispatch($booking->load('trip'));
            }
        } elseif (in_array($status, ['failed', 'cancelled'], true)) {
            // ✅ FIX : on garde la vraie raison Peex (ex: "Solde insuffisant...")
            // au lieu de laisser l'app client afficher un message générique.
            $payment->update(['status' => $status, 'failure_reason' => $reason]);
        }
        // 'pending' → nothing to do, still waiting.
    }

    // =========================================================================
    // MTN MoMo
    // =========================================================================

    public function handleMtnMomo(Request $request): JsonResponse
    {
        Log::info('MTN MoMo Webhook received', $request->all());

        try {
            $result = $this->paymentService->handleWebhook('mtn_momo', $request->all());

            if ($result['success']) {
                return response()->json(['status' => 'success'], 200);
            }

            return response()->json(['status' => 'error', 'message' => $result['error']], 400);

        } catch (\Exception $e) {
            Log::error('MTN MoMo webhook exception: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }

    // =========================================================================
    // Airtel Money
    // =========================================================================

    public function handleAirtelMoney(Request $request): JsonResponse
    {
        Log::info('Airtel Money Webhook received', $request->all());

        try {
            $result = $this->paymentService->handleWebhook('airtel_money', $request->all());

            if ($result['success']) {
                return response()->json(['status' => 'success'], 200);
            }

            return response()->json(['status' => 'error', 'message' => $result['error']], 400);

        } catch (\Exception $e) {
            Log::error('Airtel Money webhook exception: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }

    // =========================================================================
    // Stripe
    // =========================================================================

    public function handleStripe(Request $request): JsonResponse
    {
        $payload   = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (!$this->stripeService->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Stripe webhook signature verification failed');
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
        }

        Log::info('Stripe Webhook received', ['type' => $request->input('type')]);

        try {
            $result = $this->paymentService->handleWebhook('stripe', $request->all());

            if ($result['success']) {
                return response()->json(['status' => 'success'], 200);
            }

            return response()->json(['status' => 'error', 'message' => $result['error']], 400);

        } catch (\Exception $e) {
            Log::error('Stripe webhook exception: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }

    // =========================================================================
    // Generic (tests)
    // =========================================================================

    public function handleGeneric(Request $request, string $provider): JsonResponse
    {
        Log::info("Generic webhook received for {$provider}", $request->all());

        try {
            $result = $this->paymentService->handleWebhook($provider, $request->all());
            return response()->json(['status' => $result['success'] ? 'success' : 'error'], 200);
        } catch (\Exception $e) {
            Log::error("Generic webhook exception for {$provider}: " . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }
}
