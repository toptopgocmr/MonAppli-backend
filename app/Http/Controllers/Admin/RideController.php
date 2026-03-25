<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use Illuminate\Http\Request;

class RideController extends Controller
{
    public function index(Request $request)
    {
        $query = Ride::with(['passenger', 'driver.user']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('pickup_address', 'like', "%{$search}%")
                    ->orWhere('dropoff_address', 'like', "%{$search}%")
                    ->orWhereHas('passenger', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $rides = $query->latest()->paginate(20);

        $stats = [
            'total' => Ride::count(),
            'completed' => Ride::where('status', 'completed')->count(),
            'cancelled' => Ride::where('status', 'cancelled')->count(),
            'active' => Ride::whereIn('status', ['pending', 'accepted', 'in_progress'])->count(),
        ];

        return view('admin.rides.index', compact('rides', 'stats'));
    }

    public function show(Ride $ride)
    {
        $ride->load(['passenger', 'driver.user', 'transactions']);

        return view('admin.rides.show', compact('ride'));
    }
}
