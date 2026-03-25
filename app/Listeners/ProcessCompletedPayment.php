<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Models\Ride;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProcessCompletedPayment implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PaymentCompleted $event): void
    {
        $transaction = $event->transaction;
        $ride = $event->ride;

        if (!$ride) {
            return;
        }

        // Update ride payment status
        if ($transaction->type === 'ride_payment') {
            $ride->update([
                'payment_status' => 'escrowed',
            ]);

            \Log::info("Ride payment escrowed", [
                'ride_id' => $ride->id,
                'amount' => $transaction->amount,
            ]);
        }
    }
}
