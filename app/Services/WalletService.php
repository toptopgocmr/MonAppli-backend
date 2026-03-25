<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use Illuminate\Validation\ValidationException;

class WalletService
{
    public function credit(Wallet $wallet, float $amount, string $description, string $reference = null): WalletTransaction
    {
        $before = $wallet->balance;
        $wallet->increment('balance', $amount);

        return WalletTransaction::create([
            'wallet_id'      => $wallet->id,
            'type'           => 'credit',
            'amount'         => $amount,
            'balance_before' => $before,
            'balance_after'  => $wallet->fresh()->balance,
            'description'    => $description,
            'reference'      => $reference,
        ]);
    }

    public function debit(Wallet $wallet, float $amount, string $description, string $reference = null): WalletTransaction
    {
        if ($wallet->balance < $amount) {
            throw ValidationException::withMessages([
                'amount' => ['Solde insuffisant. Solde actuel : ' . $wallet->balance . ' ' . $wallet->currency],
            ]);
        }

        $before = $wallet->balance;
        $wallet->decrement('balance', $amount);

        return WalletTransaction::create([
            'wallet_id'      => $wallet->id,
            'type'           => 'debit',
            'amount'         => $amount,
            'balance_before' => $before,
            'balance_after'  => $wallet->fresh()->balance,
            'description'    => $description,
            'reference'      => $reference,
        ]);
    }

    public function requestWithdrawal(int $driverId, float $amount, string $method, string $phone): Withdrawal
    {
        $wallet = Wallet::where('driver_id', $driverId)->firstOrFail();

        $this->debit($wallet, $amount, 'Retrait Mobile Money', 'withdraw_' . uniqid());

        return Withdrawal::create([
            'driver_id'    => $driverId,
            'wallet_id'    => $wallet->id,
            'amount'       => $amount,
            'method'       => $method,
            'phone_number' => $phone,
            'status'       => 'pending',
        ]);
    }
}
