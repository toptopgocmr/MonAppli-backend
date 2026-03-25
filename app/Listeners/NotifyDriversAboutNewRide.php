<?php

namespace App\Listeners;

use App\Events\RideCreated;
use App\Models\DriverProfile;
use App\Notifications\NewRideRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyDriversAboutNewRide implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(RideCreated $event): void
    {
        $ride = $event->ride;

        // Find available drivers nearby
        $drivers = DriverProfile::query()
            ->with('user')
            ->available()
            ->kycApproved()
            ->where('vehicle_type', $ride->vehicle_type)
            ->nearby($ride->pickup_latitude, $ride->pickup_longitude, 10)
            ->limit(15)
            ->get();

        foreach ($drivers as $driverProfile) {
            try {
                $driverProfile->user->notify(new NewRideRequest($ride));
            } catch (\Exception $e) {
                \Log::error('Failed to notify driver about new ride', [
                    'driver_id' => $driverProfile->user_id,
                    'ride_id' => $ride->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        \Log::info("Notified {$drivers->count()} drivers about new ride", [
            'ride_id' => $ride->id,
        ]);
    }
}
