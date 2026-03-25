<?php

namespace App\Notifications;

use App\Models\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WithdrawalProcessedNotification extends Notification
{
    use Queueable;

    public function __construct(public Withdrawal $withdrawal) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'    => 'withdrawal_processed',
            'amount'  => $this->withdrawal->amount,
            'method'  => $this->withdrawal->method,
            'status'  => $this->withdrawal->status,
            'message' => 'Retrait de ' . $this->withdrawal->amount . ' XAF via ' . strtoupper($this->withdrawal->method) . ' : ' . $this->withdrawal->status,
        ];
    }
}
