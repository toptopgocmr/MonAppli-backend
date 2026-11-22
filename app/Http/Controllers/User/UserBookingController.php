<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserBookingController extends Controller
{
    // ── Liste des réservations du client ─────────────────────────────
    public function index()
    {
        $bookings = Booking::with(['trip.driver'])
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $bookings,
            'total'   => $bookings->count(),
        ]);
    }

    // ── Détail ───────────────────────────────────────────────────────
    public function show($id)
    {
        $booking = Booking::with(['trip.driver'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $booking]);
    }

    /**
     * ✅ FIX 1 : seats + amount sauvegardés + available_seats décrémenté
     * ✅ FIX 2 : suppression du blocage "déjà réservé" pour permettre
     *            de réserver plusieurs places pour d'autres passagers
     */
    public function store(Request $request)
    {
        $request->validate([
            'trip_id'      => 'required|exists:trips,id',
            'seats_booked' => 'nullable|integer|min:1|max:20',
            'extra_bags'   => 'nullable|integer|min:0',
            'total_price'  => 'nullable|numeric|min:0',
        ]);

        $seats = max(1, (int) ($request->seats_booked ?? 1));
        $trip  = Trip::findOrFail($request->trip_id);

        // ✅ Vérifier places disponibles
        if ($trip->available_seats < $seats) {
            return response()->json([
                'success' => false,
                'message' => "Seulement {$trip->available_seats} place(s) disponible(s) sur ce trajet.",
            ], 422);
        }

        // ── Calcul du prix CÔTÉ SERVEUR (le montant envoyé par l'app n'est
        // plus utilisé : une app modifiée pouvait sinon fixer son prix) ──
        $pricePerSeat = (float) ($trip->price_per_seat ?? 0);
        $extraBags    = max(0, (int) ($request->extra_bags ?? 0));
        $maxBags      = (int) ($trip->extra_luggage_slots ?? 0);
        if ($extraBags > $maxBags) {
            return response()->json([
                'success' => false,
                'message' => $maxBags > 0
                    ? "Maximum {$maxBags} bagage(s) supplémentaire(s) sur ce trajet."
                    : "Ce trajet n'accepte pas de bagage supplémentaire.",
            ], 422);
        }
        // Même formule que l'app : prix au kg × poids de bagage autorisé.
        $kg           = (float) ($trip->luggage_weight_kg ?? 20);
        $extraBagUnit = round(($kg > 0 ? $kg : 20) * (float) ($trip->extra_luggage_fee ?? 0));
        $totalAmount  = round($pricePerSeat * $seats + $extraBags * $extraBagUnit, 2);

        if ($request->filled('total_price') && abs((float) $request->total_price - $totalAmount) >= 1) {
            \Illuminate\Support\Facades\Log::warning('Réservation : prix envoyé par l\'app différent du prix serveur', [
                'trip_id' => $trip->id, 'app' => (float) $request->total_price, 'serveur' => $totalAmount,
            ]);
        }

        // Frais de service TopTopGo (payés par le client en plus de la course).
        $serviceFee = \App\Services\Payment\ServiceFee::for($totalAmount);

        // ✅ Créer la réservation
        $booking = Booking::create([
            'user_id'    => Auth::id(),
            'trip_id'    => $request->trip_id,
            'seats'      => $seats,
            'passengers' => $seats,
            'amount'      => $totalAmount,
            'service_fee' => $serviceFee,
            'status'     => 'pending',
            'booked_at'  => now(),
        ]);

        // ✅ Décrémenter les places disponibles
        $trip->decrement('available_seats', $seats);

        return response()->json([
            'success' => true,
            'message' => 'Réservation effectuée avec succès.',
            'booking' => $booking->load('trip.driver'),
            'data'    => $booking->load('trip.driver'),
        ], 201);
    }

    // ── Annuler ──────────────────────────────────────────────────────
    public function cancel(Request $request, $id)
    {
        $booking = Booking::where('user_id', Auth::id())->findOrFail($id);

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cette réservation ne peut plus être annulée.',
            ], 422);
        }

        $booking->update([
            'status'              => 'cancelled',
            'cancellation_reason' => $request->input('reason', 'Annulée par le client'),
            'cancelled_at'        => now(),
        ]);

        // ✅ Remettre les places
        Trip::where('id', $booking->trip_id)
            ->increment('available_seats', (int) ($booking->seats ?? $booking->passengers ?? 1));

        return response()->json([
            'success' => true,
            'message' => 'Réservation annulée avec succès.',
        ]);
    }

    public function accept($id)
    {
        $booking = Booking::where('user_id', Auth::id())->findOrFail($id);
        $booking->update(['status' => 'confirmed']);
        return response()->json(['success' => true, 'data' => $booking]);
    }

    public function reject($id)
    {
        $booking = Booking::where('user_id', Auth::id())->findOrFail($id);
        $booking->update([
            'status'              => 'rejected',
            'cancellation_reason' => 'Refusée par le client',
            'cancelled_at'        => now(),
        ]);
        Trip::where('id', $booking->trip_id)
            ->increment('available_seats', (int) ($booking->seats ?? $booking->passengers ?? 1));
        return response()->json(['success' => true]);
    }
}
