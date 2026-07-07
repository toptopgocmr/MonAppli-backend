<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Driver\Driver;
use App\Models\Trip;

class DashboardController extends Controller
{
    public function index()
    {
        // ✅ Résout la société pour le compte principal ET pour un agent
        // connecté (auth('company')->user() renvoie null pour un agent,
        // ce qui faisait planter le dashboard — première page vue après
        // connexion — pour absolument tous les agents).
        $company = \App\Support\CompanyContext::company();

        $totalDrivers  = Driver::where('company_id', $company->id)->count();
        $activeDrivers = Driver::where('company_id', $company->id)
                               ->where('driver_status', 'online')->count();
        $approvedDrivers = Driver::where('company_id', $company->id)
                                 ->where('status', 'approved')->count();

        // Courses des chauffeurs de la société
        $driverIds = Driver::where('company_id', $company->id)->pluck('id');

        $totalTrips = Trip::whereIn('driver_id', $driverIds)->count();
        $tripsThisMonth = Trip::whereIn('driver_id', $driverIds)
                              ->whereMonth('created_at', now()->month)
                              ->whereYear('created_at', now()->year)
                              ->count();

        $revenueThisMonth = Trip::whereIn('driver_id', $driverIds)
                                ->whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)
                                ->where('status', 'completed')
                                ->sum('amount');

        $recentDrivers = Driver::where('company_id', $company->id)
                               ->orderBy('created_at', 'desc')
                               ->limit(5)->get();

        $recentTrips = Trip::whereIn('driver_id', $driverIds)
                           ->with('driver')
                           ->orderBy('created_at', 'desc')
                           ->limit(5)->get();

        return view('company.dashboard', compact(
            'company', 'totalDrivers', 'activeDrivers', 'approvedDrivers',
            'totalTrips', 'tripsThisMonth', 'revenueThisMonth',
            'recentDrivers', 'recentTrips'
        ));
    }
}
