<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Events\PaymentFailed;
use App\Events\PayoutCompleted;
use App\Notifications\PaymentStatusNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPaymentNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PaymentCompleted|PaymentFailed|PayoutCompleted $event): void
    {
        $transaction = $event->transaction;
        $user = $transaction->user;

        if (!$user) {
            return;
        }

        $status = match (true) {
            $event instanceof PaymentCompleted => 'completed',
            $event instanceof PaymentFailed => 'failed',
            $event instanceof PayoutCompleted => 'payout_completed',
            default => 'unknown',
        };

        // Send notification
        // $user->notify(new PaymentStatusNotification($transaction, $status));

        // Log for debugging
        \Log::info("Payment notification: {$status}", [
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
            'amount' => $transaction->amount,
        ]);
    }
}
