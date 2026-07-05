<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Driver\Driver;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DriverController extends Controller
{
    private function company()
    {
        return auth('company')->user();
    }

    public function index(Request $request)
    {
        $company = $this->company();
        $query   = Driver::where('company_id', $company->id)->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name',  'like', '%' . $request->search . '%')
                  ->orWhere('phone',      'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('driver_status')) {
            $query->where('driver_status', $request->driver_status);
        }

        $drivers = $query->paginate(15);
        return view('company.drivers.index', compact('drivers'));
    }

    public function create()
    {
        $vehicleTypes = VehicleType::activeNames();
        return view('company.drivers.create', compact('vehicleTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'phone'        => 'required|string|max:20|unique:drivers,phone',
            'password'     => 'required|string|min:8|confirmed',
            'birth_date'   => 'nullable|date',
            'birth_place'  => 'nullable|string|max:100',
            'vehicle_brand'=> 'nullable|string|max:50',
            'vehicle_model'=> 'nullable|string|max:50',
            'vehicle_plate'=> 'nullable|string|max:20',
            'vehicle_color'=> 'nullable|string|max:30',
            'vehicle_type' => 'nullable|string|max:30',
            'new_vehicle_type' => 'nullable|string|max:50',
            'vehicle_city' => 'nullable|string|max:100',
        ]);

        $company = $this->company();

        Driver::create([
            'first_name'    => $request->first_name,
            'last_name'     => $request->last_name,
            'phone'         => $request->phone,
            'password'      => Hash::make($request->password),
            'birth_date'    => $request->birth_date,
            'birth_place'   => $request->birth_place,
            'vehicle_brand' => $request->vehicle_brand,
            'vehicle_model' => $request->vehicle_model,
            'vehicle_plate' => $request->vehicle_plate,
            'vehicle_color' => $request->vehicle_color,
            'vehicle_type'  => $this->resolveType($request, $company->id),
            'vehicle_city'  => $request->vehicle_city,
            'company_id'    => $company->id,
            'status'        => 'pending',
            'driver_status' => 'offline',
        ]);

        return redirect()->route('company.drivers.index')
                         ->with('success', 'Chauffeur créé avec succès. En attente de validation KYC.');
    }

    public function show($id)
    {
        $driver = Driver::where('company_id', $this->company()->id)->findOrFail($id);
        return view('company.drivers.show', compact('driver'));
    }

    public function edit($id)
    {
        $driver = Driver::where('company_id', $this->company()->id)->findOrFail($id);
        $vehicleTypes = VehicleType::activeNames();
        return view('company.drivers.edit', compact('driver', 'vehicleTypes'));
    }

    public function update(Request $request, $id)
    {
        $driver = Driver::where('company_id', $this->company()->id)->findOrFail($id);

        $request->validate([
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'phone'        => 'required|string|max:20|unique:drivers,phone,' . $driver->id,
            'password'     => 'nullable|string|min:8|confirmed',
            'birth_date'   => 'nullable|date',
            'birth_place'  => 'nullable|string|max:100',
            'vehicle_brand'=> 'nullable|string|max:50',
            'vehicle_model'=> 'nullable|string|max:50',
            'vehicle_plate'=> 'nullable|string|max:20',
            'vehicle_color'=> 'nullable|string|max:30',
            'vehicle_type' => 'nullable|string|max:30',
            'new_vehicle_type' => 'nullable|string|max:50',
            'vehicle_city' => 'nullable|string|max:100',
        ]);

        $data = $request->only([
            'first_name','last_name','phone','birth_date','birth_place',
            'vehicle_brand','vehicle_model','vehicle_plate',
            'vehicle_color','vehicle_city',
        ]);
        $data['vehicle_type'] = $this->resolveType($request, $this->company()->id);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $driver->update($data);

        return redirect()->route('company.drivers.show', $driver->id)
                         ->with('success', 'Profil chauffeur mis à jour.');
    }

    // Résout le type de véhicule choisi : gère l'option "+ Autre (nouveau type)"
    // en enregistrant le nouveau nom dans la liste partagée vehicle_types.
    private function resolveType(Request $request, $companyId): ?string
    {
        if ($request->vehicle_type === '__other__' && filled($request->new_vehicle_type)) {
            $vt = VehicleType::addIfMissing($request->new_vehicle_type, 'company', $companyId);
            return $vt?->name ?? trim($request->new_vehicle_type);
        }

        return $request->vehicle_type ?: null;
    }

    public function activate($id)
    {
        $driver = Driver::where('company_id', $this->company()->id)->findOrFail($id);
        $driver->update(['status' => 'approved']);
        return back()->with('success', 'Chauffeur activé.');
    }

    public function suspend($id)
    {
        $driver = Driver::where('company_id', $this->company()->id)->findOrFail($id);
        $driver->update(['status' => 'suspended']);
        return back()->with('success', 'Chauffeur suspendu.');
    }

    public function assign(Request $request, $id)
    {
        $driver = Driver::findOrFail($id);

        if ($driver->company_id) {
            return back()->with('error', 'Ce chauffeur est déjà affecté à une société.');
        }

        $driver->update(['company_id' => $this->company()->id]);
        return back()->with('success', 'Chauffeur affecté à votre société.');
    }

    public function remove($id)
    {
        $driver = Driver::where('company_id', $this->company()->id)->findOrFail($id);
        $driver->update(['company_id' => null]);
        return back()->with('success', 'Chauffeur retiré de votre société.');
    }
}
