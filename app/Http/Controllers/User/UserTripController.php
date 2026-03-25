<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\Request;

class UserTripController extends Controller
{
    /**
     * GET /api/user/trips
     * Paramètres acceptés :
     *   departure   : ville départ (aussi pickup=)
     *   destination : ville arrivée (aussi dropoff=)
     *   date        : YYYY-MM-DD (optionnel, défaut = aujourd'hui et futur)
     *   time        : HH:mm (optionnel, filtre ±1h)
     *   passengers  : int (défaut 1)
     */
    public function index(Request $request)
    {
        // ✅ FIX : on charge tout le driver sans spécifier les colonnes
        // pour éviter "Unknown column 'rating'" si la colonne n'existe pas
        $query = Trip::with(['driver'])
            ->whereIn('status', ['pending', 'accepted'])
            ->where('available_seats', '>=', 1);

        // ── Filtre départ ──────────────────────────────────────────────
        $dep = $request->departure ?? $request->pickup ?? null;
        if ($dep) {
            $query->where(function ($q) use ($dep) {
                $q->where('pickup_address',   'like', "%$dep%")
                  ->orWhere('departure',      'like', "%$dep%")
                  ->orWhere('departure_city', 'like', "%$dep%");
            });
        }

        // ── Filtre destination ─────────────────────────────────────────
        $dest = $request->destination ?? $request->dropoff ?? null;
        if ($dest) {
            $query->where(function ($q) use ($dest) {
                $q->where('dropoff_address',   'like', "%$dest%")
                  ->orWhere('destination',     'like', "%$dest%")
                  ->orWhere('destination_city','like', "%$dest%");
            });
        }

        // ── Filtre date ────────────────────────────────────────────────
        if ($request->filled('date')) {
            $query->whereDate('departure_date', $request->date);
        } else {
            $query->where(function ($q) {
                $q->whereNull('departure_date')
                  ->orWhereDate('departure_date', '>=', now()->toDateString());
            });
        }

        // ── Filtre heure ±1h ───────────────────────────────────────────
        if ($request->filled('time')) {
            try {
                $t    = \Carbon\Carbon::createFromFormat('H:i', $request->time);
                $from = $t->copy()->subHour()->format('H:i:s');
                $to   = $t->copy()->addHour()->format('H:i:s');
                $query->where(function ($q) use ($from, $to) {
                    $q->whereNull('departure_time')
                      ->orWhereBetween('departure_time', [$from, $to]);
                });
            } catch (\Exception $e) {}
        }

        // ── Filtre passagers ───────────────────────────────────────────
        $passengers = max(1, (int) $request->get('passengers', 1));
        $query->where('available_seats', '>=', $passengers);

        // ── Tri ────────────────────────────────────────────────────────
        $trips = $query
            ->orderByRaw("ISNULL(departure_date), departure_date ASC")
            ->orderBy('departure_time', 'asc')
            ->get()
            ->map(fn($trip) => $this->fmt($trip));

        return response()->json([
            'success' => true,
            'count'   => $trips->count(),
            'data'    => $trips,
        ]);
    }

    /**
     * GET /api/user/trips/{id}
     */
    public function show($id)
    {
        $trip = Trip::with(['driver'])->findOrFail($id);
        return response()->json([
            'success' => true,
            'data'    => $this->fmt($trip),
        ]);
    }

    // ── Formatage ──────────────────────────────────────────────────────────
    private function fmt(Trip $trip): array
    {
        $price = (float) ($trip->price_per_seat ?? $trip->amount ?? 0);
        $time  = $trip->departure_time ?? null;
        if ($time && strlen($time) > 5) $time = substr($time, 0, 5);

        $date = null;
        if ($trip->departure_date) {
            try {
                $date = \Carbon\Carbon::parse($trip->departure_date)->format('Y-m-d');
            } catch (\Exception $e) {
                $date = $trip->departure_date;
            }
        }

        $driver = $trip->driver;
        $photo  = null;
        if ($driver?->profile_photo) {
            $p = $driver->profile_photo;
            // getProfilePhotoAttribute() du modèle Driver gère Backblaze
            $photo = str_starts_with($p, 'http') ? $p : asset('storage/' . $p);
        }

        // ✅ rating optionnel — null si colonne absente en DB
        $rating      = null;
        $ratingCount = null;
        try { $rating      = $driver ? $driver->rating       : null; } catch (\Exception $e) {}
        try { $ratingCount = $driver ? $driver->rating_count : null; } catch (\Exception $e) {}

        return [
            'id'                  => $trip->id,
            'pickup_address'      => $trip->pickup_address   ?? $trip->departure       ?? '',
            'dropoff_address'     => $trip->dropoff_address  ?? $trip->destination     ?? '',
            'departure'           => $trip->departure        ?? $trip->pickup_address  ?? '',
            'destination'         => $trip->destination      ?? $trip->dropoff_address ?? '',
            'pickup_point'        => $trip->pickup_point     ?? null,
            'dropoff_point'       => $trip->dropoff_point    ?? null,
            'departure_date'      => $date,
            'departure_time'      => $time,
            'price_per_seat'      => $price,
            'amount'              => $price,
            'available_seats'     => (int)   ($trip->available_seats     ?? 0),
            'luggage_included'    => (int)   ($trip->luggage_included    ?? 1),
            'luggage_weight_kg'   => (float) ($trip->luggage_weight_kg   ?? 20),
            'extra_luggage_fee'   => (float) ($trip->extra_luggage_fee   ?? 0),
            'extra_luggage_slots' => (int)   ($trip->extra_luggage_slots ?? 0),
            'vehicle_type'        => $trip->vehicle_type ?? null,
            'distance_km'         => $trip->distance_km  ?? null,
            'status'              => $trip->status,
            'driver'              => $driver ? [
                'id'           => $driver->id,
                'name'         => trim(($driver->first_name ?? '') . ' ' . ($driver->last_name ?? '')),
                'first_name'   => $driver->first_name  ?? '',
                'last_name'    => $driver->last_name   ?? '',
                'phone'        => $driver->phone        ?? '',
                'rating'       => $rating,
                'rating_count' => $ratingCount,
                'photo'        => $photo,
            ] : null,
        ];
    }
}
