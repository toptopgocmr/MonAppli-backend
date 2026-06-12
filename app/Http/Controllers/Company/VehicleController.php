<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Driver\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VehicleController extends Controller
{
    // La flotte = les chauffeurs de la société avec leurs infos véhicule
    public function index()
    {
        $company = auth('company')->user();
        $drivers = Driver::where('company_id', $company->id)
                         ->whereNotNull('vehicle_plate')
                         ->orderBy('first_name')
                         ->paginate(15);

        return view('company.vehicles.index', compact('drivers'));
    }

    public function create()
    {
        // Chauffeurs de la société sans véhicule assigné
        $company = auth('company')->user();
        $drivers = Driver::where('company_id', $company->id)
                         ->whereNull('vehicle_plate')
                         ->get();
        return view('company.vehicles.create', compact('drivers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'driver_id'    => 'required|exists:drivers,id',
            'vehicle_plate'=> 'required|string|max:20',
            'vehicle_brand'=> 'required|string|max:50',
            'vehicle_model'=> 'required|string|max:50',
            'vehicle_color'=> 'required|string|max:30',
            'vehicle_type' => 'required|string|max:30',
            'vehicle_city' => 'nullable|string|max:100',
        ]);

        $company = auth('company')->user();
        $driver  = Driver::where('company_id', $company->id)->findOrFail($request->driver_id);

        $driver->update([
            'vehicle_plate' => $request->vehicle_plate,
            'vehicle_brand' => $request->vehicle_brand,
            'vehicle_model' => $request->vehicle_model,
            'vehicle_color' => $request->vehicle_color,
            'vehicle_type'  => $request->vehicle_type,
            'vehicle_city'  => $request->vehicle_city,
            'vehicle_country'=> $request->vehicle_country,
        ]);

        return redirect()->route('company.vehicles.index')
                         ->with('success', 'Véhicule ajouté avec succès.');
    }

    public function edit($id)
    {
        $company = auth('company')->user();
        $driver  = Driver::where('company_id', $company->id)->findOrFail($id);
        return view('company.vehicles.edit', compact('driver'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'vehicle_plate'=> 'required|string|max:20',
            'vehicle_brand'=> 'required|string|max:50',
            'vehicle_model'=> 'required|string|max:50',
            'vehicle_color'=> 'required|string|max:30',
            'vehicle_type' => 'required|string|max:30',
        ]);

        $company = auth('company')->user();
        $driver  = Driver::where('company_id', $company->id)->findOrFail($id);

        $driver->update($request->only([
            'vehicle_plate', 'vehicle_brand', 'vehicle_model',
            'vehicle_color', 'vehicle_type', 'vehicle_city', 'vehicle_country',
        ]));

        return redirect()->route('company.vehicles.index')
                         ->with('success', 'Véhicule mis à jour.');
    }

    public function destroy($id)
    {
        $company = auth('company')->user();
        $driver  = Driver::where('company_id', $company->id)->findOrFail($id);

        $driver->update([
            'vehicle_plate' => null, 'vehicle_brand' => null,
            'vehicle_model' => null, 'vehicle_color' => null,
            'vehicle_type'  => null,
        ]);

        return back()->with('success', 'Véhicule retiré.');
    }
}
