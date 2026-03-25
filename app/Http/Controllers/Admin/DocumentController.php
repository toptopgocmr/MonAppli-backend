<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Driver\Driver;
use App\Models\AdminLog;
use App\Notifications\DriverApprovedNotification;

class DocumentController extends Controller
{
    public function pending(Request $request)
    {
        $drivers = Driver::with('wallet')
            ->where('status', 'pending')
            ->orWhere(function ($q) {
                $q->where('status', 'approved')
                  ->where(function ($q2) {
                      $q2->whereNotNull('id_card_expiry_date')
                         ->whereDate('id_card_expiry_date', '<=', now()->addDays(30));
                  });
            })
            ->paginate(20);

        return response()->json($drivers);
    }

    public function expiring(Request $request)
    {
        $days = $request->days ?? 30;

        $drivers = Driver::where(function ($q) use ($days) {
            $q->whereDate('id_card_expiry_date', '<=', now()->addDays($days))
              ->orWhereDate('license_expiry_date', '<=', now()->addDays($days));
        })->where('status', 'approved')->get();

        return response()->json($drivers);
    }
}
