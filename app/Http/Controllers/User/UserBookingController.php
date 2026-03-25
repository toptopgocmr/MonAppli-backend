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

        // Calcul montant
        $pricePerSeat = (float) ($trip->price_per_seat ?? 0);
        $totalAmount  = (float) ($request->total_price ?? $pricePerSeat * $seats);

        // ✅ Créer la réservation
        $booking = Booking::create([
            'user_id'    => Auth::id(),
            'trip_id'    => $request->trip_id,
            'seats'      => $seats,
            'passengers' => $seats,
            'amount'     => $totalAmount,
            'extra_bags' => $request->extra_bags ?? 0,
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
    public function cancel($id)
    {
        $booking = Booking::where('user_id', Auth::id())->findOrFail($id);

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cette réservation ne peut plus être annulée.',
            ], 422);
        }

        $booking->update(['status' => 'cancelled']);

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
        $booking->update(['status' => 'rejected']);
        Trip::where('id', $booking->trip_id)
            ->increment('available_seats', (int) ($booking->seats ?? $booking->passengers ?? 1));
        return response()->json(['success' => true]);
    }
}
