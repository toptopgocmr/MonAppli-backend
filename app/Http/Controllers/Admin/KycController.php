<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver\Driver;
use Illuminate\Http\Request;

class KycController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');

        $drivers = Driver::where('status', $status)
            ->latest()
            ->paginate(20);

        return view('admin.kyc.index', compact('drivers', 'status'));
    }

    public function review(Driver $driver)
    {
        return view('admin.kyc.review', compact('driver'));
    }

    public function approve(Driver $driver)
    {
        $driver->update(['status' => 'approved']);

        return redirect()->route('admin.kyc.index', ['status' => 'pending'])
            ->with('success', 'Chauffeur approuvé avec succès.');
    }

    public function reject(Request $request, Driver $driver)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $driver->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return redirect()->route('admin.kyc.index', ['status' => 'pending'])
            ->with('success', 'Chauffeur rejeté.');
    }
}
