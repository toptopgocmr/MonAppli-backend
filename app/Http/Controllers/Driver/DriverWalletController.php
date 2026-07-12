<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\WithdrawRequest;
use App\Services\WalletService;
use App\Models\Wallet;
use Illuminate\Http\Request;

class DriverWalletController extends Controller
{
    public function __construct(private WalletService $walletService) {}

    public function show(Request $request)
    {
        $wallet = Wallet::with(['transactions', 'withdrawals'])
            ->where('driver_id', $request->user()->id)
            ->firstOrCreate(
                ['driver_id' => $request->user()->id],
                ['balance' => 0, 'currency' => 'XAF']
            );

        $transactions = $wallet->transactions()->latest()->get();

        $totalEarned    = $transactions->where('type', 'credit')->sum('amount');
        $totalWithdrawn = $transactions->where('type', 'debit')->sum('amount');
        $pendingBalance = $wallet->withdrawals()->where('status', 'pending')->sum('amount');

        // ✅ Récap basé sur les courses (CA brut / commission), demandé pour
        // que le chauffeur voie la répartition complète — pas seulement le
        // solde net déjà stocké dans le wallet.
        $recap = $request->user()->withdrawalRecap();

        // Détail des 20 dernières transactions avec commission visible
        $history = $transactions->take(20)->map(function ($tx) {
            return [
                'id'             => $tx->id,
                'type'           => $tx->type,
                'amount'         => $tx->amount,
                'balance_before' => $tx->balance_before,
                'balance_after'  => $tx->balance_after,
                'description'    => $tx->description,
                'reference'      => $tx->reference,
                'created_at'     => $tx->created_at,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'wallet'  => [
                'balance'         => (float) $wallet->balance,
                'pending_balance' => (float) $pendingBalance,
                'total_earned'    => (float) $totalEarned,
                'total_withdrawn' => (float) $totalWithdrawn,
                'currency'        => $wallet->currency ?? 'XAF',
                'commission_rate' => 10, // % ToptopGo
                // ✅ Récap complet (courses terminées) : CA brut avant
                // commission, commission TopTopGo déjà prélevée, et solde
                // réel disponible au retrait (= wallet.balance).
                'gross_earned'      => $recap['gross_revenue'],
                'commission_taken'  => $recap['commission_taken'],
                'withdrawals_paid'  => $recap['withdrawals_paid'],
                'available_balance' => $recap['available_balance'],
            ],
            'transactions' => $history,
            'withdrawals'  => $wallet->withdrawals()
                ->latest()
                ->take(10)
                ->get()
                ->values(),
        ]);
    }

    public function withdraw(WithdrawRequest $request)
    {
        $withdrawal = $this->walletService->requestWithdrawal(
            $request->user()->id,
            $request->amount,
            $request->method,
            $request->phone_number
        );

        return response()->json([
            'success'    => true,
            'message'    => 'Demande de retrait soumise avec succès.',
            'withdrawal' => $withdrawal,
        ], 201);
    }

    public function transactions(Request $request)
    {
        $wallet = Wallet::where('driver_id', $request->user()->id)->firstOrFail();
        return response()->json($wallet->transactions()->latest()->paginate(20));
    }
}
