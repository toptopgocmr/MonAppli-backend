<?php

namespace App\Console\Commands;

use App\Models\Ride;
use App\Notifications\RideStatusUpdate;
use Illuminate\Console\Command;

class CleanupAbandonedRides extends Command
{
    protected $signature = 'rides:cleanup-abandoned';

    protected $description = 'Cancel rides that have not been accepted within the time limit';

    public function handle(): int
    {
        $maxWaitMinutes = config('rides.max_wait_time_minutes', 15);

        $abandonedRides = Ride::where('status', Ride::STATUS_PENDING)
            ->whereNull('driver_id')
            ->where('created_at', '<=', now()->subMinutes($maxWaitMinutes))
            ->get();

        $this->info("Found {$abandonedRides->count()} abandoned rides...");

        foreach ($abandonedRides as $ride) {
            $ride->update([
                'status' => Ride::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => 'Aucun chauffeur disponible',
            ]);

            // Notify passenger
            try {
                $ride->passenger->notify(new RideStatusUpdate($ride, 'no_driver'));
            } catch (\Exception $e) {
                $this->error("Failed to notify passenger for ride {$ride->id}");
            }

            $this->line("Cancelled ride {$ride->id}");
        }

        $this->info('Done.');

        return Command::SUCCESS;
    }
}
