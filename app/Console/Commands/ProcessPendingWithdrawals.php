<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\Payment\PaymentService;
use Illuminate\Console\Command;

class ProcessPendingWithdrawals extends Command
{
    protected $signature = 'payments:process-withdrawals';

    protected $description = 'Process pending withdrawal requests';

    public function handle(PaymentService $paymentService): int
    {
        $pendingWithdrawals = Transaction::where('type', 'withdrawal')
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(5))
            ->limit(50)
            ->get();

        $this->info("Processing {$pendingWithdrawals->count()} pending withdrawals...");

        foreach ($pendingWithdrawals as $transaction) {
            try {
                $provider = $paymentService->provider($transaction->provider);
                $status = $provider->getTransactionStatus($transaction->provider_transaction_id);

                if ($status['success'] && isset($status['status'])) {
                    $transaction->update(['status' => $status['status']]);
                    $this->line("Transaction {$transaction->reference}: {$status['status']}");
                }
            } catch (\Exception $e) {
                $this->error("Error processing {$transaction->reference}: {$e->getMessage()}");
            }
        }

        $this->info('Done.');

        return Command::SUCCESS;
    }
}
