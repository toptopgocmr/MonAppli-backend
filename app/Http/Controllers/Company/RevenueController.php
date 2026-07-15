<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Booking;
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

        // ✅ FIX : mêmes causes qu'au dashboard société (voir
        // DashboardController::index()) — Trip.status === 'completed'
        // n'est posé que quand le chauffeur clôture la course, pas quand le
        // client paie. On se base désormais sur Booking::paid(), la vraie
        // source de vérité du paiement (Booking::isPaid()/scopePaid()),
        // pour tous les trajets de la société : ceux de ses chauffeurs ET
        // ceux issus de ses itinéraires programmés pas encore assignés
        // (Trip.company_id).
        $companyTripIds = Trip::where(function ($q) use ($driverIds, $company) {
                $q->whereIn('driver_id', $driverIds)
                  ->orWhere('company_id', $company->id);
            })->pluck('id');

        $paidBookings = fn () => Booking::whereIn('trip_id', $companyTripIds)->paid();

        // Revenus ce mois
        $revenueThisMonth = $paidBookings()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $tripsThisMonth = $paidBookings()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Revenus total
        $revenueTotal = $paidBookings()->sum('amount');
        $tripsTotal    = $paidBookings()->count();

        // Évolution mensuelle (12 derniers mois)
        $monthlyRevenue = $paidBookings()
            ->where('created_at', '>=', now()->subMonths(12)->startOfMonth())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total, COUNT(*) as trips")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Top chauffeurs ce mois — regroupement fait côté PHP (pas de JOIN
        // SQL) car le driver_id vit sur Trip, pas sur Booking.
        $topDrivers = $paidBookings()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->with('trip:id,driver_id')
            ->get()
            ->groupBy(fn ($booking) => $booking->trip?->driver_id)
            ->filter(fn ($group, $driverId) => $driverId !== null)
            ->map(fn ($group, $driverId) => [
                'driver' => Driver::find($driverId),
                'total'  => $group->sum('amount'),
                'count'  => $group->count(),
            ])
            ->filter(fn ($item) => $item['driver'] !== null)
            ->sortByDesc('total')
            ->take(5)
            ->values();

        return view('company.revenus.index', compact(
            'company',
            'revenueThisMonth', 'tripsThisMonth',
            'revenueTotal',     'tripsTotal',
            'monthlyRevenue',   'topDrivers'
        ));
    }
}
