<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Events\DriverStatusUpdated;
use Illuminate\Http\Request;

class DriverStatusController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'status' => 'required|in:online,pause,offline',
        ]);

        $driver = $request->user();
        $driver->driver_status = $request->status; // ✅ Corrigé : driver_status et non status
        $driver->save();

        broadcast(new DriverStatusUpdated($driver));

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour avec succès.',
            'status'  => $driver->driver_status,
        ]);
    }

    public function show(Request $request)
    {
        $driver = $request->user();

        return response()->json([
            'success' => true,
            'status'  => $driver->driver_status, // ✅ Corrigé
        ]);
    }
}