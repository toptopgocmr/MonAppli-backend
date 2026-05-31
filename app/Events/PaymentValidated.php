<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
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
            new Channel('user.'   . $this->booking->user_id),
            new Channel('driver.' . $this->booking->trip->driver_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'payment.received';
    }

    public function broadcastWith(): array
    {
        return [
            'booking_id'   => $this->booking->id,
            'trip_id'      => $this->booking->trip_id,
            'amount'       => $this->booking->amount,
            'chat_enabled' => true,
            'chat_channel' => 'chat.trip.' . $this->booking->trip_id,
            'message'      => 'Paiement reçu ! La réservation #' . $this->booking->id . ' est confirmée.',
        ];
    }
}
