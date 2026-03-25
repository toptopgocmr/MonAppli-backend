<?php

namespace App\Notifications;

use App\Models\Ride;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class RideStatusUpdate extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $statusMessages = [
        'accepted' => [
            'title' => 'Chauffeur trouvé',
            'body' => 'Un chauffeur a accepté votre course',
        ],
        'driver_arriving' => [
            'title' => 'Chauffeur en route',
            'body' => 'Votre chauffeur arrive bientôt',
        ],
        'in_progress' => [
            'title' => 'Course démarrée',
            'body' => 'Votre trajet est en cours',
        ],
        'completed' => [
            'title' => 'Course terminée',
            'body' => 'Merci d\'avoir voyagé avec TopTopGo',
        ],
        'cancelled' => [
            'title' => 'Course annulée',
            'body' => 'La course a été annulée',
        ],
        'driver_cancelled' => [
            'title' => 'Chauffeur indisponible',
            'body' => 'Nous recherchons un nouveau chauffeur',
        ],
    ];

    public function __construct(
        protected Ride $ride,
        protected string $status
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        if ($notifiable->fcm_token) {
            $channels[] = 'fcm';
        }

        return $channels;
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        $message = $this->statusMessages[$this->status] ?? [
            'title' => 'Mise à jour',
            'body' => 'Statut de votre course mis à jour',
        ];

        return (new FcmMessage(notification: new FcmNotification(
            title: $message['title'],
            body: $message['body'],
        )))
            ->data([
                'type' => 'ride_status',
                'ride_id' => (string) $this->ride->id,
                'status' => $this->status,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ])
            ->custom([
                'android' => [
                    'notification' => [
                        'channel_id' => 'ride_updates',
                    ],
                    'priority' => 'high',
                ],
            ]);
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    public function toArray(object $notifiable): array
    {
        $message = $this->statusMessages[$this->status] ?? [
            'title' => 'Mise à jour',
            'body' => 'Statut mis à jour',
        ];

        return [
            'type' => 'ride_status',
            'ride_id' => $this->ride->id,
            'status' => $this->status,
            'title' => $message['title'],
            'message' => $message['body'],
            'ride' => [
                'id' => $this->ride->id,
                'status' => $this->ride->status,
                'driver_id' => $this->ride->driver_id,
            ],
        ];
    }
}
