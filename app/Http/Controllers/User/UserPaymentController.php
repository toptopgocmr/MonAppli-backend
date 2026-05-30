<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Events\PaymentValidated;
use App\Services\Payment\FlutterwaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserPaymentController extends Controller
{
    public function __construct(
        protected FlutterwaveService $flutterwave
    ) {}

    // ── Paiement Mobile Money via Flutterwave ────────────────────────
    public function mobileMoney(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'phone'      => 'required|string',
            'provider'   => 'required|in:mtn,airtel',
        ]);

        $booking = Booking::with('trip')
            ->where('user_id', Auth::id())
            ->findOrFail($request->booking_id);

        if ($booking->status !== 'accepted') {
            return response()->json([
                'success' => false,
                'message' => 'La réservation doit être acceptée avant le paiement (statut: ' . $booking->status . ').',
            ], 422);
        }

        $alreadyPaid = Payment::where('booking_id', $booking->id)
            ->where('status', 'success')
            ->exists();

        if ($alreadyPaid) {
            return response()->json([
                'success' => false,
                'message' => 'Cette réservation a déjà été payée.',
            ], 422);
        }

        $transactionRef = 'TXN-' . strtoupper(Str::random(10));
        $user           = Auth::user();
        $operator       = strtoupper($request->provider); // mtn → MTN, airtel → AIRTEL

        // ── Créer le paiement en base (statut pending) ───────────────
        $payment = Payment::create([
            'user_id'         => $user->id,
            'trip_id'         => $booking->trip_id,
            'driver_id'       => $booking->trip->driver_id,
            'booking_id'      => $booking->id,
            'amount'          => $booking->amount,
            'commission'      => $booking->amount * 0.10,
            'driver_net'      => $booking->amount * 0.90,
            'method'          => 'mobile_money',
            'provider'        => 'flutterwave',
            'status'          => 'pending',
            'transaction_ref' => $transactionRef,
            'country'         => 'CG',
            'city'            => $booking->trip->departure_city ?? 'N/A',
            'paid_at'         => null,
        ]);

        // ── Appel Flutterwave ────────────────────────────────────────
        $result = $this->flutterwave->collect([
            'phone'       => $request->phone,
            'amount'      => (int) $booking->amount,
            'operator'    => $operator,
            'reference'   => $transactionRef,
            'description' => 'ToptopGo – Réservation #' . $booking->id,
            'user_id'     => $user->id,
            'email'       => $user->email ?? "user_{$user->id}@toptopgo.cg",
            'first_name'  => $user->first_name ?? null,
            'last_name'   => $user->last_name  ?? null,
        ]);

        if (!$result['success']) {
            Log::error('UserPaymentController::mobileMoney FLW error', [
                'booking_id' => $booking->id,
                'error'      => $result['error'] ?? 'unknown',
            ]);

            $payment->update(['status' => 'failed']);

            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Erreur lors de l\'initiation du paiement Mobile Money.',
            ], 400);
        }

        // ── Sauvegarder l'ID de charge Flutterwave ───────────────────
        $payment->update([
            'flw_charge_id' => $result['data']['charge_id'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paiement initié. Veuillez autoriser la transaction sur votre téléphone.',
            'data'    => [
                'transaction_ref' => $transactionRef,
                'amount'          => $booking->amount,
                'status'          => 'pending',
                'instruction'     => $result['instruction'] ?? 'Autorisez le paiement sur votre téléphone Mobile Money.',
                'charge_id'       => $result['data']['charge_id'] ?? null,
                'chat_enabled'    => false,
                'chat_channel'    => 'chat.trip.' . $booking->trip_id,
            ],
        ]);
    }

    // ── Paiement Stripe ──────────────────────────────────────────────
    public function stripe(Request $request)
    {
        $request->validate([
            'booking_id'        => 'required|exists:bookings,id',
            'payment_method_id' => 'required|string',
        ]);

        $booking = Booking::with('trip')
            ->where('user_id', Auth::id())
            ->findOrFail($request->booking_id);

        if ($booking->status !== 'accepted') {
            return response()->json([
                'success' => false,
                'message' => 'La réservation doit être acceptée avant le paiement.',
            ], 422);
        }

        $payment = Payment::create([
            'user_id'         => Auth::id(),
            'trip_id'         => $booking->trip_id,
            'driver_id'       => $booking->trip->driver_id,
            'booking_id'      => $booking->id,
            'amount'          => $booking->amount,
            'commission'      => $booking->amount * 0.10,
            'driver_net'      => $booking->amount * 0.90,
            'method'          => 'stripe',
            'status'          => 'pending',
            'transaction_ref' => 'STR-' . strtoupper(Str::random(10)),
            'country'         => 'CG',
            'city'            => $booking->trip->departure_city ?? 'N/A',
            'paid_at'         => null,
        ]);

        // TODO: intégrer Stripe réel ici
        $payment->update(['status' => 'success', 'paid_at' => now()]);
        $booking->update(['status' => 'paid']);

        PaymentValidated::dispatch($booking->load('trip'));

        return response()->json([
            'success' => true,
            'message' => 'Paiement Stripe effectué. Le chat avec le chauffeur est maintenant disponible.',
            'data'    => [
                'payment'         => $payment,
                'transaction_ref' => $payment->transaction_ref,
                'amount'          => $payment->amount,
                'status'          => $payment->status,
                'chat_enabled'    => true,
                'chat_channel'    => 'chat.trip.' . $booking->trip_id,
            ],
        ]);
    }

    // ── Statut d'un paiement (polling) ───────────────────────────────
    public function status(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        $payment = Payment::where('booking_id', $request->booking_id)
            ->where('user_id', Auth::id())
            ->latest()
            ->firstOrFail();

        // Si toujours en attente et qu'on a un charge_id FLW, on re-vérifie
        if ($payment->status === 'pending' && !empty($payment->flw_charge_id)) {
            $check = $this->flutterwave->getTransactionStatus($payment->flw_charge_id);

            if ($check['success'] && isset($check['status'])) {
                $newStatus = $check['status']; // 'completed' | 'failed' | 'processing'

                if ($newStatus === 'completed') {
                    $payment->update(['status' => 'success', 'paid_at' => now()]);
                    $payment->refresh();

                    // Déverrouiller le chat
                    $booking = Booking::find($payment->booking_id);
                    if ($booking && $booking->status !== 'paid') {
                        $booking->update(['status' => 'paid']);
                        PaymentValidated::dispatch($booking->load('trip'));
                    }
                } elseif ($newStatus === 'failed') {
                    $payment->update(['status' => 'failed']);
                    $payment->refresh();
                }
            }
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'transaction_ref' => $payment->transaction_ref,
                'amount'          => $payment->amount,
                'method'          => $payment->method,
                'status'          => $payment->status,
                'paid_at'         => $payment->paid_at,
                'chat_enabled'    => $payment->status === 'success',
                'chat_channel'    => 'chat.trip.' . $payment->trip_id,
            ],
        ]);
    }
}
