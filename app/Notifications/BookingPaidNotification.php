<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class BookingPaidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Booking $booking) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (!empty($notifiable->fcm_token)) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        $trip     = $this->booking->trip;
        $depart   = $trip?->departure   ?? $trip?->pickup_address  ?? '—';
        $dest     = $trip?->destination ?? $trip?->dropoff_address ?? '—';
        $seats    = (int) ($this->booking->seats ?? $this->booking->passengers ?? 1);
        $amount   = number_format((float) $this->booking->amount, 0, '.', ' ');

        return (new FcmMessage(notification: new FcmNotification(
            title: '✅ Réservation payée !',
            body: "{$seats} place(s) · {$depart} → {$dest} · {$amount} FCFA",
        )))
        ->data([
            'type'       => 'booking_paid',
            'booking_id' => (string) $this->booking->id,
            'trip_id'    => (string) $this->booking->trip_id,
            'amount'     => (string) $this->booking->amount,
            'seats'      => (string) $seats,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ])
        ->custom([
            'android' => [
                'notification' => [
                    'channel_id' => 'bookings',
                    'sound'      => 'default',
                ],
                'priority' => 'high',
            ],
            'apns' => [
                'payload' => [
                    'aps' => ['sound' => 'default'],
                ],
            ],
        ]);
    }

    public function toArray(object $notifiable): array
    {
        $trip   = $this->booking->trip;
        $seats  = (int) ($this->booking->seats ?? $this->booking->passengers ?? 1);

        return [
            'type'        => 'booking_paid',
            'booking_id'  => $this->booking->id,
            'trip_id'     => $this->booking->trip_id,
            'amount'      => $this->booking->amount,
            'seats'       => $seats,
            'departure'   => $trip?->departure   ?? $trip?->pickup_address  ?? '—',
            'destination' => $trip?->destination ?? $trip?->dropoff_address ?? '—',
            'title'       => 'Réservation payée !',
            'message'     => "{$seats} place(s) réservée(s) et payées pour votre trajet.",
        ];
    }
}
