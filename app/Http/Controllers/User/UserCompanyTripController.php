<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CompanyItinerary;
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
            'company'         => $company ? [
                'id'    => $company->id,
                'name'  => $company->name,
                'phone' => $company->phone,
                'logo'  => $company->logo
                    ? (str_starts_with($company->logo, 'http') ? $company->logo : url('storage/' . $company->logo))
                    : null,
            ] : null,
        ];
    }
}
