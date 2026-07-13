<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyItinerary;
use App\Models\PricingGrid;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ItineraryController extends Controller
{
    private function company()
    {
        // ✅ Résout la société pour le compte principal ET pour un agent
        // connecté (auth('company')->user() renvoie null pour un agent).
        return \App\Support\CompanyContext::company();
    }

    public function index(Request $request)
    {
        $company = $this->company();
        $query   = CompanyItinerary::where('company_id', $company->id)->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('departure',   'like', '%' . $request->search . '%')
                  ->orWhere('destination','like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $itineraries = $query->paginate(20);
        return view('company.itineraries.index', compact('itineraries'));
    }

    public function create()
    {
        $pricingGrids = PricingGrid::where('company_id', $this->company()->id)
                                   ->where('is_active', true)
                                   ->with('rates')
                                   ->get();
        $vehicleTypes = VehicleType::activeNames();

        return view('company.itineraries.create', compact('pricingGrids', 'vehicleTypes'));
    }

    public function store(Request $request)
    {
        $company = $this->company();

        $request->validate([
            'departure'       => 'required|string|max:200',
            'departure_point' => 'nullable|string|max:300',
            'departure_time'  => 'nullable|date_format:H:i',
            'destination'     => 'required|string|max:200',
            'arrival_point'   => 'nullable|string|max:300',
            'arrival_time'    => 'nullable|date_format:H:i',
            'pricing_grid_id' => 'nullable|exists:pricing_grids,id',
            'price'           => 'nullable|numeric|min:0',
            'distance_km'     => 'nullable|numeric|min:0',
            'duration_min'    => 'nullable|integer|min:0',
            'vehicle_type'    => 'nullable|string|max:50',
            'new_vehicle_type'=> 'nullable|string|max:50',
            'seats'           => 'nullable|integer|min:1|max:60',
            'notes'           => 'nullable|string|max:500',
        ]);

        // Sécurité : la grille doit appartenir à la société
        $gridId = $request->pricing_grid_id
            ? PricingGrid::where('company_id', $company->id)->where('id', $request->pricing_grid_id)->value('id')
            : null;

        // Distance/durée calculées automatiquement côté serveur si non fournies
        // par le calcul JS (fiabilité : ne dépend plus uniquement du navigateur).
        [$distanceKm, $durationMin] = $this->resolveRoute(
            $request->departure, $request->destination,
            $request->distance_km, $request->duration_min
        );

        CompanyItinerary::create([
            'company_id'      => $company->id,
            'pricing_grid_id' => $gridId,
            'departure'       => $request->departure,
            'departure_point' => $request->departure_point,
            'departure_time'  => $request->departure_time,
            'destination'     => $request->destination,
            'arrival_point'   => $request->arrival_point,
            'arrival_time'    => $request->arrival_time,
            'price'           => $request->price,
            'distance_km'     => $distanceKm,
            'duration_min'    => $durationMin,
            'vehicle_type'    => $this->resolveVehicleType($request, $company->id),
            'seats'           => $request->seats ?: 4,
            'is_active'       => true,
            'notes'           => $request->notes,
        ]);

        return redirect()->route('company.itineraries.index')
                         ->with('success', 'Itinéraire créé avec succès.');
    }

    public function edit($id)
    {
        $company = $this->company();
        $itinerary = CompanyItinerary::where('company_id', $company->id)->findOrFail($id);
        $pricingGrids = PricingGrid::where('company_id', $company->id)
                                   ->where('is_active', true)
                                   ->with('rates')
                                   ->get();
        $vehicleTypes = VehicleType::activeNames();

        return view('company.itineraries.edit', compact('itinerary', 'pricingGrids', 'vehicleTypes'));
    }

    public function update(Request $request, $id)
    {
        $company = $this->company();
        $itinerary = CompanyItinerary::where('company_id', $company->id)->findOrFail($id);

        $request->validate([
            'departure'       => 'required|string|max:200',
            'departure_point' => 'nullable|string|max:300',
            'departure_time'  => 'nullable|date_format:H:i',
            'destination'     => 'required|string|max:200',
            'arrival_point'   => 'nullable|string|max:300',
            'arrival_time'    => 'nullable|date_format:H:i',
            'pricing_grid_id' => 'nullable|exists:pricing_grids,id',
            'price'           => 'nullable|numeric|min:0',
            'distance_km'     => 'nullable|numeric|min:0',
            'duration_min'    => 'nullable|integer|min:0',
            'vehicle_type'    => 'nullable|string|max:50',
            'new_vehicle_type'=> 'nullable|string|max:50',
            'seats'           => 'nullable|integer|min:1|max:60',
            'notes'           => 'nullable|string|max:500',
        ]);

        $gridId = $request->pricing_grid_id
            ? PricingGrid::where('company_id', $company->id)->where('id', $request->pricing_grid_id)->value('id')
            : null;

        // Si le départ ou la destination a changé, on ignore l'ancienne distance/durée
        // pour forcer un recalcul automatique.
        $routeChanged = $request->departure !== $itinerary->departure
            || $request->destination !== $itinerary->destination;

        [$distanceKm, $durationMin] = $this->resolveRoute(
            $request->departure, $request->destination,
            $routeChanged ? null : $request->distance_km,
            $routeChanged ? null : $request->duration_min
        );

        $itinerary->update(array_merge(
            $request->only([
                'departure','departure_point','departure_time',
                'destination','arrival_point','arrival_time',
                'price','notes','seats',
            ]),
            [
                'pricing_grid_id' => $gridId,
                'distance_km'     => $distanceKm,
                'duration_min'    => $durationMin,
                'vehicle_type'    => $this->resolveVehicleType($request, $company->id),
            ]
        ));

        return redirect()->route('company.itineraries.index')
                         ->with('success', 'Itinéraire mis à jour.');
    }

    // Calcule automatiquement la distance (km) et la durée (min) du trajet à
    // partir des noms de ville, si elles ne sont pas déjà fournies (ex: par le
    // calcul JS côté formulaire). Sert de filet de sécurité fiable côté serveur
    // — géocodage (Nominatim) + calcul d'itinéraire routier (OSRM).
    private function resolveRoute(?string $departure, ?string $destination, $distanceKm, $durationMin): array
    {
        if (filled($distanceKm) && filled($durationMin)) {
            return [(float) $distanceKm, (int) $durationMin];
        }

        if (blank($departure) || blank($destination)) {
            return [$distanceKm !== null ? (float) $distanceKm : null, $durationMin !== null ? (int) $durationMin : null];
        }

        try {
            $from = $this->geocodeCity($departure);
            $to   = $this->geocodeCity($destination);

            if (!$from || !$to) {
                return [$distanceKm !== null ? (float) $distanceKm : null, $durationMin !== null ? (int) $durationMin : null];
            }

            $response = Http::timeout(8)->get(
                "https://router.project-osrm.org/route/v1/driving/{$from['lon']},{$from['lat']};{$to['lon']},{$to['lat']}",
                ['overview' => 'false']
            );

            if (!$response->ok() || ($response->json('code') !== 'Ok')) {
                return [$distanceKm !== null ? (float) $distanceKm : null, $durationMin !== null ? (int) $durationMin : null];
            }

            $route = $response->json('routes.0');
            if (!$route) {
                return [$distanceKm !== null ? (float) $distanceKm : null, $durationMin !== null ? (int) $durationMin : null];
            }

            return [
                round($route['distance'] / 1000, 1),
                (int) round($route['duration'] / 60),
            ];
        } catch (\Throwable $e) {
            Log::warning('Calcul automatique distance/durée itinéraire échoué', [
                'departure' => $departure, 'destination' => $destination, 'error' => $e->getMessage(),
            ]);
            return [$distanceKm !== null ? (float) $distanceKm : null, $durationMin !== null ? (int) $durationMin : null];
        }
    }

    private function geocodeCity(string $city): ?array
    {
        $response = Http::withHeaders(['User-Agent' => 'TopTopGo/1.0 (toptopgoinfo@gmail.com)'])
            ->timeout(8)
            ->get('https://nominatim.openstreetmap.org/search', [
                'q' => $city, 'format' => 'json', 'limit' => 1, 'accept-language' => 'fr',
            ]);

        if (!$response->ok()) {
            return null;
        }

        $first = $response->json(0);
        if (!$first) {
            return null;
        }

        return ['lat' => (float) $first['lat'], 'lon' => (float) $first['lon']];
    }

    // Résout le type de véhicule choisi : gère l'option "+ Autre (nouveau type)"
    // en enregistrant le nouveau nom dans la liste partagée vehicle_types.
    private function resolveVehicleType(Request $request, $companyId): ?string
    {
        if ($request->vehicle_type === '__other__' && filled($request->new_vehicle_type)) {
            $vt = VehicleType::addIfMissing($request->new_vehicle_type, 'company', $companyId);
            return $vt?->name ?? trim($request->new_vehicle_type);
        }

        return $request->vehicle_type ?: null;
    }

    public function toggle($id)
    {
        $itinerary = CompanyItinerary::where('company_id', $this->company()->id)->findOrFail($id);
        $itinerary->update(['is_active' => !$itinerary->is_active]);

        $msg = $itinerary->is_active ? 'Itinéraire activé.' : 'Itinéraire désactivé.';
        return back()->with('success', $msg);
    }

    public function destroy($id)
    {
        $itinerary = CompanyItinerary::where('company_id', $this->company()->id)->findOrFail($id);
        $itinerary->delete();
        return back()->with('success', 'Itinéraire supprimé.');
    }
}
