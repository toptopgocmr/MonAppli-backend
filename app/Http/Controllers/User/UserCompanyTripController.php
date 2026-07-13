<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CompanyItinerary;
use App\Models\Trip;
use Illuminate\Http\Request;

class UserCompanyTripController extends Controller
{
    /**
     * GET /api/user/company-trips
     * Liste les itinéraires publiés par les sociétés partenaires — trajets
     * réguliers (pas des courses ponctuelles) affichés côté "Courses Sociétés"
     * de l'app client. Le client contacte la société directement (pas de
     * réservation instantanée, ce sont des trajets programmés par la société).
     */
    public function index(Request $request)
    {
        $query = CompanyItinerary::where('is_active', true)->with('company');

        $dep = $request->departure ?? $request->pickup ?? null;
        if ($dep) {
            $query->where('departure', 'like', "%$dep%");
        }

        $dest = $request->destination ?? $request->dropoff ?? null;
        if ($dest) {
            $query->where('destination', 'like', "%$dest%");
        }

        $itineraries = $query->orderBy('departure_time')->get()->map(fn ($i) => $this->fmt($i));

        return response()->json([
            'success' => true,
            'count'   => $itineraries->count(),
            'data'    => $itineraries,
        ]);
    }

    /**
     * POST /api/user/company-trips/{id}/book
     *
     * ✅ Rend les "itinéraires programmés" réservables + payables en un clic,
     * exactement comme un trajet de covoiturage classique (choisir → payer).
     *
     * Un CompanyItinerary n'est qu'un horaire publié par la société (pas de
     * chauffeur, pas de date, pas de places précises). Réserver crée (ou
     * réutilise, si un autre client a déjà réservé sur cet itinéraire
     * aujourd'hui) un vrai Trip du jour SANS chauffeur assigné — la société
     * l'assigne ensuite depuis son dashboard (Company\ReservationController
     * ::assignDriver). On renvoie le Trip formaté exactement comme
     * UserTripController::fmt() pour que l'app cliente puisse enchaîner
     * directement sur son écran de réservation/paiement habituel
     * (TripDetailPage), sans écran ni logique supplémentaire.
     */
    public function book(Request $request, $id)
    {
        $itinerary = CompanyItinerary::where('is_active', true)->with('company')->findOrFail($id);

        $capacity = (int) ($itinerary->seats ?? 4);
        if ($capacity < 1) {
            return response()->json([
                'success' => false,
                'message' => "Cet itinéraire n'est pas disponible à la réservation pour le moment.",
            ], 422);
        }

        $today = now()->toDateString();

        // Réutiliser un trajet déjà créé aujourd'hui pour cet itinéraire tant
        // qu'il n'a pas encore de chauffeur assigné et qu'il reste des places
        // — plusieurs clients partagent alors le même trajet (comme pour un
        // trajet covoiturage classique publié par un chauffeur).
        $trip = Trip::where('company_itinerary_id', $itinerary->id)
            ->whereDate('departure_date', $today)
            ->whereNull('driver_id')
            ->where('available_seats', '>=', 1)
            ->first();

        if (!$trip) {
            $trip = Trip::create([
                'company_id'            => $itinerary->company_id,
                'company_itinerary_id'  => $itinerary->id,
                'driver_id'             => null,
                'user_id'               => null,
                'departure'             => $itinerary->departure,
                'pickup_address'        => $itinerary->departure_point ?? $itinerary->departure,
                'pickup_point'          => $itinerary->departure_point,
                'destination'           => $itinerary->destination,
                'dropoff_address'       => $itinerary->arrival_point ?? $itinerary->destination,
                'dropoff_point'         => $itinerary->arrival_point,
                'departure_date'        => $today,
                'departure_time'        => $itinerary->departure_time,
                'arrival_time'          => $itinerary->arrival_time,
                'price_per_seat'        => $itinerary->price,
                'amount'                => $itinerary->price,
                'available_seats'       => $capacity,
                'total_seats'           => $capacity,
                'vehicle_type'          => $itinerary->vehicle_type,
                'distance_km'           => $itinerary->distance_km,
                'status'                => 'pending',
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->fmtTrip($trip->fresh(), $itinerary),
        ]);
    }

    // Même forme que UserTripController::fmt(), avec un chauffeur "placeholder"
    // tant qu'aucun n'est réellement assigné (driver_id encore null).
    private function fmtTrip(Trip $trip, CompanyItinerary $itinerary): array
    {
        $time = $trip->departure_time ? substr($trip->departure_time, 0, 5) : null;
        $arrivalTime = $trip->arrival_time ? substr($trip->arrival_time, 0, 5) : null;

        $company = $itinerary->company;
        $companyLogo = $company?->logo
            ? (str_starts_with($company->logo, 'http') ? $company->logo : url('storage/' . $company->logo))
            : null;

        return [
            'id'                  => $trip->id,
            'pickup_address'      => $trip->pickup_address,
            'dropoff_address'     => $trip->dropoff_address,
            'departure'           => $trip->departure,
            'destination'         => $trip->destination,
            'pickup_point'        => $trip->pickup_point,
            'dropoff_point'       => $trip->dropoff_point,
            'departure_date'      => $trip->departure_date ? $trip->departure_date->format('Y-m-d') : null,
            'departure_time'      => $time,
            'arrival_time'        => $arrivalTime,
            'price_per_seat'      => (float) $trip->price_per_seat,
            'amount'              => (float) $trip->price_per_seat,
            'available_seats'     => (int) $trip->available_seats,
            'luggage_included'    => (int) ($trip->luggage_included ?? 1),
            'luggage_weight_kg'   => (float) ($trip->luggage_weight_kg ?? 20),
            'extra_luggage_fee'   => (float) ($trip->extra_luggage_fee ?? 0),
            'extra_luggage_slots' => (int) ($trip->extra_luggage_slots ?? 0),
            'vehicle_type'        => $trip->vehicle_type,
            'distance_km'         => $trip->distance_km,
            'status'              => $trip->status,
            'driver'              => [
                'id'           => null,
                'name'         => "Chauffeur en attente d'attribution",
                'first_name'   => "Chauffeur en attente d'attribution",
                'last_name'    => '',
                'rating'       => null,
                'rating_count' => null,
                'photo'        => null,
                'company_id'   => $itinerary->company_id,
                'company_name' => $company?->name,
                'company_logo' => $companyLogo,
            ],
        ];
    }

    private function fmt(CompanyItinerary $i): array
    {
        $company = $i->company;

        return [
            'id'              => $i->id,
            'departure'       => $i->departure,
            'departure_point' => $i->departure_point,
            'departure_time'  => $i->departure_time ? substr($i->departure_time, 0, 5) : null,
            'destination'     => $i->destination,
            'arrival_point'   => $i->arrival_point,
            'arrival_time'    => $i->arrival_time ? substr($i->arrival_time, 0, 5) : null,
            'price'           => (float) ($i->price ?? 0),
            'distance_km'     => $i->distance_km,
            'duration_min'    => $i->duration_min,
            'vehicle_type'    => $i->vehicle_type,
            'notes'           => $i->notes,
            // ⚠️ Numéro de la société volontairement absent : le client contacte
            // la société uniquement via l'appel in-app — aucun numéro brut transmis.
            'company'         => $company ? [
                'id'    => $company->id,
                'name'  => $company->name,
                'logo'  => $company->logo
                    ? (str_starts_with($company->logo, 'http') ? $company->logo : url('storage/' . $company->logo))
                    : null,
            ] : null,
        ];
    }
}
