<?php

namespace App\Notifications;

use App\Models\Ride;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class NewRideRequest extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Ride $ride
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->fcm_token) {
            $channels[] = 'fcm';
        }

        return $channels;
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return (new FcmMessage(notification: new FcmNotification(
            title: 'Nouvelle course disponible',
            body: "De {$this->ride->pickup_address} vers {$this->ride->dropoff_address}",
        )))
            ->data([
                'type' => 'new_ride',
                'ride_id' => (string) $this->ride->id,
                'pickup_address' => $this->ride->pickup_address,
                'dropoff_address' => $this->ride->dropoff_address,
                'price' => (string) $this->ride->price,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ])
            ->custom([
                'android' => [
                    'notification' => [
                        'channel_id' => 'ride_requests',
                        'sound' => 'new_ride.mp3',
                    ],
                    'priority' => 'high',
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'new_ride.aiff',
                        ],
                    ],
                ],
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_ride',
            'ride_id' => $this->ride->id,
            'title' => 'Nouvelle course disponible',
            'message' => "De {$this->ride->pickup_address} vers {$this->ride->dropoff_address}",
            'price' => $this->ride->price,
        ];
    }
}
