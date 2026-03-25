<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function index(Request $request)
    {
        $query = Trip::with(['driver', 'vehicle', 'bookings'])->latest();

        // ✅ Recherche départ séparé
        if ($request->filled('departure')) {
            $dep = $request->departure;
            $query->where(function ($q) use ($dep) {
                $q->where('pickup_address',   'like', "%$dep%")
                  ->orWhere('departure',      'like', "%$dep%")
                  ->orWhere('departure_city', 'like', "%$dep%");
            });
        }

        // ✅ Recherche destination séparée
        if ($request->filled('destination')) {
            $dest = $request->destination;
            $query->where(function ($q) use ($dest) {
                $q->where('dropoff_address',   'like', "%$dest%")
                  ->orWhere('destination',     'like', "%$dest%")
                  ->orWhere('destination_city','like', "%$dest%");
            });
        }

        // Ancien ?search= rétrocompatibilité
        if ($request->filled('search') && !$request->filled('departure') && !$request->filled('destination')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('pickup_address',   'like', "%$s%")
                  ->orWhere('dropoff_address', 'like', "%$s%")
                  ->orWhere('departure',       'like', "%$s%")
                  ->orWhere('destination',     'like', "%$s%");
            });
        }

        // Filtre statut
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filtre dates
        if ($request->filled('from')) {
            $query->whereDate('departure_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('departure_date', '<=', $request->to);
        }

        $trips = $query->paginate(10)->withQueryString();

        $stats = [
            'total'       => Trip::count(),
            'pending'     => Trip::where('status', 'pending')->count(),
            'in_progress' => Trip::where('status', 'in_progress')->count(),
            'completed'   => Trip::where('status', 'completed')->count(),
            'cancelled'   => Trip::whereIn('status', ['cancelled', 'rejected'])->count(),
        ];

        return view('admin.trips.index', compact('trips', 'stats'));
    }

    public function show($id)
    {
        $trip = Trip::with(['driver', 'vehicle', 'bookings.user'])->findOrFail($id);
        return view('admin.trips.show', compact('trip'));
    }

    public function destroy($id)
    {
        $trip = Trip::findOrFail($id);
        $trip->delete();
        return redirect()->route('admin.trips.index')->with('success', 'Trajet supprimé.');
    }

    public function updateStatus(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);
        $request->validate(['status' => 'required|in:pending,accepted,in_progress,completed,cancelled,rejected']);
        $trip->update(['status' => $request->status]);
        return back()->with('success', 'Statut mis à jour.');
    }
}
