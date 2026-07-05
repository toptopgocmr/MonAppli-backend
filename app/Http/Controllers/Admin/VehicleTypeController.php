<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleType;
use Illuminate\Http\Request;

class VehicleTypeController extends Controller
{
    public function index()
    {
        $vehicleTypes = VehicleType::orderBy('name')->get();
        return view('admin.vehicle-types.index', compact('vehicleTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
        ]);

        $adminId = session('admin_id');
        $type = VehicleType::addIfMissing($request->name, 'admin', $adminId);

        if (!$type) {
            return back()->with('error', "Impossible d'ajouter ce type de véhicule.");
        }

        return back()->with('success', 'Type de véhicule ajouté : ' . $type->name);
    }

    public function toggle(VehicleType $vehicleType)
    {
        $vehicleType->update(['is_active' => !$vehicleType->is_active]);
        return back()->with('success', $vehicleType->is_active ? 'Type réactivé.' : 'Type désactivé.');
    }

    public function destroy(VehicleType $vehicleType)
    {
        $vehicleType->delete();
        return back()->with('success', 'Type de véhicule supprimé.');
    }
}
