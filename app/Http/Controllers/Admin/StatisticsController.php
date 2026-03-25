<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\Payment;
use App\Models\User\User;
use App\Models\Driver\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function overview(Request $request)
    {
        $from = $request->from ? now()->parse($request->from) : now()->subDays(30);
        $to   = $request->to   ? now()->parse($request->to)   : now();

        return response()->json([
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],

            'revenue' => [
                'total'       => Payment::where('status', 'success')->whereBetween('created_at', [$from, $to])->sum('amount'),
                'commission'  => Payment::where('status', 'success')->whereBetween('created_at', [$from, $to])->sum('commission'),
                'driver_payouts' => Payment::where('status', 'success')->whereBetween('created_at', [$from, $to])->sum('driver_net'),
            ],

            'trips' => [
                'total'      => Trip::whereBetween('created_at', [$from, $to])->count(),
                'completed'  => Trip::where('status', 'completed')->whereBetween('created_at', [$from, $to])->count(),
                'cancelled'  => Trip::where('status', 'cancelled')->whereBetween('created_at', [$from, $to])->count(),
                'by_type'    => Trip::whereBetween('created_at', [$from, $to])->groupBy('vehicle_type')->select('vehicle_type', DB::raw('count(*) as total'))->get(),
            ],

            'users' => [
                'new'   => User::whereBetween('created_at', [$from, $to])->count(),
                'total' => User::count(),
            ],

            'drivers' => [
                'new'      => Driver::whereBetween('created_at', [$from, $to])->count(),
                'approved' => Driver::where('status', 'approved')->count(),
                'pending'  => Driver::where('status', 'pending')->count(),
            ],

            'payments_by_method' => Payment::where('status', 'success')
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('method')
                ->select('method', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
                ->get(),
        ]);
    }

    public function daily(Request $request)
    {
        $days = $request->days ?? 30;

        $revenue = Payment::where('status', 'success')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('sum(amount) as total'), DB::raw('sum(commission) as commission'))
            ->orderBy('date')
            ->get();

        $trips = Trip::where('created_at', '>=', now()->subDays($days))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->orderBy('date')
            ->get();

        return response()->json([
            'revenue' => $revenue,
            'trips'   => $trips,
        ]);
    }

    public function topDrivers(Request $request)
    {
        $limit = $request->limit ?? 10;

        $drivers = Trip::where('status', 'completed')
            ->groupBy('driver_id')
            ->select('driver_id', DB::raw('count(*) as trips'), DB::raw('sum(driver_net) as earnings'))
            ->with('driver:id,first_name,last_name,vehicle_plate')
            ->orderByDesc('trips')
            ->limit($limit)
            ->get();

        return response()->json($drivers);
    }
}
