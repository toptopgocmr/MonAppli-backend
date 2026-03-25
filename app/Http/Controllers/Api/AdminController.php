<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Ride;
use App\Models\Transaction;
use App\Models\DriverProfile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function dashboardStats(): JsonResponse
    {
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        // Users stats
        $totalUsers = User::count();
        $totalPassengers = User::where('role', 'passenger')->count();
        $totalDrivers = User::where('role', 'driver')->count();
        $activeDrivers = DriverProfile::where('is_online', true)->count();
        $pendingKyc = DriverProfile::where('kyc_status', 'pending')->count();

        // Rides stats
        $totalRides = Ride::count();
        $completedRides = Ride::where('status', 'completed')->count();
        $todayRides = Ride::whereDate('created_at', $today)->count();
        $todayCompletedRides = Ride::where('status', 'completed')
            ->whereDate('completed_at', $today)
            ->count();

        // Revenue stats
        $totalRevenue = Transaction::where('type', 'ride_payment')
            ->where('status', 'completed')
            ->sum('commission');

        $todayRevenue = Transaction::where('type', 'ride_payment')
            ->where('status', 'completed')
            ->whereDate('completed_at', $today)
            ->sum('commission');

        $thisMonthRevenue = Transaction::where('type', 'ride_payment')
            ->where('status', 'completed')
            ->where('completed_at', '>=', $thisMonth)
            ->sum('commission');

        $lastMonthRevenue = Transaction::where('type', 'ride_payment')
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$lastMonth, $thisMonth])
            ->sum('commission');

        // Transaction stats
        $totalTransactions = Transaction::where('status', 'completed')->sum('amount');
        $pendingWithdrawals = Transaction::where('type', 'withdrawal')
            ->whereIn('status', ['pending', 'processing'])
            ->sum('amount');

        // Recent activity
        $recentRides = Ride::with(['passenger:id,first_name,last_name', 'driver:id,first_name,last_name'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'passenger_id', 'driver_id', 'pickup_address', 'dropoff_address', 'price', 'status', 'created_at']);

        $recentTransactions = Transaction::with('user:id,first_name,last_name')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'user_id', 'type', 'amount', 'status', 'provider', 'created_at']);

        // Charts data - Last 7 days
        $ridesPerDay = Ride::where('created_at', '>=', now()->subDays(7))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $revenuePerDay = Transaction::where('type', 'ride_payment')
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->subDays(7))
            ->select(DB::raw('DATE(completed_at) as date'), DB::raw('SUM(commission) as amount'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $this->success([
            'users' => [
                'total' => $totalUsers,
                'passengers' => $totalPassengers,
                'drivers' => $totalDrivers,
                'active_drivers' => $activeDrivers,
                'pending_kyc' => $pendingKyc,
            ],
            'rides' => [
                'total' => $totalRides,
                'completed' => $completedRides,
                'today' => $todayRides,
                'today_completed' => $todayCompletedRides,
                'completion_rate' => $totalRides > 0 ? round(($completedRides / $totalRides) * 100, 1) : 0,
            ],
            'revenue' => [
                'total' => $totalRevenue,
                'today' => $todayRevenue,
                'this_month' => $thisMonthRevenue,
                'last_month' => $lastMonthRevenue,
                'growth' => $lastMonthRevenue > 0
                    ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
                    : 0,
            ],
            'transactions' => [
                'total_volume' => $totalTransactions,
                'pending_withdrawals' => $pendingWithdrawals,
            ],
            'charts' => [
                'rides_per_day' => $ridesPerDay,
                'revenue_per_day' => $revenuePerDay,
            ],
            'recent' => [
                'rides' => $recentRides,
                'transactions' => $recentTransactions,
            ],
        ]);
    }

    /**
     * Get payment providers statistics
     */
    public function paymentStats(): JsonResponse
    {
        $providers = ['peex', 'mtn_momo', 'airtel_money', 'stripe'];

        $stats = [];
        foreach ($providers as $provider) {
            $transactions = Transaction::where('provider', $provider)
                ->where('status', 'completed');

            $stats[$provider] = [
                'count' => $transactions->count(),
                'volume' => $transactions->sum('amount'),
            ];
        }

        $totalVolume = array_sum(array_column($stats, 'volume'));

        foreach ($stats as $provider => &$data) {
            $data['percentage'] = $totalVolume > 0
                ? round(($data['volume'] / $totalVolume) * 100, 1)
                : 0;
        }

        return $this->success([
            'by_provider' => $stats,
            'total_volume' => $totalVolume,
        ]);
    }

    /**
     * Get geographic statistics
     */
    public function geoStats(): JsonResponse
    {
        // Most popular pickup locations
        $popularPickups = Ride::select(
            DB::raw('ROUND(pickup_latitude, 2) as lat'),
            DB::raw('ROUND(pickup_longitude, 2) as lng'),
            DB::raw('COUNT(*) as count')
        )
            ->groupBy('lat', 'lng')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Most popular dropoff locations
        $popularDropoffs = Ride::select(
            DB::raw('ROUND(dropoff_latitude, 2) as lat'),
            DB::raw('ROUND(dropoff_longitude, 2) as lng'),
            DB::raw('COUNT(*) as count')
        )
            ->groupBy('lat', 'lng')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Active drivers locations
        $driverLocations = DriverProfile::where('is_online', true)
            ->select('current_latitude as lat', 'current_longitude as lng')
            ->get();

        return $this->success([
            'popular_pickups' => $popularPickups,
            'popular_dropoffs' => $popularDropoffs,
            'active_drivers' => $driverLocations,
        ]);
    }
}
