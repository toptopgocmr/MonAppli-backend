<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentService;
use App\Services\Payment\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected StripeService $stripeService
    ) {}

    /**
     * Handle Peex webhook for collect (payment received)
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Handle Peex webhook for payout (driver payment)
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Handle Peex webhook for bank payout
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Handle MTN MoMo webhook
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Handle Airtel Money webhook
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Handle Stripe webhook
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleStripe(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        // Verify webhook signature
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

    /**
     * Generic webhook handler (for testing)
     *
     * @param Request $request
     * @param string $provider
     * @return JsonResponse
     */
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
