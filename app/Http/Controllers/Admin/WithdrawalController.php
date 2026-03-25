<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Withdrawal;
use App\Models\AdminLog;
use App\Notifications\WithdrawalProcessedNotification;

class WithdrawalController extends Controller
{
    public function index(Request $request)
    {
        $query = Withdrawal::with('driver');

        if ($request->status) $query->where('status', $request->status);
        if ($request->from)   $query->whereDate('created_at', '>=', $request->from);
        if ($request->to)     $query->whereDate('created_at', '<=', $request->to);

        return response()->json($query->latest()->paginate(20));
    }

    public function approve(Request $request, $id)
    {
        $withdrawal = Withdrawal::findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return response()->json(['message' => 'Retrait déjà traité.'], 422);
        }

        $withdrawal->update([
            'status'          => 'success',
            'processed_at'    => now(),
            'transaction_ref' => 'TXN-' . strtoupper(uniqid()),
        ]);

        // Notifier le chauffeur
        $withdrawal->driver?->notify(new WithdrawalProcessedNotification($withdrawal));

        AdminLog::create([
            'admin_id'   => $request->user()->id,
            'action'     => 'approve_withdrawal',
            'model'      => 'Withdrawal',
            'model_id'   => $withdrawal->id,
            'new_data'   => ['status' => 'success'],
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Retrait approuvé.', 'withdrawal' => $withdrawal]);
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['reason' => 'nullable|string|max:255']);

        $withdrawal = Withdrawal::findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return response()->json(['message' => 'Retrait déjà traité.'], 422);
        }

        $withdrawal->update(['status' => 'failed', 'processed_at' => now()]);

        // Rembourser le wallet
        $wallet = $withdrawal->wallet;
        $before = $wallet->balance;
        $wallet->increment('balance', $withdrawal->amount);

        \App\Models\WalletTransaction::create([
            'wallet_id'      => $wallet->id,
            'type'           => 'credit',
            'amount'         => $withdrawal->amount,
            'balance_before' => $before,
            'balance_after'  => $wallet->fresh()->balance,
            'description'    => 'Remboursement retrait rejeté #' . $withdrawal->id,
        ]);

        $withdrawal->driver?->notify(new WithdrawalProcessedNotification($withdrawal));

        return response()->json(['message' => 'Retrait rejeté et solde remboursé.']);
    }
}
