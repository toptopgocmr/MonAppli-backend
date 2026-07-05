<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Events\PaymentValidated;
use App\Services\Payment\FlutterwaveService;
use App\Services\Payment\PeexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserPaymentController extends Controller
{
    public function __construct(
        protected FlutterwaveService $flutterwave,
        protected PeexService        $peex,
    ) {}

    // ── Paiement Mobile Money (Peex si le pays est couvert, sinon Flutterwave) ──
    public function mobileMoney(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'phone'      => 'required|string',
        ]);

        // L'app mobile envoie soit 'network' + 'country' (écran multi-pays),
        // soit l'ancien schéma 'provider' (mtn|airtel) + 'country_code'.
        // On accepte les deux pour ne rien casser.
        $network = $request->input('network') ?? $request->input('provider');
        $countryCode = strtoupper($request->input('country') ?? $request->input('country_code', config('flutterwave.country_code', 'CM')));

        if (!$network) {
            return response()->json([
                'success' => false,
                'message' => 'Moyen de paiement (network/provider) manquant.',
            ], 422);
        }

        [$method, $operator] = $this->mapNetwork($network);

        $booking = Booking::with('trip')
            ->where('user_id', Auth::id())
            ->findOrFail($request->booking_id);

        // ✅ Le client peut payer dès que la réservation est en pending ou confirmed
        if (!in_array($booking->status, ['pending', 'confirmed', 'accepted'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cette réservation ne peut pas être payée (statut: ' . $booking->status . ').',
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
        $user = Auth::user();

        // ── Choix de la passerelle ────────────────────────────────────
        // Peex ne couvre aujourd'hui que les pays confirmés par sa doc
        // officielle (Cameroun pour le moment). Ailleurs on garde Flutterwave.
        $usePeex = $this->peex->supportsCollectFor($countryCode);
        $gateway = $usePeex ? 'peex' : 'flutterwave';

        // ── Créer le paiement en base (statut pending) ───────────────
        $payment = Payment::create([
            'user_id'         => $user->id,
            'trip_id'         => $booking->trip_id,
            'driver_id'       => $booking->trip->driver_id,
            'booking_id'      => $booking->id,
            'amount'          => $booking->amount,
            'commission'      => $booking->amount * 0.10,
            'driver_net'      => $booking->amount * 0.90,
            'method'          => $method,
            'provider'        => $gateway,
            'status'          => 'pending',
            'transaction_ref' => $transactionRef,
            'country'         => $countryCode,
            'city'            => $booking->trip->departure_city ?? 'N/A',
            'paid_at'         => null,
        ]);

        if ($usePeex) {
            $result = $this->peex->collect([
                'track_id'      => $transactionRef,
                'phone'         => $request->phone,
                'amount'        => (int) $booking->amount,
                'currency'      => 'XAF',
                'customer_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'Client TopTopGo',
                'country'       => $countryCode,
                'description'   => 'TopTopGo - Réservation #' . $booking->id,
            ]);
        } else {
            $result = $this->flutterwave->collect([
                'phone'        => $request->phone,
                'country_code' => $countryCode,
                'amount'       => (int) $booking->amount,
                'operator'     => $operator,
                'reference'    => $transactionRef,
                'description'  => 'ToptopGo – Réservation #' . $booking->id,
                'user_id'      => $user->id,
                'email'        => $user->email ?? "user_{$user->id}@toptopgo.app",
                'first_name'   => $user->first_name ?? null,
                'last_name'    => $user->last_name  ?? null,
            ]);
        }

        if (!$result['success']) {
            Log::error('UserPaymentController::mobileMoney error', [
                'gateway'    => $gateway,
                'booking_id' => $booking->id,
                'error'      => $result['error'] ?? 'unknown',
            ]);

            $payment->update(['status' => 'failed']);

            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Erreur lors de l\'initiation du paiement Mobile Money.',
            ], 400);
        }

        // ── Sauvegarder l'identifiant de transaction fournisseur ─────
        if ($usePeex) {
            $payment->update([
                'provider_transaction_id' => $result['transaction_id'] ?? null,
            ]);
        } else {
            $payment->update([
                'flw_charge_id' => $result['data']['charge_id'] ?? null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Paiement initié. Veuillez autoriser la transaction sur votre téléphone.',
            'data'    => [
                'transaction_ref' => $transactionRef,
                'amount'          => $booking->amount,
                'status'          => 'pending',
                'instruction'     => $result['instruction'] ?? 'Autorisez le paiement sur votre téléphone Mobile Money.',
                'gateway'         => $gateway,
                'charge_id'       => $result['data']['charge_id'] ?? null,
                'chat_enabled'    => false,
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

        // Si toujours en attente, on re-vérifie auprès du bon fournisseur
        if ($payment->status === 'pending') {
            $check = $this->pollProviderStatus($payment);

            if ($check && ($check['success'] ?? false) && isset($check['status'])) {
                $newStatus = $check['status'];

                if (in_array($newStatus, ['success', 'completed'], true)) {
                    $receiptNumber = 'RCP-' . strtoupper(Str::random(8)) . '-' . now()->format('YmdHis');
                    $payment->update([
                        'status'         => 'success',
                        'paid_at'        => now(),
                        'receipt_number' => $receiptNumber,
                    ]);
                    $payment->refresh();

                    $booking = Booking::find($payment->booking_id);
                    if ($booking && $booking->status !== 'paid') {
                        $booking->update(['status' => 'paid']);
                        PaymentValidated::dispatch($booking->load('trip'));
                    }
                } elseif (in_array($newStatus, ['failed', 'cancelled'], true)) {
                    $payment->update(['status' => $newStatus]);
                    $payment->refresh();
                }
            }
        }

        $isPaid   = $payment->status === 'success';
        $receipt  = null;

        if ($isPaid) {
            $booking = Booking::with(['trip.driver'])->find($payment->booking_id);
            $receipt = [
                'receipt_number'  => $payment->receipt_number,
                'transaction_ref' => $payment->transaction_ref,
                'amount'          => $payment->amount,
                'method'          => $payment->method,
                'paid_at'         => $payment->paid_at,
                'booking_id'      => $payment->booking_id,
                'trip'            => $booking?->trip ? [
                    'departure'      => $booking->trip->departure      ?? $booking->trip->pickup_address,
                    'destination'    => $booking->trip->destination    ?? $booking->trip->dropoff_address,
                    'departure_date' => $booking->trip->departure_date
                        ? \Carbon\Carbon::parse($booking->trip->departure_date)->format('Y-m-d') : null,
                    'departure_time' => $booking->trip->departure_time
                        ? substr($booking->trip->departure_time, 0, 5) : null,
                    'driver_name'    => $booking->trip->driver
                        ? trim(($booking->trip->driver->first_name ?? '') . ' ' . ($booking->trip->driver->last_name ?? ''))
                        : null,
                ] : null,
                'seats'           => $booking?->seats ?? $booking?->passengers ?? 1,
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'transaction_ref' => $payment->transaction_ref,
                'amount'          => $payment->amount,
                'method'          => $payment->method,
                'status'          => $payment->status,
                'paid_at'         => $payment->paid_at,
                'chat_enabled'    => $isPaid,
                'chat_channel'    => 'chat.trip.' . $payment->trip_id,
                'receipt'         => $receipt,
            ],
        ]);
    }

    /**
     * Poll the right provider depending on how the payment was initiated.
     */
    protected function pollProviderStatus(Payment $payment): ?array
    {
        if ($payment->provider === 'peex') {
            return $this->peex->getTransactionStatus($payment->transaction_ref);
        }

        if (!empty($payment->flw_charge_id)) {
            return $this->flutterwave->getTransactionStatus($payment->flw_charge_id);
        }

        return null;
    }

    /**
     * Map the network string sent by the app (or the legacy 'provider' field)
     * to [enum method value stored in DB, operator code used by gateways].
     *
     * `payments.method` is a strict DB enum:
     * ['mtn','orange','airtel','moov','visa','mastercard'] — an unrecognized
     * value would fail the insert, so we always normalize to a safe fallback.
     */
    protected function mapNetwork(string $network): array
    {
        $normalized = strtoupper(trim($network));

        return match (true) {
            str_contains($normalized, 'MTN')    => ['mtn', 'MTN'],
            str_contains($normalized, 'ORANGE') => ['orange', 'ORANGE'],
            str_contains($normalized, 'AIRTEL') => ['airtel', 'AIRTEL'],
            str_contains($normalized, 'MOOV') || str_contains($normalized, 'TMONEY') => ['moov', 'MOOV'],
            default => ['mtn', 'MTN'],
        };
    }
}
