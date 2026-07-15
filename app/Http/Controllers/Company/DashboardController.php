<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Booking;
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

        // ✅ FIX : le CA/paiements affichés au dashboard société ne
        // remontaient jamais. La requête se basait sur Trip.status ===
        // 'completed', mais ce statut n'est posé que quand le CHAUFFEUR
        // clôture manuellement la course (TripService::complete /
        // DriverTripController) — une étape séparée et souvent postérieure
        // au paiement du client. Un client peut donc avoir payé sa place
        // (Mobile Money confirmé) sans que le trajet ne soit jamais marqué
        // 'completed', et ce paiement n'apparaissait alors nulle part. La
        // vraie source de vérité pour "payé" est Booking::isPaid()/paid()
        // (voir app/Models/Booking.php), qui couvre aussi les trajets
        // d'itinéraires société pas encore assignés à un chauffeur
        // (Trip.company_id, cf. ReservationController::index()).
        $companyTripIds = Trip::where(function ($q) use ($driverIds, $company) {
                $q->whereIn('driver_id', $driverIds)
                  ->orWhere('company_id', $company->id);
            })->pluck('id');

        $paidBookings = fn () => Booking::whereIn('trip_id', $companyTripIds)->paid();

        $tripsThisMonth = $paidBookings()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $revenueThisMonth = $paidBookings()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
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
