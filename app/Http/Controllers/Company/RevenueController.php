<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Driver\Driver;
use App\Models\Trip;
use Illuminate\Http\Request;

class RevenueController extends Controller
{
    public function index(Request $request)
    {
        $company   = auth('company')->user();
        $driverIds = Driver::where('company_id', $company->id)->pluck('id');

        $year  = $request->get('year',  now()->year);
        $month = $request->get('month', null);

        $query = Trip::whereIn('driver_id', $driverIds)->where('status', 'completed');

        if ($month) {
            $query->whereMonth('created_at', $month)->whereYear('created_at', $year);
        } else {
            $query->whereYear('created_at', $year);
        }

        $totalRevenue    = $query->sum('price');
        $commission      = $totalRevenue * ($company->commission_rate / 100);
        $netRevenue      = $totalRevenue - $commission;
        $totalTrips      = $query->count();
        $avgPerTrip      = $totalTrips > 0 ? $totalRevenue / $totalTrips : 0;

        // Revenus par mois pour le graphique
        $monthlyRevenue = Trip::whereIn('driver_id', $driverIds)
            ->where('status', 'completed')
            ->whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, SUM(price) as total, COUNT(*) as trips')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Top chauffeurs
        $topDrivers = Trip::whereIn('driver_id', $driverIds)
            ->where('status', 'completed')
            ->whereYear('created_at', $year)
            ->selectRaw('driver_id, SUM(price) as total, COUNT(*) as trips')
            ->groupBy('driver_id')
            ->with('driver')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return view('company.revenus.index', compact(
            'totalRevenue', 'commission', 'netRevenue', 'totalTrips',
            'avgPerTrip', 'monthlyRevenue', 'topDrivers', 'year', 'month', 'company'
        ));
    }
}
