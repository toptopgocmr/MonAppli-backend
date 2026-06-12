<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyItinerary;
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
        return view('company.itineraries.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'departure'    => 'required|string|max:200',
            'destination'  => 'required|string|max:200',
            'price'        => 'nullable|numeric|min:0',
            'distance_km'  => 'nullable|numeric|min:0',
            'duration_min' => 'nullable|integer|min:0',
            'vehicle_type' => 'nullable|string|max:50',
            'notes'        => 'nullable|string|max:500',
        ]);

        CompanyItinerary::create([
            'company_id'   => $this->company()->id,
            'departure'    => $request->departure,
            'destination'  => $request->destination,
            'price'        => $request->price,
            'distance_km'  => $request->distance_km,
            'duration_min' => $request->duration_min,
            'vehicle_type' => $request->vehicle_type,
            'is_active'    => true,
            'notes'        => $request->notes,
        ]);

        return redirect()->route('company.itineraries.index')
                         ->with('success', 'Itinéraire créé avec succès.');
    }

    public function edit($id)
    {
        $itinerary = CompanyItinerary::where('company_id', $this->company()->id)->findOrFail($id);
        return view('company.itineraries.edit', compact('itinerary'));
    }

    public function update(Request $request, $id)
    {
        $itinerary = CompanyItinerary::where('company_id', $this->company()->id)->findOrFail($id);

        $request->validate([
            'departure'    => 'required|string|max:200',
            'destination'  => 'required|string|max:200',
            'price'        => 'nullable|numeric|min:0',
            'distance_km'  => 'nullable|numeric|min:0',
            'duration_min' => 'nullable|integer|min:0',
            'vehicle_type' => 'nullable|string|max:50',
            'notes'        => 'nullable|string|max:500',
        ]);

        $itinerary->update($request->only([
            'departure','destination','price','distance_km',
            'duration_min','vehicle_type','notes',
        ]));

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
