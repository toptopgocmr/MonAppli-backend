<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingConfirmed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function broadcastOn(): array
    {
        // Canal privé — seulement le client concerné
        return [
            new PrivateChannel('user.' . $this->booking->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'booking.confirmed';
    }

    public function broadcastWith(): array
    {
        return [
            'booking_id' => $this->booking->id,
            'trip_id'    => $this->booking->trip_id,
            'amount'     => $this->booking->amount,
            'status'     => $this->booking->status,
            'message'    => 'Votre réservation a été confirmée par le chauffeur.',
            'sound'      => 'booking_confirmed',
        ];
    }
}