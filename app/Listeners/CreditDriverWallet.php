<?php

namespace App\Listeners;

use App\Events\PaymentValidated;
use App\Models\Driver\Driver;
use App\Models\Wallet;
use App\Notifications\BookingPaidNotification;
use App\Services\WalletService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CreditDriverWallet implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private WalletService $walletService) {}

    public function handle(PaymentValidated $event): void
    {
        $booking = $event->booking;
        $trip    = $booking->trip;

        if (!$trip || !$trip->driver_id) {
            Log::warning('CreditDriverWallet: driver_id manquant', ['booking_id' => $booking->id]);
            return;
        }

        $total      = (float) $booking->amount;
        $commission = round($total * 0.10, 2);   // 10% ToptopGo
        $driverNet  = round($total - $commission, 2); // 90% chauffeur

        // Trouver ou créer le wallet du chauffeur
        $wallet = Wallet::firstOrCreate(
            ['driver_id' => $trip->driver_id],
            ['balance'   => 0, 'currency' => 'XAF']
        );

        $this->walletService->credit(
            wallet:      $wallet,
            amount:      $driverNet,
            description: "Course #{$booking->id} — {$driverNet} FCFA (total {$total} FCFA - commission {$commission} FCFA)",
            reference:   'BOOKING-' . $booking->id,
        );

        Log::info('CreditDriverWallet: wallet crédité', [
            'driver_id'  => $trip->driver_id,
            'booking_id' => $booking->id,
            'total'      => $total,
            'commission' => $commission,
            'driver_net' => $driverNet,
        ]);

        // ✅ Notifier le chauffeur (FCM + database)
        $driver = Driver::find($trip->driver_id);
        if ($driver) {
            try {
                $driver->notify(new BookingPaidNotification($booking));
            } catch (\Exception $e) {
                Log::error('CreditDriverWallet: notification échouée', [
                    'driver_id' => $driver->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }
    }
}
