<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentValidated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function broadcastOn(): array
    {
        return [
            // Notifier le client
            new PrivateChannel('user.' . $this->booking->user_id),
            // Notifier le chauffeur
            new PrivateChannel('driver.' . $this->booking->trip->driver_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'payment.validated';
    }

    public function broadcastWith(): array
    {
        return [
            'booking_id' => $this->booking->id,
            'trip_id'    => $this->booking->trip_id,
            'amount'     => $this->booking->amount,
            'chat_enabled' => true, // Flutter active le chat
            'chat_channel' => 'chat.trip.' . $this->booking->trip_id,
            'message'    => 'Paiement validé ! Vous pouvez maintenant discuter avec le chauffeur.',
            'sound'      => 'payment_success',
        ];
    }
}