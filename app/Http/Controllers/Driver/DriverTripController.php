<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverTripController extends Controller
{
    // ── Liste des trajets du chauffeur ────────────────────────────────────
    public function index()
    {
        $trips = Trip::where('driver_id', Auth::id())
            ->withCount('bookings')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $trips->map(fn($t) => $this->fmt($t)),
        ]);
    }

    // ── Créer un trajet ───────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'departure'           => 'required|string|max:255',
            'destination'         => 'required|string|max:255',
            'price_per_seat'      => 'required|numeric|min:0',
            'available_seats'     => 'required|integer|min:1|max:50',
            'departure_date'      => 'required|date',
            'departure_time'      => 'required|string',
            'luggage_included'    => 'nullable|integer|min:0',
            'luggage_weight_kg'   => 'nullable|numeric|min:0',
            'extra_luggage_fee'   => 'nullable|numeric|min:0',
            'extra_luggage_slots' => 'nullable|integer|min:0',
            'vehicle_type'        => 'nullable|string|max:100',
        ]);

        $price = (float) ($request->price_per_seat ?? 0);
        $seats = (int)   ($request->available_seats ?? 3);
        $time  = substr($request->departure_time ?? '08:00', 0, 8);
        if (strlen($time) === 5) $time .= ':00';

        $trip = Trip::create([
            'driver_id'           => Auth::id(),
            'departure'           => $request->departure,
            'pickup_address'      => $request->departure,
            'destination'         => $request->destination,
            'dropoff_address'     => $request->destination,
            'departure_city'      => $request->departure,
            'destination_city'    => $request->destination,
            'pickup_point'        => $request->pickup_point  ?? null,
            'dropoff_point'       => $request->dropoff_point ?? null,
            'price_per_seat'      => $price,
            'amount'              => $price * $seats,
            'available_seats'     => $seats,
            'departure_date'      => $request->departure_date,
            'departure_time'      => $time,
            'luggage_included'    => (int)   ($request->luggage_included    ?? 1),
            'luggage_weight_kg'   => (float) ($request->luggage_weight_kg   ?? 20),
            'extra_luggage_fee'   => (float) ($request->extra_luggage_fee   ?? 0),
            'extra_luggage_slots' => (int)   ($request->extra_luggage_slots ?? 0),
            'vehicle_type'        => substr($request->vehicle_type ?? '', 0, 100),
            'status'              => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trajet publié avec succès.',
            'data'    => $this->fmt($trip),
        ], 201);
    }

    // ── Détail d'un trajet ────────────────────────────────────────────────
    public function show($id)
    {
        $trip = Trip::where('driver_id', Auth::id())
            ->withCount('bookings')->find($id);
        if (!$trip) return response()->json(['success' => false, 'message' => 'Introuvable'], 404);
        return response()->json(['success' => true, 'data' => $this->fmt($trip)]);
    }

    // ── Modifier un trajet ────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $trip = Trip::where('driver_id', Auth::id())->find($id);
        if (!$trip) return response()->json(['success' => false, 'message' => 'Introuvable'], 404);
        if (in_array($trip->status, ['in_progress', 'completed'])) {
            return response()->json(['success' => false, 'message' => 'Impossible de modifier ce trajet.'], 422);
        }
        $trip->update($request->only([
            'departure', 'destination', 'pickup_point', 'dropoff_point',
            'price_per_seat', 'amount', 'available_seats',
            'departure_date', 'departure_time',
            'luggage_included', 'luggage_weight_kg',
            'extra_luggage_fee', 'extra_luggage_slots', 'vehicle_type',
        ]));
        return response()->json(['success' => true, 'data' => $this->fmt($trip->fresh())]);
    }

    // ── Supprimer un trajet ───────────────────────────────────────────────
    public function destroy($id)
    {
        $trip = Trip::where('driver_id', Auth::id())->find($id);
        if (!$trip) return response()->json(['success' => false, 'message' => 'Introuvable'], 404);
        if ($trip->status === 'in_progress') {
            return response()->json(['success' => false, 'message' => 'Trajet en cours, impossible de supprimer.'], 422);
        }
        $trip->delete();
        return response()->json(['success' => true, 'message' => 'Trajet supprimé.']);
    }

    // ── Démarrer un trajet ────────────────────────────────────────────────
    public function start(Request $request, $id)
    {
        $trip = Trip::where('driver_id', Auth::id())->find($id);
        if (!$trip) return response()->json(['success' => false, 'message' => 'Introuvable'], 404);

        $confirmed = Booking::where('trip_id', $trip->id)
            ->whereIn('status', ['confirmed', 'paid'])->sum('seats');
        $total   = (int) ($trip->available_seats ?? 0) + (int) $confirmed;
        $allFull = $total > 0 && $confirmed >= $total;
        $force   = $request->boolean('force', false);

        if (!$allFull && !$force) {
            return response()->json([
                'success'         => false,
                'message'         => 'Toutes les places ne sont pas réservées.',
                'confirmed_seats' => $confirmed,
                'total_seats'     => $total,
                'can_force'       => true,
            ], 422);
        }

        $trip->update(['status' => 'in_progress', 'started_at' => now()]);
        return response()->json([
            'success' => true,
            'message' => 'Trajet démarré ! Bonne route 🚗',
            'data'    => $this->fmt($trip->fresh()),
        ]);
    }

    // ── Terminer un trajet ────────────────────────────────────────────────
    public function end($id)
    {
        $trip = Trip::where('driver_id', Auth::id())->find($id);
        if (!$trip) return response()->json(['success' => false, 'message' => 'Introuvable'], 404);
        $trip->update(['status' => 'completed', 'completed_at' => now()]);
        Booking::where('trip_id', $trip->id)
            ->whereIn('status', ['confirmed', 'paid'])
            ->update(['status' => 'completed']);
        return response()->json([
            'success' => true, 'message' => 'Trajet terminé 🏁',
            'data'    => $this->fmt($trip->fresh()),
        ]);
    }

    // ── Réservations du chauffeur ─────────────────────────────────────────
    public function bookings(Request $request)
    {
        $query = Booking::whereHas('trip', fn($q) => $q->where('driver_id', Auth::id()))
            ->with(['trip', 'user'])
            ->orderBy('created_at', 'desc');
        if ($request->trip_id) $query->where('trip_id', $request->trip_id);
        return response()->json([
            'success' => true,
            'data'    => $query->get()->map(fn($b) => $this->fmtBooking($b)),
        ]);
    }

    // ── Confirmer une réservation ─────────────────────────────────────────
    public function confirmBooking($id)
    {
        $booking = Booking::whereHas('trip', fn($q) => $q->where('driver_id', Auth::id()))->find($id);
        if (!$booking) return response()->json(['success' => false, 'message' => 'Introuvable'], 404);
        $booking->update(['status' => 'confirmed']);
        Trip::where('id', $booking->trip_id)
            ->decrement('available_seats', (int) ($booking->seats ?? $booking->passengers ?? 1));
        return response()->json([
            'success' => true,
            'message' => '✅ Réservation confirmée. Client notifié.',
            'data'    => $this->fmtBooking($booking->fresh()->load('trip', 'user')),
        ]);
    }

    // ── Refuser une réservation ───────────────────────────────────────────
    public function rejectBooking(Request $request, $id)
    {
        $booking = Booking::whereHas('trip', fn($q) => $q->where('driver_id', Auth::id()))->find($id);
        if (!$booking) return response()->json(['success' => false, 'message' => 'Introuvable'], 404);
        $booking->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->reason ?? '',
        ]);
        Trip::where('id', $booking->trip_id)
            ->increment('available_seats', (int) ($booking->seats ?? $booking->passengers ?? 1));
        return response()->json(['success' => true, 'message' => 'Réservation refusée.']);
    }

    // ── Formatage trajet ──────────────────────────────────────────────────
    private function fmt(Trip $t): array
    {
        $confirmed = Booking::where('trip_id', $t->id)
            ->whereIn('status', ['confirmed','paid'])->sum('seats');
        $total = (int) ($t->available_seats ?? 0) + (int) $confirmed;
        $price = (float) ($t->price_per_seat ?? $t->amount ?? 0);
        $time  = $t->departure_time ?? '';
        if (strlen($time) > 5) $time = substr($time, 0, 5);
        $date  = $t->departure_date
            ? ($t->departure_date instanceof \Carbon\Carbon
                ? $t->departure_date->format('Y-m-d')
                : \Carbon\Carbon::parse($t->departure_date)->format('Y-m-d'))
            : null;

        return [
            'id'                  => $t->id,
            'departure'           => $t->departure        ?? $t->pickup_address  ?? '',
            'pickup_address'      => $t->pickup_address   ?? $t->departure       ?? '',
            'pickup_point'        => $t->pickup_point     ?? null,
            'destination'         => $t->destination      ?? $t->dropoff_address ?? '',
            'dropoff_address'     => $t->dropoff_address  ?? $t->destination     ?? '',
            'dropoff_point'       => $t->dropoff_point    ?? null,
            'departure_date'      => $date,
            'departure_time'      => $time,
            'price_per_seat'      => $price,
            'amount'              => $price,
            'available_seats'     => (int)   ($t->available_seats     ?? 0),
            'confirmed_seats'     => (int)   $confirmed,
            'total_seats'         => (int)   $total,
            'luggage_included'    => (int)   ($t->luggage_included    ?? 1),
            'luggage_weight_kg'   => (float) ($t->luggage_weight_kg   ?? 20),
            'extra_luggage_fee'   => (float) ($t->extra_luggage_fee   ?? 0),
            'extra_luggage_slots' => (int)   ($t->extra_luggage_slots ?? 0),
            'vehicle_type'        => $t->vehicle_type ?? '',
            'status'              => $t->status ?? 'pending',
            'bookings_count'      => $t->bookings_count ?? 0,
            'created_at'          => $t->created_at,
        ];
    }

    // ── Formatage réservation ─────────────────────────────────────────────
    private function fmtBooking(Booking $b): array
    {
        $user  = $b->user;
        $photo = null;
        if ($user?->profile_photo) {
            $photo = str_starts_with($user->profile_photo, 'http')
                ? $user->profile_photo
                : asset('storage/' . $user->profile_photo);
        }

        return [
            'id'         => $b->id,
            'trip_id'    => $b->trip_id,
            'status'     => $b->status,
            'seats'      => (int)   ($b->seats ?? $b->passengers ?? 1),
            'amount'     => (float) ($b->amount ?? 0),
            'created_at' => $b->created_at,
            'client'     => $user ? [
                'id'            => $user->id,
                'name'          => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                'phone'         => $user->phone ?? '',
                'profile_photo' => $photo,
            ] : null,
            'trip' => $b->trip ? [
                'departure'           => $b->trip->departure      ?? '',
                'destination'         => $b->trip->destination    ?? '',
                'departure_date'      => $b->trip->departure_date
                    ? \Carbon\Carbon::parse($b->trip->departure_date)->format('Y-m-d')
                    : null,
                'departure_time'      => $b->trip->departure_time
                    ? substr($b->trip->departure_time, 0, 5) : null,
                'price_per_seat'      => (float) ($b->trip->price_per_seat ?? $b->trip->amount ?? 0),
                'pickup_point'        => $b->trip->pickup_point        ?? null,
                'luggage_weight_kg'   => (float) ($b->trip->luggage_weight_kg   ?? 20),
                'extra_luggage_fee'   => (float) ($b->trip->extra_luggage_fee   ?? 0),
                'extra_luggage_slots' => (int)   ($b->trip->extra_luggage_slots ?? 0),
            ] : null,
        ];
    }
}
