<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\Payment\PaymentService;
use Illuminate\Console\Command;

class SyncPaymentStatus extends Command
{
    protected $signature = 'payments:sync-status';

    protected $description = 'Sync transaction statuses from payment providers';

    public function handle(PaymentService $paymentService): int
    {
        $pendingTransactions = Transaction::whereIn('status', ['pending', 'processing'])
            ->whereNotNull('provider_transaction_id')
            ->where('created_at', '>=', now()->subHours(24))
            ->limit(100)
            ->get();

        $this->info("Syncing {$pendingTransactions->count()} transactions...");

        $updated = 0;

        foreach ($pendingTransactions as $transaction) {
            try {
                $provider = $paymentService->provider($transaction->provider);
                $status = $provider->getTransactionStatus($transaction->provider_transaction_id);

                if ($status['success'] && isset($status['status'])) {
                    if ($status['status'] !== $transaction->status) {
                        $transaction->update([
                            'status' => $status['status'],
                            'completed_at' => $status['status'] === 'completed' ? now() : null,
                            'failed_at' => $status['status'] === 'failed' ? now() : null,
                        ]);
                        $updated++;
                    }
                }
            } catch (\Exception $e) {
                $this->error("Error syncing {$transaction->reference}: {$e->getMessage()}");
            }
        }

        $this->info("Updated {$updated} transactions.");

        return Command::SUCCESS;
    }
}
