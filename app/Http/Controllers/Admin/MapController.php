<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Driver\Driver;
use App\Models\Trip;

class MapController extends Controller
{
    public function index()
    {
        return view('admin.geolocation.index');
    }

    public function trips(Request $request)
    {
        $query = Trip::with(['driver', 'user'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $trips = $query->get()->map(function ($trip) {
            return [
                'id'              => $trip->id,
                'driver'          => $trip->driver ? [
                    'name'         => $trip->driver->first_name . ' ' . $trip->driver->last_name,
                    'phone'        => $trip->driver->phone,
                    'vehicle_plate'=> $trip->driver->vehicle_plate,
                    'vehicle_type' => $trip->driver->vehicle_type,
                ] : null,
                'user'            => $trip->user ? [
                    'name'  => $trip->user->first_name . ' ' . $trip->user->last_name,
                    'phone' => $trip->user->phone,
                ] : null,
                'pickup_address'  => $trip->pickup_address,
                'dropoff_address' => $trip->dropoff_address,
                'pickup_lat'      => $trip->pickup_lat,
                'pickup_lng'      => $trip->pickup_lng,
                'dropoff_lat'     => $trip->dropoff_lat,
                'dropoff_lng'     => $trip->dropoff_lng,
                'distance_km'     => $trip->distance_km,
                'amount'          => $trip->amount,
                'price_per_seat'  => $trip->price_per_seat,
                'available_seats' => $trip->available_seats,
                'departure_date'  => $trip->departure_date,
                'departure_time'  => $trip->departure_time,
                'status'          => $trip->status,
                'created_at'      => $trip->created_at->format('d/m/Y H:i'),
            ];
        });

        return response()->json(['data' => $trips]);
    }

    public function onlineDrivers()
    {
        $drivers = Driver::with('latestLocation')
            ->where('driver_status', 'online')
            ->where('status', 'approved')
            ->get()
            ->map(function ($driver) {
                return [
                    'id'            => $driver->id,
                    'name'          => $driver->first_name . ' ' . $driver->last_name,
                    'vehicle_plate' => $driver->vehicle_plate,
                    'vehicle_type'  => $driver->vehicle_type,
                    'vehicle_color' => $driver->vehicle_color,
                    'driver_status' => $driver->driver_status,
                    'lat'           => $driver->latestLocation?->lat,
                    'lng'           => $driver->latestLocation?->lng,
                    'recorded_at'   => $driver->latestLocation?->recorded_at,
                ];
            });

        return response()->json($drivers);
    }

    public function activeTrips()
    {
        return response()->json(
            Trip::with('user', 'driver')
                ->whereIn('status', ['accepted', 'in_progress'])
                ->get()
        );
    }
}