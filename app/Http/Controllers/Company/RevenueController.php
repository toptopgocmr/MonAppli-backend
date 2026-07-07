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
        // ✅ Résout la société pour le compte principal ET pour un agent
        // connecté (auth('company')->user() renvoie null pour un agent).
        $company   = \App\Support\CompanyContext::company();
        $driverIds = Driver::where('company_id', $company->id)->pluck('id');

        // Revenus ce mois
        $revenueThisMonth = Trip::whereIn('driver_id', $driverIds)
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $tripsThisMonth = Trip::whereIn('driver_id', $driverIds)
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Revenus total
        $revenueTotal = Trip::whereIn('driver_id', $driverIds)
            ->where('status', 'completed')
            ->sum('amount');

        $tripsTotal = Trip::whereIn('driver_id', $driverIds)
            ->where('status', 'completed')
            ->count();

        // Évolution mensuelle (12 derniers mois)
        $monthlyRevenue = Trip::whereIn('driver_id', $driverIds)
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subMonths(12)->startOfMonth())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total, COUNT(*) as trips")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Top chauffeurs ce mois
        $topDriversRaw = Trip::whereIn('driver_id', $driverIds)
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->selectRaw('driver_id, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('driver_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $topDrivers = $topDriversRaw->map(function ($row) {
            return [
                'driver' => Driver::find($row->driver_id),
                'total'  => $row->total,
                'count'  => $row->count,
            ];
        })->filter(fn($item) => $item['driver'] !== null)->values();

        return view('company.revenus.index', compact(
            'company',
            'revenueThisMonth', 'tripsThisMonth',
            'revenueTotal',     'tripsTotal',
            'monthlyRevenue',   'topDrivers'
        ));
    }
}
