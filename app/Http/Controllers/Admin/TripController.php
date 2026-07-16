<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Company;
use App\Models\Trip;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function index(Request $request)
    {
        // ✅ FIX : une réservation ne doit apparaître dans le détail d'un
        // trajet (admin) que si le paiement client a été confirmé —
        // Booking::scopePaid(). Avant ce fix, toutes les réservations
        // (y compris 'pending', jamais payées) étaient chargées.
        $query = Trip::with(['driver', 'vehicle', 'bookings' => fn ($q) => $q->paid()])->latest();

        // ✅ Recherche départ séparé
        if ($request->filled('departure')) {
            $dep = $request->departure;
            $query->where(function ($q) use ($dep) {
                $q->where('pickup_address',   'like', "%$dep%")
                  ->orWhere('departure',      'like', "%$dep%")
                  ->orWhere('departure_city', 'like', "%$dep%");
            });
        }

        // ✅ Recherche destination séparée
        if ($request->filled('destination')) {
            $dest = $request->destination;
            $query->where(function ($q) use ($dest) {
                $q->where('dropoff_address',   'like', "%$dest%")
                  ->orWhere('destination',     'like', "%$dest%")
                  ->orWhere('destination_city','like', "%$dest%");
            });
        }

        // Ancien ?search= rétrocompatibilité
        if ($request->filled('search') && !$request->filled('departure') && !$request->filled('destination')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('pickup_address',   'like', "%$s%")
                  ->orWhere('dropoff_address', 'like', "%$s%")
                  ->orWhere('departure',       'like', "%$s%")
                  ->orWhere('destination',     'like', "%$s%");
            });
        }

        // Filtre statut
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filtre dates
        if ($request->filled('from')) {
            $query->whereDate('departure_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('departure_date', '<=', $request->to);
        }

        // ✅ Filtre chauffeur (nom ou téléphone)
        if ($request->filled('driver')) {
            $d = $request->driver;
            $query->whereHas('driver', function ($dq) use ($d) {
                $dq->where('first_name', 'like', "%$d%")
                   ->orWhere('last_name', 'like', "%$d%")
                   ->orWhere('phone', 'like', "%$d%");
            });
        }

        // ✅ Filtre client (nom ou téléphone) — soit le client à l'origine du
        // trajet (trip.user_id), soit un passager ayant réservé une place
        // (bookings.user) dans le cas d'un trajet covoiturage.
        if ($request->filled('client')) {
            $c = $request->client;
            $query->where(function ($q) use ($c) {
                $q->whereHas('user', function ($uq) use ($c) {
                    $uq->where('first_name', 'like', "%$c%")
                       ->orWhere('last_name', 'like', "%$c%")
                       ->orWhere('phone', 'like', "%$c%");
                })->orWhereHas('bookings.user', function ($uq) use ($c) {
                    $uq->where('first_name', 'like', "%$c%")
                       ->orWhere('last_name', 'like', "%$c%")
                       ->orWhere('phone', 'like', "%$c%");
                });
            });
        }

        // ✅ Filtre société — soit le trajet est directement rattaché à la
        // société (trips.company_id, réservation sur itinéraire société),
        // soit c'est le c