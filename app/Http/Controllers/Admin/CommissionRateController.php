<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CommissionRate;
use App\Models\Trip;
use App\Models\Driver\Driver;
use Carbon\Carbon;

class CommissionRateController extends Controller
{
    /**
     * Tableau de bord + liste des règles
     */
    public function index(Request $request)
    {
        // ── Règles de commission ──────────────────────────────────
        $globalRate   = CommissionRate::where('type', 'global')->where('is_active', true)->first();
        $countryRates = CommissionRate::where('type', 'country')->where('is_active', true)->with('driver')->get();
        $vehicleRates = CommissionRate::where('type', 'vehicle_type')->where('is_active', true)->get();
        $driverRates  = CommissionRate::where('type', 'driver')->where('is_active', true)->with('driver')->get();
        $allRates     = CommissionRate::with('driver')->latest()->get();

        // ── Période ───────────────────────────────────────────────
        $period = $request->get('period', 'month');
        [$startDate, $endDate] = $this->getDateRange($period, $request);

        // ── Requête de base ───────────────────────────────────────
        $query = Trip::where('status', 'completed')
            ->whereBetween('completed_at', [$startDate, $endDate]);

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }

        if ($request->filled('country')) {
            $query->whereHas('driver', function ($q) use ($request) {
                $q->where('vehicle_country', $request->country);
            });
        }

        if ($request->filled('city')) {
            $query->whereHas('driver', function ($q) use ($request) {
                $q->where('vehicle_city', $request->city);
            });
        }

        if ($request->filled('vehicle_type')) {
            $query->where('vehicle_type', $request->vehicle_type);
        }

        // ── Stats globales ────────────────────────────────────────
        $totalRevenue    = (clone $query)->sum('amount');
        $totalCommission = (clone $query)->sum('commission');
        $totalDriverNet  = (clone $query)->sum('driver_net');
        $totalTrips      = (clone $query)->count();

        // ── Évolution vs période précédente ──────────────────────
        $diffDays   = max(1, $startDate->diffInDays($endDate));
        $prevStart  = $startDate->copy()->subDays($diffDays);
        $prevEnd    = $startDate->copy()->subSecond();

        $prevCommission = Trip::where('status', 'completed')
            ->whereBetween('completed_at', [$prevStart, $prevEnd])
            ->sum('commission');

        $commissionEvolution = $prevCommission > 0
            ? round((($totalCommission - $prevCommission) / $prevCommission) * 100, 1)
            : 0;

        // ── Stats journalières ────────────────────────────────────
        $dailyStats = Trip::where('status', 'completed')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->when($request->filled('driver_id'), function ($q) use ($request) {
                $q->where('driver_id', $request->driver_id);
            })
            ->selectRaw('DATE(completed_at) as date,
                         SUM(amount) as revenue,
                         SUM(commission) as commission,
                         COUNT(*) as trips')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // ── Top chauffeurs ────────────────────────────────────────
        $topDrivers = Trip::where('status', 'completed')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->when($request->filled('country'), function ($q) use ($request) {
                $q->whereHas('driver', function ($d) use ($request) {
                    $d->where('vehicle_country', $request->country);
                });
            })
            ->when($request->filled('city'), function ($q) use ($request) {
                $q->whereHas('driver', function ($d) use ($request) {
                    $d->where('vehicle_city', $request->city);
                });
            })
            ->selectRaw('driver_id,
                         SUM(amount) as total_amount,
                         SUM(commission) as total_commission,
                         SUM(driver_net) as total_net,
                         COUNT(*) as trips_count')
            ->groupBy('driver_id')
            ->orderByDesc('total_commission')
            ->with('driver')
            ->take(10)
            ->get();

        // ── Liste paginée ─────────────────────────────────────────
        $trips = (clone $query)
            ->with(['driver', 'user'])
            ->latest('completed_at')
            ->paginate(15);

        // ── Données filtres ───────────────────────────────────────
        $drivers   = Driver::orderBy('first_name')->get();
        $countries = Driver::distinct()->pluck('vehicle_country')->filter()->sort()->values();
        $cities    = Driver::distinct()->pluck('vehicle_city')->filter()->sort()->values();

        return view('admin.commissions.index', compact(
            'globalRate',
            'countryRates',
            'vehicleRates',
            'driverRates',
            'allRates',
            'period',
            'startDate',
            'endDate',
            'totalRevenue',
            'totalCommission',
            'totalDriverNet',
            'totalTrips',
            'commissionEvolution',
            'prevCommission',
            'dailyStats',
            'topDrivers',
            'trips',
            'drivers',
            'countries',
            'cities'
        ));
    }


    /**
     * Créer une nouvelle règle
     */
    public function store(Request $request)
    {
        $request->validate([
            'type'         => 'required|in:global,country,vehicle_type,driver',
            'rate'         => 'required|numeric|min:0|max:100',
            'country'      => 'required_if:type,country|nullable|string',
            'vehicle_type' => 'required_if:type,vehicle_type|nullable|in:Standard,Confort,Van,PMR',
            'driver_id'    => 'required_if:type,driver|nullable|exists:drivers,id',
            'description'  => 'nullable|string|max:255',
        ]);

        // Désactiver ancien global
        if ($request->type === 'global') {
            CommissionRate::where('type', 'global')
                ->update(['is_active' => false]);
        }

        CommissionRate::updateOrCreate(
            [
                'type'         => $request->type,
                'country'      => $request->type === 'country' ? $request->country : null,
                'vehicle_type' => $request->type === 'vehicle_type' ? $request->vehicle_type : null,
                'driver_id'    => $request->type === 'driver' ? $request->driver_id : null,
            ],
            [
                'rate'        => $request->rate,
                'description' => $request->description,
                'is_active'   => true,
                'created_by'  => session('admin_id') ?? 1
            ]
        );

        return redirect()->route('admin.commission-rates.index')
            ->with('success', 'Taux enregistré avec succès');
    }


    /**
     * Mise à jour
     */
    public function update(Request $request, CommissionRate $commissionRate)
    {
        $request->validate([
            'rate' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $commissionRate->update([
            'rate' => $request->rate,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.commission-rates.index')
            ->with('success', 'Taux mis à jour');
    }


    /**
     * Suppression
     */
    public function destroy(CommissionRate $commissionRate)
    {
        if ($commissionRate->type === 'global') {
            return back()->with('error', 'Impossible de supprimer le taux global');
        }

        $commissionRate->delete();

        return back()->with('success', 'Supprimé');
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
            ->with(['driver','user'])
            ->latest('completed_at')
            ->get();

        $filename = "commissions.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
        ];

        $callback = function() use ($trips){

            $file = fopen('php://output','w');

            fputcsv($file,[
                'Trip',
                'Date',
                'Driver',
                'Amount',
                'Commission'
            ]);

            foreach($trips as $trip){

                fputcsv($file,[
                    $trip->id,
                    $trip->completed_at,
                    optional($trip->driver)->first_name,
                    $trip->amount,
                    $trip->commission
                ]);

            }

            fclose($file);

        };

        return response()->stream($callback,200,$headers);

    }


    private function getDateRange(string $period, Request $request): array
    {
        return match ($period) {

            'day' => [
                Carbon::today()->startOfDay(),
                Carbon::today()->endOfDay()
            ],

            'week' => [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ],

            'month' => [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ],

            'year' => [
                Carbon::now()->startOfYear(),
                Carbon::now()->endOfYear()
            ],

            'custom' => [
                Carbon::parse($request->start)->startOfDay(),
                Carbon::parse($request->end)->endOfDay()
            ],

            default => [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ]
        };
    }
}