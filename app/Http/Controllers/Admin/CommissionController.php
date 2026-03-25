<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trip;
use App\Models\CommissionSetting;
use App\Models\Driver\Driver;
use Carbon\Carbon;

class CommissionController extends Controller
{
    /**
     * Tableau de bord des commissions
     */
    public function index(Request $request)
    {
        // ── Taux actuel ──────────────────────────────────────────
        $currentRate = CommissionSetting::currentRate();
        $rateHistory = CommissionSetting::latest()->take(5)->get();

        // ── Période sélectionnée ──────────────────────────────────
        $period = $request->get('period', 'month');
        [$startDate, $endDate] = $this->getDateRange($period, $request);

        // ── Requête de base ───────────────────────────────────────
        $query = Trip::where('status', 'completed')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->with(['driver', 'user']);

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }
        if ($request->filled('country')) {
            $query->whereHas('driver', fn($q) => $q->where('vehicle_country', $request->country));
        }
        if ($request->filled('city')) {
            $query->whereHas('driver', fn($q) => $q->where('vehicle_city', $request->city));
        }
        if ($request->filled('vehicle_type')) {
            $query->where('vehicle_type', $request->vehicle_type);
        }

        // ── Stats globales ────────────────────────────────────────
        $statsQuery      = clone $query;
        $totalRevenue    = $statsQuery->sum('amount');
        $totalCommission = (clone $query)->sum('commission');
        $totalDriverNet  = (clone $query)->sum('driver_net');
        $totalTrips      = (clone $query)->count();

        // ── Évolution vs période précédente ──────────────────────
        $diffDays      = max(1, $startDate->diffInDays($endDate));
        $prevStart     = $startDate->copy()->subDays($diffDays);
        $prevEnd       = $startDate->copy()->subSecond();
        $prevCommission = Trip::where('status', 'completed')
            ->whereBetween('completed_at', [$prevStart, $prevEnd])
            ->sum('commission');
        $commissionEvolution = $prevCommission > 0
            ? round((($totalCommission - $prevCommission) / $prevCommission) * 100, 1)
            : 0;

        // ── Stats journalières pour graphique ────────────────────
        $dailyStats = Trip::where('status', 'completed')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->when($request->filled('driver_id'), fn($q) => $q->where('driver_id', $request->driver_id))
            ->selectRaw('DATE(completed_at) as date, SUM(amount) as revenue, SUM(commission) as commission, COUNT(*) as trips')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // ── Top 10 chauffeurs ─────────────────────────────────────
        $topDrivers = Trip::where('status', 'completed')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->when($request->filled('country'), fn($q) => $q->whereHas('driver', fn($d) => $d->where('vehicle_country', $request->country)))
            ->when($request->filled('city'), fn($q) => $q->whereHas('driver', fn($d) => $d->where('vehicle_city', $request->city)))
            ->selectRaw('driver_id, SUM(amount) as total_amount, SUM(commission) as total_commission, SUM(driver_net) as total_net, COUNT(*) as trips_count')
            ->groupBy('driver_id')
            ->orderByDesc('total_commission')
            ->with('driver')
            ->take(10)
            ->get();

        // ── Liste paginée ─────────────────────────────────────────
        $trips = (clone $query)->latest('completed_at')->paginate(15);

        // ── Données filtres ───────────────────────────────────────
        $drivers   = Driver::orderBy('first_name')->get();
        $countries = Driver::distinct()->pluck('vehicle_country')->filter()->sort()->values();
        $cities    = Driver::distinct()->pluck('vehicle_city')->filter()->sort()->values();

        return view('admin.commissions.index', compact(
            'currentRate', 'rateHistory',
            'period', 'startDate', 'endDate',
            'totalRevenue', 'totalCommission', 'totalDriverNet', 'totalTrips',
            'commissionEvolution', 'prevCommission',
            'dailyStats', 'topDrivers', 'trips',
            'drivers', 'countries', 'cities'
        ));
    }

    /**
     * Mettre à jour le taux de commission
     */
    public function updateRate(Request $request)
    {
        $request->validate([
            'rate'        => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        CommissionSetting::create([
            'rate'        => $request->rate,
            'description' => $request->description ?? 'Mise à jour du taux',
            'created_by'  => session('admin_id'),
        ]);

        return redirect()->route('admin.commissions.index')
            ->with('success', "Taux de commission mis à jour à {$request->rate}% !");
    }

    /**
     * Export CSV
     */
    public function export(Request $request)
    {
        $period = $request->get('period', 'month');
        [$startDate, $endDate] = $this->getDateRange($period, $request);

        $trips = Trip::where('status', 'completed')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->when($request->filled('driver_id'), fn($q) => $q->where('driver_id', $request->driver_id))
            ->when($request->filled('country'), fn($q) => $q->whereHas('driver', fn($d) => $d->where('vehicle_country', $request->country)))
            ->when($request->filled('city'), fn($q) => $q->whereHas('driver', fn($d) => $d->where('vehicle_city', $request->city)))
            ->with(['driver', 'user'])
            ->latest('completed_at')
            ->get();

        $filename = "commissions_{$period}_" . now()->format('Ymd') . ".csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($trips) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM Excel

            fputcsv($file, [
                'Trip ID', 'Date', 'Chauffeur', 'Téléphone', 'Pays', 'Ville',
                'Type véhicule', 'Distance (km)', 'Montant (XAF)',
                'Commission (XAF)', 'Net Chauffeur (XAF)',
            ], ';');

            foreach ($trips as $trip) {
                fputcsv($file, [
                    $trip->id,
                    $trip->completed_at?->format('d/m/Y H:i'),
                    ($trip->driver->first_name ?? '') . ' ' . ($trip->driver->last_name ?? ''),
                    $trip->driver->phone ?? '',
                    $trip->driver->vehicle_country ?? '',
                    $trip->driver->vehicle_city ?? '',
                    $trip->vehicle_type,
                    $trip->distance_km,
                    number_format($trip->amount, 0, ',', ' '),
                    number_format($trip->commission, 0, ',', ' '),
                    number_format($trip->driver_net, 0, ',', ' '),
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Plage de dates selon période
     */
    private function getDateRange(string $period, Request $request): array
    {
        return match ($period) {
            'day'    => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            'week'   => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'month'  => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'year'   => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            'custom' => [
                Carbon::parse($request->get('start', now()->startOfMonth()->toDateString()))->startOfDay(),
                Carbon::parse($request->get('end', now()->toDateString()))->endOfDay(),
            ],
            default  => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
        };
    }
}