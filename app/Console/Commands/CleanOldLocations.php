<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DriverLocation;

class CleanOldLocations extends Command
{
    protected $signature   = 'toptopgo:clean-locations {--days=7}';
    protected $description = 'Supprime les anciennes positions GPS des chauffeurs';

    public function handle(): void
    {
        $days    = (int) $this->option('days');
        $deleted = DriverLocation::where('created_at', '<', now()->subDays($days))->delete();
        $this->info("Supprimé {$deleted} anciennes positions GPS (> {$days} jours).");
    }
}
