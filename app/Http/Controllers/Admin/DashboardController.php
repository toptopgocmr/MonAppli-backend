<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User\User;
use App\Models\Driver\Driver;
use App\Models\Trip;
use App\Models\Payment;
use App\Models\SosAlert;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $stats = [
            'total_users'      => User::count(),
            'new_users_today'  => User::whereDate('created_at', today())->count(),
            'active_drivers'   => Driver::where('status', 'approved')->count(),
            'online_drivers'   => Driver::where('driver_status', 'online')->count(),
            'today_rides'      => Trip::whereDate('created_at', today())->count(),
            'active_rides'     => Trip::where('status', 'in_progress')->count(),
            'today_revenue'    => Payment::where('status', 'success')->whereDate('created_at', today())->sum('amount'),
            'today_commission' => Payment::where('status', 'success')->whereDate('created_at', today())->sum('commission'),
        ];

        $drivers = Driver::where('status', 'approved')
            ->whereNotNull('vehicle_lat')
            ->whereNotNull('vehicle_lng')
            ->get();

        return view('admin.dashboard', compact('stats', 'drivers'));
    }

    /**
     * API JSON : positions en temps réel + filtres
     * GET /admin/drivers/live?chauffeur=&matricule=&couleur=&status=
     */
    public function liveDrivers(Request $request)
    {
        $query = Driver::where('status', 'approved')
            ->whereNotNull('vehicle_lat')
            ->whereNotNull('vehicle_lng');

        if ($request->filled('chauffeur')) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->chauffeur . '%')
                  ->orWhere('last_name',  'like', '%' . $request->chauffeur . '%');
            });
        }

        if ($request->filled('matricule')) {
            $query->where('vehicle_plate', 'like', '%' . $request->matricule . '%');
        }

        if ($request->filled('couleur')) {
            $query->where('vehicle_color', 'like', '%' . $request->couleur . '%');
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('driver_status', $request->status);
        }

        $drivers = $query->get()->map(fn($d) => [
            'id'            => $d->id,
            'first_name'    => $d->first_name,
            'last_name'     => $d->last_name,
            'phone'         => $d->phone,
            'vehicle_plate' => $d->vehicle_plate,
            'vehicle_brand' => $d->vehicle_brand,
            'vehicle_model' => $d->vehicle_model,
            'vehicle_color' => $d->vehicle_color,
            'vehicle_type'  => $d->vehicle_type,
            'driver_status' => $d->driver_status,
            'lat'           => (float) $d->vehicle_lat,
            'lng'           => (float) $d->vehicle_lng,
            'updated_at'    => $d->updated_at?->diffForHumans(),
        ]);

        return response()->json(['drivers' => $drivers]);
    }
}