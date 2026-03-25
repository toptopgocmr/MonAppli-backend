<?php

namespace App\Notifications;

use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TripAcceptedNotification extends Notification
{
    use Queueable;

    public function __construct(public Trip $trip) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'        => 'trip_accepted',
            'trip_id'     => $this->trip->id,
            'driver_name' => $this->trip->driver->first_name . ' ' . $this->trip->driver->last_name,
            'vehicle'     => $this->trip->driver->vehicle_plate,
            'message'     => 'Votre course a été acceptée par un chauffeur.',
        ];
    }
}
