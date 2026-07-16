<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User\User;
use App\Models\Driver\Driver;
use App\Models\Admin\AdminUser;
use App\Models\Company;
use App\Models\Trip;
use App\Models\Payment;
use App\Models\SosAlert;
use App\Models\CompanyAgent;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $yesterday     = today()->subDay();
        $thisMonth     = now()->month;
        $thisYear      = now()->year;
        $lastMonthDate = now()->subMonth();

        $stats = [
            'total_users'       => User::count(),
            'new_users_today'   => User::whereDate('created_at', today())->count(),
            'total_companies'   => Company::count(),
            'active_drivers'    => Driver::where('status', 'approved')->count(),
            'online_drivers'    => Driver::where('driver_status', 'online')->count(),
            'today_rides'       => Trip::whereDate('created_at', today())->count(),
            'active_rides'      => Trip::where('status', 'in_progress')->count(),
            'today_revenue'     => Payment::where('status', 'success')->whereDate('created_at', today())->sum('amount'),
            'today_commission'  => Payment::where('status', 'success')->whereDate('created_at', today())->sum('commission'),
            'total_commission'  => Payment::where('status', 'success')->sum('commission'),
            'sos_active'        => SosAlert::where('status', 'active')->count(),
            'total_admins'      => AdminUser::count(),

            // ── Paiements (succès / rejetés / annulés) ──
            'payments_success'   => Payment::where('status', 'success')->count(),
            'payments_failed'    => Payment::where('status', 'failed')->count(),
            'payments_cancelled' => Payment::where('status', 'cancelled')->count(),
            'payments_pending'   => Payment::where('status', 'pending')->count(),

            // ── Comptes agents des sociétés ──
            'company_agents_total'  => CompanyAgent::count(),
            'company_agents_active' => CompanyAgent::where('status', 'active')->count(),

            // ── Comparaisons ──
            'users_this_month'        => User::whereMonth('created_at', $thisMonth)->whereYear('created_at', $thisYear)->count(),
            'users_last_month'        => User::whereMonth('created_at', $lastMonthDate->month)->whereYear('created_at', $lastMonthDate->year)->count(),

            'companies_this_month'    => Company::whereMonth('created_at', $thisMonth)->whereYear('created_at', $thisYear)->count(),
            'companies_last_month'    => Company::whereMonth('created_at', $lastMonthDate->month)->whereYear('created_at', $lastMonthDate->year)->count(),

            'drivers_pending'         => Driver::where('status', 'pending')->count(),
            'drivers_last_week'       => Driver::where('status', 'approved')->whereDate('created_at', '<', today()->subDays(7))->count(),

            'rides_yesterday'         => Trip::whereDate('created_at', $yesterday)->count(),
            'rides_this_week'         => Trip::whereDate('created_at', '>=', today()->subDays(6))->count(),
            'rides_last_week'         => Trip::whereBetween('created_at', [today()->subDays(13), today()->subDays(7)])->count(),

            'revenue_yesterday'       => Payment::where('status', 'success')->whereDate('created_at', $yesterday)->sum('amount'),

            'commission_this_month'   => Payment::where('status', 'success')->whereMonth('created_at', $thisMonth)->whereYear('created_at', $thisYear)->sum('commission'),
            'commission_last_month'   => Payment::where('status', 'success')->whereMonth('created_at', $lastMonthDate->month)->whereYear('created_at', $lastMonthDate->year)->sum('commission'),

            'sos_week'                => SosAlert::whereDate('created_at', '>=', today()->subDays(6))->count(),
            'sos_treated_week'        => SosAlert::where('status', 'treated')->whereDate('treated_at', '>=', today()->subDays(6))->count(),
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