<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DriverApprovedNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'    => 'driver_approved',
            'message' => 'Félicitations ! Votre compte chauffeur a été approuvé. Vous pouvez maintenant accepter des courses.',
        ];
    }
}
