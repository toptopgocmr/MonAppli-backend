<?php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayoutCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Transaction $transaction
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->transaction->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'payout.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'transaction_id' => $this->transaction->id,
            'reference' => $this->transaction->reference,
            'amount' => $this->transaction->amount,
            'status' => 'completed',
            'message' => 'Votre retrait de ' . $this->transaction->getFormattedAmount() . ' a été effectué',
        ];
    }
}
