<?php

namespace App\Events;

use App\Models\Transaction;
use App\Models\Ride;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentFailed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Transaction $transaction,
        public ?Ride $ride = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->transaction->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'payment.failed';
    }

    public function broadcastWith(): array
    {
        return [
            'transaction_id' => $this->transaction->id,
            'reference' => $this->transaction->reference,
            'amount' => $this->transaction->amount,
            'type' => $this->transaction->type,
            'status' => 'failed',
            'ride_id' => $this->ride?->id,
            'message' => 'Le paiement a échoué. Veuillez réessayer.',
        ];
    }
}
