<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DriverLocation;

class DriverLocationController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $driver = $request->user();

        DriverLocation::create([
            'driver_id'     => $driver->id,
            'lat'           => $request->lat,
            'lng'           => $request->lng,
            'driver_status' => $driver->driver_status,
            'recorded_at'   => now(),
        ]);

        $driver->update([
            'vehicle_lat' => $request->lat,
            'vehicle_lng' => $request->lng,
        ]);

        return response()->json(['message' => 'Position mise à jour.']);
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:online,pause,offline',
        ]);

        $request->user()->update(['driver_status' => $request->status]);
        return response()->json(['message' => 'Statut mis à jour.']);
    }
}