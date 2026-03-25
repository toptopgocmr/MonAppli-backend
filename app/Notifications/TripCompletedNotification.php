<?php

namespace App\Notifications;

use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TripCompletedNotification extends Notification
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
            'type'      => 'trip_completed',
            'trip_id'   => $this->trip->id,
            'amount'    => $this->trip->amount,
            'message'   => 'Course terminée. Montant : ' . $this->trip->amount . ' XAF.',
        ];
    }
}
