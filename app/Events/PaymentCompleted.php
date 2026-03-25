<?php

namespace App\Events;

use App\Models\Transaction;
use App\Models\Ride;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Transaction $transaction,
        public ?Ride $ride = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('user.' . $this->transaction->user_id),
        ];

        // Also broadcast to driver if it's a ride payment
        if ($this->ride && $this->ride->driver_id) {
            $channels[] = new PrivateChannel('user.' . $this->ride->driver_id);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'payment.completed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'transaction_id' => $this->transaction->id,
            'reference' => $this->transaction->reference,
            'amount' => $this->transaction->amount,
            'type' => $this->transaction->type,
            'status' => 'completed',
            'ride_id' => $this->ride?->id,
            'message' => $this->getNotificationMessage(),
        ];
    }

    protected function getNotificationMessage(): string
    {
        return match ($this->transaction->type) {
            'ride_payment' => 'Paiement du trajet effectué avec succès',
            'driver_credit' => 'Vous avez reçu un paiement de ' . $this->transaction->getFormattedAmount(),
            'withdrawal' => 'Votre retrait a été effectué avec succès',
            default => 'Transaction complétée',
        };
    }
}
