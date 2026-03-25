<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SosAlert;
use App\Models\User\User;
use App\Models\Driver\Driver;
use Carbon\Carbon;

class SosAlertController extends Controller
{
    public function index(Request $request)
    {
        $query = SosAlert::with(['sender', 'trip', 'treatedBy'])
            ->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('sender_type')) {
            $type = $request->sender_type === 'driver'
                ? \App\Models\Driver\Driver::class
                : \App\Models\User\User::class;
            $query->where('sender_type', $type);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $alerts = $query->paginate(20);

        $totalActive   = SosAlert::where('status', 'active')->count();
        $totalTreated  = SosAlert::where('status', 'treated')->count();
        $totalToday    = SosAlert::whereDate('created_at', today())->count();
        $totalAll      = SosAlert::count();

        return view('admin.sos.index', compact(
            'alerts', 'totalActive', 'totalTreated', 'totalToday', 'totalAll'
        ));
    }

    public function show($id)
    {
        $alert = SosAlert::with(['sender', 'trip.driver', 'trip.user', 'treatedBy'])
            ->findOrFail($id);

        return view('admin.sos.show', compact('alert'));
    }

    public function treat(Request $request, $id)
    {
        $alert = SosAlert::findOrFail($id);

        if ($alert->status === 'treated') {
            return back()->with('error', 'Cette alerte a déjà été traitée.');
        }

        $alert->update([
            'status'     => 'treated',
            'treated_by' => session('admin_id'),
            'treated_at' => now(),
        ]);

        return back()->with('success', 'Alerte SOS marquée comme traitée.');
    }

    public function treatAll()
    {
        $count = SosAlert::where('status', 'active')->count();

        SosAlert::where('status', 'active')->update([
            'status'     => 'treated',
            'treated_by' => session('admin_id'),
            'treated_at' => now(),
        ]);

        return back()->with('success', "{$count} alerte(s) marquée(s) comme traitée(s).");
    }

    public function destroy($id)
    {
        SosAlert::findOrFail($id)->delete();
        return back()->with('success', 'Alerte supprimée.');
    }

    public function live()
    {
        $alerts = SosAlert::where('status', 'active')
            ->with(['sender', 'sender.vehicle'])
            ->latest()
            ->get()
            ->map(function($a) {
                $isDriver = str_contains($a->sender_type, 'Driver');
                $sender   = $a->sender;
                $vehicle  = ($isDriver && $sender) ? optional($sender->vehicle) : null;

                $vehicleLabel = null;
                if ($vehicle && ($vehicle->brand || $vehicle->model || $vehicle->plate)) {
                    $vehicleLabel = trim(($vehicle->brand ?? '') . ' ' . ($vehicle->model ?? ''));
                    if ($vehicle->plate) {
                        $vehicleLabel .= ' — ' . $vehicle->plate;
                    }
                }

                return [
                    'id'          => $a->id,
                    'sender_name' => trim(($sender->first_name ?? '') . ' ' . ($sender->last_name ?? '')),
                    'sender_type' => $isDriver ? 'driver' : 'user',
                    'phone'       => $sender->phone ?? null,
                    'vehicle'     => $vehicleLabel,
                    'message'     => $a->message,
                    'lat'         => (float) $a->lat,
                    'lng'         => (float) $a->lng,
                    'created_at'  => $a->created_at->diffForHumans(),
                    'trip_id'     => $a->trip_id,
                ];
            });

        return response()->json(['alerts' => $alerts]);
    }
}