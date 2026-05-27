<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payment\FlutterwaveService;
use App\Services\Payment\PaymentService;
use App\Services\Payment\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected PaymentService      $paymentService,
        protected StripeService       $stripeService,
        protected FlutterwaveService  $flutterwaveService,
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
    // =========================================================================

    public function handlePeexCollect(Request $request): JsonResponse
    {
        Log::info('Peex Collect Webhook received', $request->all());

        try {
            $result = $this->paymentService->handleWebhook('peex', $request->all());

            if ($result['success']) {
                return response()->json(['status' => 'success'], 200);
            }

            Log::warning('Peex webhook processing failed', $result);
            return response()->json(['status' => 'error', 'message' => $result['error']], 400);

        } catch (\Exception $e) {
            Log::error('Peex webhook exception: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }

    public function handlePeexPayout(Request $request): JsonResponse
    {
        Log::info('Peex Payout Webhook received', $request->all());

        try {
            $result = $this->paymentService->handleWebhook('peex', $request->all());

            if ($result['success']) {
                return response()->json(['status' => 'success'], 200);
            }

            return response()->json(['status' => 'error', 'message' => $result['error']], 400);

        } catch (\Exception $e) {
            Log::error('Peex payout webhook exception: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }

    public function handlePeexBankPayout(Request $request): JsonResponse
    {
        Log::info('Peex Bank Payout Webhook received', $request->all());

        try {
            $result = $this->paymentService->handleWebhook('peex', $request->all());

            return response()->json(['status' => $result['success'] ? 'success' : 'error'], 200);

        } catch (\Exception $e) {
            Log::error('Peex bank payout webhook exception: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
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
