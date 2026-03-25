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
        $wallet = Wallet::with('transactions', 'withdrawals')
            ->where('driver_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'balance'      => $wallet->balance,
            'currency'     => $wallet->currency,
            'transactions' => $wallet->transactions->sortByDesc('created_at')->take(20)->values(),
            'withdrawals'  => $wallet->withdrawals->sortByDesc('created_at')->take(10)->values(),
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