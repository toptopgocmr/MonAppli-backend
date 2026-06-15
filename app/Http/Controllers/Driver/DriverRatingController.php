<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Rating;
use App\Models\Driver\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverRatingController extends Controller
{
    // ── Noter un passager après un trajet terminé ─────────────────────────
    public function store(Request $request, $bookingId)
    {
        $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $booking = Booking::whereHas('trip', fn($q) => $q->where('driver_id', Auth::id()))
            ->where('status', 'completed')
            ->find($bookingId);

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Réservation introuvable ou non terminée.'], 404);
        }

        $rating = Rating::firstOrCreate(
            ['booking_id' => $booking->id],
            ['driver_id' => Auth::id(), 'user_id' => $booking->user_id]
        );

        $rating->update([
            'user_rating'   => $request->rating,
            'user_comment'  => $request->comment ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => '⭐ Passager noté avec succès.',
            'data'    => $rating,
        ]);
    }

    // ── Notes reçues par le chauffeur (de ses passagers) ─────────────────
    public function received()
    {
        $ratings = Rating::where('driver_id', Auth::id())
            ->whereNotNull('driver_rating')
            ->with('user', 'booking.trip')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($r) => [
                'id'         => $r->id,
                'rating'     => $r->driver_rating,
                'comment'    => $r->driver_comment,
                'created_at' => $r->created_at,
                'passenger'  => $r->user ? [
                    'name'  => trim(($r->user->first_name ?? '') . ' ' . ($r->user->last_name ?? '')),
                    'photo' => $r->user->profile_photo ?? null,
                ] : null,
                'trip' => $r->booking?->trip ? [
                    'departure'   => $r->booking->trip->departure   ?? '',
                    'destination' => $r->booking->trip->destination ?? '',
                    'date'        => $r->booking->trip->departure_date
                        ? \Carbon\Carbon::parse($r->booking->trip->departure_date)->format('d/m/Y')
                        : null,
                ] : null,
            ]);

        // Calculer la moyenne dynamiquement depuis les enregistrements réels
        $allDriverRatings = Rating::where('driver_id', Auth::id())
            ->whereNotNull('driver_rating')
            ->pluck('driver_rating');

        $count = $allDriverRatings->count();
        $avg   = $count > 0 ? round($allDriverRatings->average(), 2) : 0;

        // Mettre à jour le cache sur le Driver si différent
        $driver = Driver::find(Auth::id());
        if ($driver && ((float)$driver->rating_avg !== (float)$avg || (int)$driver->rating_count !== $count)) {
            $driver->update(['rating_avg' => $avg, 'rating_count' => $count]);
        }

        return response()->json([
            'success'      => true,
            'rating_avg'   => $avg,
            'rating_count' => $count,
            'data'         => $ratings,
        ]);
    }

    // ── Réservations terminées en attente de notation ─────────────────────
    public function pending()
    {
        $rated = Rating::where('driver_id', Auth::id())
            ->whereNotNull('user_rating')
            ->pluck('booking_id');

        $bookings = Booking::whereHas('trip', fn($q) => $q->where('driver_id', Auth::id()))
            ->where('status', 'completed')
            ->whereNotIn('id', $rated)
            ->with(['user', 'trip'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn($b) => [
                'booking_id' => $b->id,
                'trip_id'    => $b->trip_id,
                'passenger'  => $b->user ? [
                    'id'    => $b->user->id,
                    'name'  => trim(($b->user->first_name ?? '') . ' ' . ($b->user->last_name ?? '')),
                    'photo' => $b->user->profile_photo ?? null,
                    'initials' => strtoupper(
                        substr($b->user->first_name ?? 'P', 0, 1) .
                        substr($b->user->last_name  ?? '', 0, 1)
                    ),
                ] : null,
                'trip' => $b->trip ? [
                    'departure'   => $b->trip->departure   ?? '',
                    'destination' => $b->trip->destination ?? '',
                    'date'        => $b->trip->departure_date
                        ? \Carbon\Carbon::parse($b->trip->departure_date)->format('d/m/Y')
                        : null,
                ] : null,
            ]);

        return response()->json(['success' => true, 'data' => $bookings]);
    }
}
