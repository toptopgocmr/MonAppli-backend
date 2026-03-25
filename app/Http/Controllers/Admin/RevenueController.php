<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Driver;
use App\Models\User;
use App\Models\Country;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RevenueController extends Controller
{
    // ─── Vue principale Blade ─────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = $this->buildQuery($request);

        $data = $query
            ->with(['driver.user', 'user'])
            ->orderBy('earned_at', 'desc')
            ->paginate(15);

        return view('admin.revenus.index', [
            'kpis'        => $this->getKpis(),
            'data'        => $data,
            'filters'     => $this->getFilterOptions(),
            'total_query' => $this->buildQuery($request)->sum('montant_commission'),
        ]);
    }

    // ─── Stats JSON ───────────────────────────────────────────────────────────
    public function stats(Request $request)
    {
        $period = $request->get('period', 'month');

        $format = match($period) {
            'day'   => '%Y-%m-%d %H:00',
            'week'  => '%Y-%m-%d',
            'month' => '%Y-%m-%d',
            'year'  => '%Y-%m',
            default => '%Y-%m-%d',
        };

        [$start, $end] = $this->getPeriodDates($period);

        $data = Commission::query()
            ->select(
                DB::raw("DATE_FORMAT(earned_at, '{$format}') as label"),
                DB::raw('SUM(montant_commission) as total'),
                DB::raw('COUNT(*) as nb_courses')
            )
            ->whereBetween('earned_at', [$start, $end])
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        return response()->json([
            'period' => $period,
            'data'   => $data,
            'total'  => $data->sum('total'),
        ]);
    }

    public function byCountry(Request $request)
    {
        $data = $this->buildQuery($request)
            ->select('country_id', DB::raw('SUM(montant_commission) as total_revenus'), DB::raw('COUNT(*) as nb_courses'))
            ->groupBy('country_id')
            ->orderByDesc('total_revenus')
            ->get();

        return response()->json($data);
    }

    public function byCity(Request $request)
    {
        $data = $this->buildQuery($request)
            ->select('city_id', 'country_id', DB::raw('SUM(montant_commission) as total_revenus'), DB::raw('COUNT(*) as nb_courses'))
            ->groupBy('city_id', 'country_id')
            ->orderByDesc('total_revenus')
            ->get();

        return response()->json($data);
    }

    public function byDriver(Request $request)
    {
        $data = $this->buildQuery($request)
            ->select('driver_id', DB::raw('SUM(montant_commission) as total_revenus'), DB::raw('SUM(montant_course) as total_courses'), DB::raw('COUNT(*) as nb_courses'))
            ->with('driver.user')
            ->groupBy('driver_id')
            ->orderByDesc('total_revenus')
            ->get();

        return response()->json($data);
    }

    public function byClient(Request $request)
    {
        $data = $this->buildQuery($request)
            ->select('user_id', DB::raw('SUM(montant_commission) as total_revenus'), DB::raw('SUM(montant_course) as total_courses'), DB::raw('COUNT(*) as nb_courses'))
            ->with('user')
            ->groupBy('user_id')
            ->orderByDesc('total_revenus')
            ->get();

        return response()->json($data);
    }

    // ─── Export CSV ───────────────────────────────────────────────────────────
    public function export(Request $request): StreamedResponse
    {
        $commissions = $this->buildQuery($request)
            ->with(['course', 'driver.user', 'user'])
            ->orderBy('earned_at', 'desc')
            ->get();

        $filename = 'TopTopGo_Revenus_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($commissions) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'ID', 'Date', 'Chauffeur', 'Téléphone Chauffeur',
                'Client', 'Téléphone Client',
                'Montant Course', 'Devise', 'Taux (%)', 'Commission',
            ], ';');

            foreach ($commissions as $c) {
                fputcsv($handle, [
                    $c->id,
                    $c->earned_at?->format('d/m/Y H:i'),
                    $c->driver?->user?->name ?? '-',
                    $c->driver?->user?->phone ?? '-',
                    $c->user?->name ?? '-',
                    $c->user?->phone ?? '-',
                    number_format($c->montant_course, 2, '.', ''),
                    $c->currency,
                    number_format($c->taux_applique, 2, '.', ''),
                    number_format($c->montant_commission, 2, '.', ''),
                ], ';');
            }

            fclose($handle);
        }, 200, $headers);
    }

    // ─── Helpers privés ───────────────────────────────────────────────────────
    private function buildQuery(Request $request)
    {
        $query = Commission::query();

        if ($request->filled('date_start')) {
            $query->where('earned_at', '>=', $request->date_start . ' 00:00:00');
        }
        if ($request->filled('date_end')) {
            $query->where('earned_at', '<=', $request->date_end . ' 23:59:59');
        }
        if ($request->filled('period') && ! $request->filled('date_start')) {
            [$start, $end] = $this->getPeriodDates($request->period);
            $query->whereBetween('earned_at', [$start, $end]);
        }
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }
        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }
        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('montant_min')) {
            $query->where('montant_commission', '>=', $request->montant_min);
        }
        if ($request->filled('montant_max')) {
            $query->where('montant_commission', '<=', $request->montant_max);
        }

        return $query;
    }

    private function getKpis(): array
    {
        $now = now();

        return [
            'today'           => Commission::whereDate('earned_at', $now->toDateString())->sum('montant_commission'),
            'this_week'       => Commission::whereBetween('earned_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])->sum('montant_commission'),
            'this_month'      => Commission::whereYear('earned_at', $now->year)->whereMonth('earned_at', $now->month)->sum('montant_commission'),
            'this_year'       => Commission::whereYear('earned_at', $now->year)->sum('montant_commission'),
            'total_all'       => Commission::sum('montant_commission'),
            'yesterday'       => Commission::whereDate('earned_at', $now->copy()->subDay()->toDateString())->sum('montant_commission'),
            'last_week_total' => Commission::whereBetween('earned_at', [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()])->sum('montant_commission'),
            'last_month'      => Commission::whereYear('earned_at', $now->copy()->subMonth()->year)->whereMonth('earned_at', $now->copy()->subMonth()->month)->sum('montant_commission'),
        ];
    }

    private function getFilterOptions(): array
    {
        $countryIds = Commission::distinct()->whereNotNull('country_id')->pluck('country_id');
        $cityIds    = Commission::distinct()->whereNotNull('city_id')->pluck('city_id');
        $driverIds  = Commission::distinct()->whereNotNull('driver_id')->pluck('driver_id');
        $userIds    = Commission::distinct()->whereNotNull('user_id')->pluck('user_id');

        return [
            'countries' => $countryIds->isNotEmpty() ? Country::whereIn('id', $countryIds)->get() : collect(),
            'cities'    => $cityIds->isNotEmpty() ? City::whereIn('id', $cityIds)->get() : collect(),
            'drivers'   => $driverIds->isNotEmpty() ? Driver::whereIn('id', $driverIds)->with('user')->get() : collect(),
            'clients'   => $userIds->isNotEmpty() ? User::whereIn('id', $userIds)->get() : collect(),
        ];
    }

    private function getPeriodDates(string $period): array
    {
        $now = now();
        return match($period) {
            'day'   => [$now->copy()->startOfDay(),   $now->copy()->endOfDay()],
            'week'  => [$now->copy()->startOfWeek(),  $now->copy()->endOfWeek()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'year'  => [$now->copy()->startOfYear(),  $now->copy()->endOfYear()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }
}