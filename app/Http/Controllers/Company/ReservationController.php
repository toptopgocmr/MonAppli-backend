<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Driver\Driver;
use App\Models\Trip;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    private function driverIds()
    {
        return Driver::where('company_id', auth('company')->id())->pluck('id');
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

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('departure',   'like', '%' . $request->search . '%')
                  ->orWhere('destination', 'like', '%' . $request->search . '%')
                  ->orWhere('departure_city', 'like', '%' . $request->search . '%')
                  ->orWhere('destination_city', 'like', '%' . $request->search . '%');
            });
        }

        $trips = $query->paginate(20);
        return view('company.reservations.index', compact('trips'));
    }

    public function show($id)
    {
        $driverIds = $this->driverIds();
        $trip = Trip::whereIn('driver_id', $driverIds)->with(['driver', 'user'])->findOrFail($id);
        return view('company.reservations.show', compact('trip'));
    }
}
