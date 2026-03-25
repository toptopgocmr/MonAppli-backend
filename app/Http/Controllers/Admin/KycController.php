<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DriverProfile;
use Illuminate\Http\Request;

class KycController extends Controller
{
    public function index(Request $request)
    {
        $query = DriverProfile::with('user');

        // Filter by KYC status
        $status = $request->get('status', 'pending');
        $query->where('kyc_status', $status);

        $drivers = $query->latest()->paginate(20);

        return view('admin.kyc.index', compact('drivers', 'status'));
    }

    public function review(DriverProfile $driver)
    {
        $driver->load('user');

        return view('admin.kyc.review', compact('driver'));
    }

    public function approve(DriverProfile $driver)
    {
        $driver->update([
            'kyc_status' => 'approved',
            'is_verified' => true,
            'kyc_reviewed_at' => now(),
            'kyc_reviewed_by' => auth()->id(),
        ]);

        // Notify driver
        // $driver->user->notify(new KycApproved());

        return redirect()->route('admin.kyc')
            ->with('success', 'Chauffeur approuvé avec succès');
    }

    public function reject(Request $request, DriverProfile $driver)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $driver->update([
            'kyc_status' => 'rejected',
            'is_verified' => false,
            'kyc_rejection_reason' => $request->rejection_reason,
            'kyc_reviewed_at' => now(),
            'kyc_reviewed_by' => auth()->id(),
        ]);

        // Notify driver
        // $driver->user->notify(new KycRejected($request->rejection_reason));

        return redirect()->route('admin.kyc')
            ->with('success', 'Chauffeur rejeté');
    }
}
