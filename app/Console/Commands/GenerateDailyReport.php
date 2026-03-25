<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Trip;
use App\Models\Payment;
use App\Models\User\User;
use App\Models\Driver\Driver;
use Illuminate\Support\Facades\Log;

class GenerateDailyReport extends Command
{
    protected $signature   = 'toptopgo:daily-report';
    protected $description = 'Génère le rapport quotidien TopTopGo';

    public function handle(): void
    {
        $date = now()->subDay()->toDateString();

        $report = [
            'date'            => $date,
            'new_users'       => User::whereDate('created_at', $date)->count(),
            'new_drivers'     => Driver::whereDate('created_at', $date)->count(),
            'trips_total'     => Trip::whereDate('created_at', $date)->count(),
            'trips_completed' => Trip::where('status', 'completed')->whereDate('created_at', $date)->count(),
            'trips_cancelled' => Trip::where('status', 'cancelled')->whereDate('created_at', $date)->count(),
            'revenue'         => Payment::where('status', 'success')->whereDate('created_at', $date)->sum('amount'),
            'commission'      => Payment::where('status', 'success')->whereDate('created_at', $date)->sum('commission'),
        ];

        $this->table(array_keys($report), [array_values($report)]);

        Log::channel('daily')->info('Daily report', $report);

        $this->info('Rapport généré pour ' . $date);
    }
}
