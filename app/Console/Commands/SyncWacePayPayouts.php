<?php

namespace App\Console\Commands;

use App\Models\CompanyWithdrawal;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Services\Payment\WacePayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Suit les décaissements WacePay (module Payout de la plateforme PayIn) après
 * approbation — pas d'évènement webhook payout côté WacePay :
 *   SUCCESS                → provider_status = SUCCESS (définitif)
 *   FAILED / CANCELLED     → retrait repassé en "failed" + wallet chauffeur recrédité
 *   PENDING / PROCESSING   → on note le statut et on attend.
 * Planifiée toutes les 10 min (routes/console.php).
 */
class SyncWacePayPayouts extends Command
{
    protected $signature = 'wacepay:sync-payouts {--days=15 : Ancienneté max des retraits suivis}';

    protected $description = 'Synchronise le statut des retraits payés via WacePay';

    public function handle(WacePayService $wacepay): int
    {
        if (!$wacepay->isPayoutConfigured()) {
            $this->warn('WacePay payout non configuré — rien à faire.');
            return self::SUCCESS;
        }

        $since = now()->subDays((int) $this->option('days'));
        $final = ['SUCCESS', 'SUCCESSFUL', 'COMPLETED', 'PAID', 'FAILED', 'REJECTED', 'CANCEL', 'CANCELED', 'CANCELLED'];

        foreach ([Withdrawal::class, CompanyWithdrawal::class] as $model) {
            $rows = $model::where('provider', 'wacepay')
                ->whereNotNull('transaction_ref')
                ->where(fn ($q) => $q->whereNull('provider_status')->orWhereNotIn('provider_status', $final))
                ->where('updated_at', '>=', $since)
                ->limit(100)
                ->get();

            foreach ($rows as $w) {
                $msisdn = $w->phone_number
                    ? $wacepay->msisdn($w->phone_number, strtoupper($w->country ?? $w->driver?->vehicle_country ?? 'CG'))
                    : null;
                $res = $wacepay->getPayoutStatus($w->transaction_ref, $msisdn);
                if (!($res['success'] ?? false)) {
                    $this->error("{$w->transaction_ref} : " . ($res['error'] ?? 'erreur'));
                    continue;
                }

                $raw = $res['raw_status'];
                $this->line(class_basename($model) . " #{$w->id} {$w->transaction_ref} : {$raw}");

                if ($res['status'] === 'failed' && $w->status === 'success') {
                    $this->cancel($w, $raw);
                } elseif ($raw !== $w->provider_status) {
                    $w->update(['provider_status' => $raw]);
                }
            }
        }

        return self::SUCCESS;
    }

    protected function cancel($w, string $raw): void
    {
        DB::transaction(function () use ($w, $raw) {
            $w->update(['status' => 'failed', 'provider_status' => $raw]);

            // Retrait chauffeur : le wallet avait été débité dès la demande → on recrédite.
            if ($w instanceof Withdrawal && ($wallet = $w->wallet)) {
                $before = $wallet->balance;
                $wallet->increment('balance', $w->amount);

                WalletTransaction::create([
                    'wallet_id'      => $wallet->id,
                    'type'           => 'credit',
                    'amount'         => $w->amount,
                    'balance_before' => $before,
                    'balance_after'  => $wallet->fresh()->balance,
                    'description'    => 'Remboursement retrait échoué/annulé WacePay #' . $w->id,
                    'reference'      => $w->transaction_ref,
                ]);
            }
            // Retrait société : le solde est recalculé à partir des retraits non "failed".
        });

        Log::warning('WacePay : décaissement échoué/annulé, retrait repassé en échec', [
            'model' => class_basename($w), 'id' => $w->id, 'reference' => $w->transaction_ref,
        ]);
    }
}
