<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Driver\Driver;
use App\Models\Trip;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    private function companyId()
    {
        return \App\Support\CompanyContext::company()->id;
    }

    private function driverIds()
    {
        return Driver::where('company_id', $this->companyId())->pluck('id');
    }

    public function index(Request $request)
    {
        $driverIds = $this->driverIds();

        $query = Trip::whereIn('driver_id', $driverIds)
                     ->with(['driver', 'user'])
                     ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->get('period') === 'month') {
            $query->whereMonth('created_at', now()->month)
                  ->whereYear('created_at', now()->year);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('departure',   'like', '%' . $request->search . '%')
                  ->orWhere('destination', 'like', '%' . $request->search . '%')
                  ->orWhere('departure_city', 'like', '%' . $request->search . '%')
                  ->orWhere('destination_city', 'like', '%' . $request->search . '%');
            });
        }

        $trips = $query->paginate(20);

        // ✅ Trajets issus d'itinéraires programmés réservés + payés par des
        // clients, mais sans chauffeur assigné — la société choisit le
        // chauffeur ici (voir UserCompanyTripController::book() côté client
        // et assignDriver() ci-dessous).
        $pendingAssignment = Trip::where('company_id', $this->companyId())
            ->whereNull('driver_id')
            ->with(['bookings' => fn ($q) => $q->paid()->with('user')])
            ->orderBy('departure_date')
            ->orderBy('departure_time')
            ->get();

        $companyDrivers = Driver::where('company_id', $this->companyId())
            ->where('status', 'approved')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'vehicle_type']);

        return view('company.reservations.index', compact('trips', 'pendingAssignment', 'companyDrivers'));
    }

    public function show($id)
    {
        $driverIds = $this->driverIds();
        $trip = Trip::whereIn('driver_id', $driverIds)->with(['driver', 'user'])->findOrFail($id);
        return view('company.reservations.show', compact('trip'));
    }

    // ✅ Assigne un chauffeur de la société à un trajet issu d'un itinéraire
    // programmé, une fois qu'au moins un client a réservé/payé sa place.
    public function assignDriver(Request $request, $tripId)
    {
        $request->validate(['driver_id' => 'required|exists:drivers,id']);

        $trip = Trip::where('company_id', $this->companyId())
            ->whereNull('driver_id')
            ->findOrFail($tripId);

        $driver = Driver::where('company_id', $this->companyId())->findOrFail($request->driver_id);

        $trip->update(['driver_id' => $driver->id]);

        return back()->with('success', "Chauffeur {$driver->first_name} {$driver->last_name} assigné au trajet #{$trip->id}.");
    }
}
