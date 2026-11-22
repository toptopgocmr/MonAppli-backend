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

        // Prix de la COURSE (hors frais de service client, qui restent à TopTopGo).
        $total = (float) $booking->amount;

        // ✅ Commission = celle calculée au paiement avec le taux configuré dans
        // l'admin (société / chauffeur / pays…), et non plus un 10 % fixe.
        $payment = \App\Models\Payment::where('booking_id', $booking->id)
            ->where('status', 'success')
            ->latest()
            ->first();

        if ($payment && $payment->driver_net !== null) {
            $driverNet  = round((float) $payment->driver_net, 2);
            $commission = round((float) ($payment->commission ?? ($total - $driverNet)), 2);
        } else {
            $rate = \App\Models\CommissionRate::resolveRate(
                (int) $trip->driver_id,
                (string) ($trip->driver->vehicle_type ?? ''),
                (string) ($payment->country ?? $trip->country ?? 'CG')
            );
            $commission = round($total * ($rate / 100), 2);
            $driverNet  = round($total - $commission, 2);
        }

        // Idempotence : ne jamais créditer deux fois la même course.
        if (\App\Models\WalletTransaction::where('reference', 'BOOKING-' . $booking->id)->exists()) {
            Log::info('CreditDriverWallet: course déjà créditée', ['booking_id' => $booking->id]);
            return;
        }

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
