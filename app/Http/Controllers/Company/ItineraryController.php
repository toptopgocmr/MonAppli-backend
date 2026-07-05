<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyItinerary;
use App\Models\PricingGrid;
use Illuminate\Http\Request;

class ItineraryController extends Controller
{
    private function company()
    {
        return auth('company')->user();
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

        return view('company.itineraries.create', compact('pricingGrids'));
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
            'notes'           => 'nullable|string|max:500',
        ]);

        // Sécurité : la grille doit appartenir à la société
        $gridId = $request->pricing_grid_id
            ? PricingGrid::where('company_id', $company->id)->where('id', $request->pricing_grid_id)->value('id')
            : null;

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
            'distance_km'     => $request->distance_km,
            'duration_min'    => $request->duration_min,
            'vehicle_type'    => $request->vehicle_type,
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

        return view('company.itineraries.edit', compact('itinerary', 'pricingGrids'));
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
            'notes'           => 'nullable|string|max:500',
        ]);

        $gridId = $request->pricing_grid_id
            ? PricingGrid::where('company_id', $company->id)->where('id', $request->pricing_grid_id)->value('id')
            : null;

        $itinerary->update(array_merge(
            $request->only([
                'departure','departure_point','departure_time',
                'destination','arrival_point','arrival_time',
                'price','distance_km','duration_min','vehicle_type','notes',
            ]),
            ['pricing_grid_id' => $gridId]
        ));

        return redirect()->route('company.itineraries.index')
                         ->with('success', 'Itinéraire mis à jour.');
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
