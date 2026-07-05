<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Driver\Driver;
use App\Models\Vehicle;
use App\Models\VehicleDriverShift;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    private function company()
    {
        return auth('company')->user();
    }

    // La flotte = les véhicules de la société, chacun pouvant avoir plusieurs chauffeurs
    public function index(Request $request)
    {
        $company = $this->company();

        $query = Vehicle::where('company_id', $company->id)
                        ->withCount(['activeShifts'])
                        ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('plate', 'like', '%' . $request->search . '%')
                  ->orWhere('brand', 'like', '%' . $request->search . '%')
                  ->orWhere('model', 'like', '%' . $request->search . '%');
            });
        }

        $vehicles = $query->paginate(15)->withQueryString();

        // Nombre de chauffeurs distincts par véhicule (pour affichage rapide)
        foreach ($vehicles as $vehicle) {
            $vehicle->drivers_count = VehicleDriverShift::where('vehicle_id', $vehicle->id)
                ->where('status', 'active')
                ->distinct('driver_id')
                ->count('driver_id');
        }

        return view('company.vehicles.index', compact('vehicles'));
    }

    public function create()
    {
        return view('company.vehicles.create');
    }

    public function store(Request $request)
    {
        $company = $this->company();

        $request->validate([
            'plate' => 'required|string|max:20|unique:vehicles,plate',
            'brand' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
            'type'  => 'nullable|string|max:30',
            'city'  => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        Vehicle::create([
            'company_id' => $company->id,
            'plate'      => $request->plate,
            'brand'      => $request->brand,
            'model'      => $request->model,
            'color'      => $request->color,
            'type'       => $request->type,
            'city'       => $request->city,
            'country'    => $request->country,
            'notes'      => $request->notes,
            'status'     => 'active',
        ]);

        return redirect()->route('company.vehicles.index')
                         ->with('success', 'Véhicule ajouté à la flotte.');
    }

    // Fiche véhicule : infos + chauffeurs assignés + créneaux
    public function show($id)
    {
        $company = $this->company();
        $vehicle = Vehicle::where('company_id', $company->id)
                          ->with(['shifts' => fn($q) => $q->orderBy('day_of_week')->orderBy('specific_date')])
                          ->with('shifts.driver')
                          ->findOrFail($id);

        // Chauffeurs de la société pas encore assignés à CE véhicule (peuvent l'être à d'autres)
        $assignedIds = $vehicle->shifts->where('status', 'active')->pluck('driver_id')->unique();
        $availableDrivers = Driver::where('company_id', $company->id)
                                  ->whereNotIn('id', $assignedIds)
                                  ->orderBy('first_name')
                                  ->get();

        return view('company.vehicles.show', compact('vehicle', 'availableDrivers'));
    }

    public function edit($id)
    {
        $company = $this->company();
        $vehicle = Vehicle::where('company_id', $company->id)->findOrFail($id);
        return view('company.vehicles.edit', compact('vehicle'));
    }

    public function update(Request $request, $id)
    {
        $company = $this->company();
        $vehicle = Vehicle::where('company_id', $company->id)->findOrFail($id);

        $request->validate([
            'plate' => 'required|string|max:20|unique:vehicles,plate,' . $vehicle->id,
            'brand' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
            'type'  => 'nullable|string|max:30',
            'city'  => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'status'  => 'nullable|in:active,maintenance,inactive',
            'notes' => 'nullable|string|max:500',
        ]);

        $vehicle->update($request->only([
            'plate', 'brand', 'model', 'color', 'type', 'city', 'country', 'status', 'notes',
        ]));

        // Répercuter les nouvelles infos sur les chauffeurs actuellement assignés
        $this->syncVehicleToDrivers($vehicle);

        return redirect()->route('company.vehicles.index')
                         ->with('success', 'Véhicule mis à jour.');
    }

    public function destroy($id)
    {
        $company = $this->company();
        $vehicle = Vehicle::where('company_id', $company->id)->findOrFail($id);
        $vehicle->delete(); // cascade sur vehicle_driver_shifts

        return back()->with('success', 'Véhicule retiré de la flotte.');
    }

    // ── Association chauffeurs (créneaux / rotation) ─────────────────────

    public function storeShift(Request $request, $id)
    {
        $company = $this->company();
        $vehicle = Vehicle::where('company_id', $company->id)->findOrFail($id);

        $request->validate([
            'driver_id'      => 'required|exists:drivers,id',
            'schedule_mode'  => 'required|in:recurring,specific',
            'day_of_week'    => 'required_if:schedule_mode,recurring|nullable|integer|min:0|max:6',
            'specific_date'  => 'required_if:schedule_mode,specific|nullable|date',
            'start_time'     => 'nullable|date_format:H:i',
            'end_time'       => 'nullable|date_format:H:i',
            'is_primary'     => 'nullable|boolean',
        ]);

        $driver = Driver::where('company_id', $company->id)->findOrFail($request->driver_id);

        $shift = VehicleDriverShift::create([
            'vehicle_id'    => $vehicle->id,
            'driver_id'     => $driver->id,
            'day_of_week'   => $request->schedule_mode === 'recurring' ? $request->day_of_week : null,
            'specific_date' => $request->schedule_mode === 'specific' ? $request->specific_date : null,
            'start_time'    => $request->start_time,
            'end_time'      => $request->end_time,
            'is_primary'    => (bool) $request->is_primary,
            'status'        => 'active',
        ]);

        // Le chauffeur assigné récupère les infos du véhicule (compat écrans existants : trajets, admin, KYC)
        $this->syncVehicleToDriver($vehicle, $driver);

        return back()->with('success', 'Chauffeur assigné au véhicule.');
    }

    public function destroyShift($vehicleId, $shiftId)
    {
        $company = $this->company();
        $vehicle = Vehicle::where('company_id', $company->id)->findOrFail($vehicleId);
        $shift   = VehicleDriverShift::where('vehicle_id', $vehicle->id)->findOrFail($shiftId);

        $driver = $shift->driver;
        $shift->delete();

        // Si le chauffeur n'a plus aucun créneau actif sur CE véhicule, on retire les infos embarquées
        $stillAssigned = VehicleDriverShift::where('vehicle_id', $vehicle->id)
            ->where('driver_id', $driver->id)
            ->where('status', 'active')
            ->exists();

        if (!$stillAssigned && $driver->vehicle_plate === $vehicle->plate) {
            $driver->update([
                'vehicle_plate' => null, 'vehicle_brand' => null, 'vehicle_model' => null,
                'vehicle_color' => null, 'vehicle_type' => null,
            ]);
        }

        return back()->with('success', 'Créneau retiré.');
    }

    // ── Sync infos véhicule → chauffeur (compat Trip::vehicle(), admin, KYC) ──

    private function syncVehicleToDriver(Vehicle $vehicle, Driver $driver): void
    {
        // vehicle_type sur drivers est un enum fermé (Standard/Confort/Van/PMR) :
        // on ne recopie que si la valeur du véhicule y correspond, sinon on laisse tel quel.
        $allowedTypes = ['Standard', 'Confort', 'Van', 'PMR'];

        $driver->update([
            'vehicle_plate' => $vehicle->plate,
            'vehicle_brand' => $vehicle->brand,
            'vehicle_model' => $vehicle->model,
            'vehicle_color' => $vehicle->color,
            'vehicle_type'  => in_array($vehicle->type, $allowedTypes, true) ? $vehicle->type : $driver->vehicle_type,
            'vehicle_city'  => $vehicle->city ?? $driver->vehicle_city,
            'vehicle_country' => $vehicle->country ?? $driver->vehicle_country,
        ]);
    }

    private function syncVehicleToDrivers(Vehicle $vehicle): void
    {
        $driverIds = VehicleDriverShift::where('vehicle_id', $vehicle->id)
            ->where('status', 'active')
            ->pluck('driver_id')
            ->unique();

        foreach (Driver::whereIn('id', $driverIds)->get() as $driver) {
            $this->syncVehicleToDriver($vehicle, $driver);
        }
    }
}
