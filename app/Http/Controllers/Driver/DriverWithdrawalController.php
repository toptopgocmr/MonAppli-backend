<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Services\WalletService;
use Illuminate\Http\Request;

class DriverWithdrawalController extends Controller
{
    public function __construct(private WalletService $walletService) {}

    public function index(Request $request)
    {
        $withdrawals = Withdrawal::where('driver_id', $request->user()->id)
                                 ->latest()
                                 ->paginate(20);

        return response()->json(['success' => true, 'data' => $withdrawals]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount'       => 'required|numeric|min:1000',
            'method'       => 'required|string|in:mobile_money,bank',
            'phone_number' => 'required|string',
        ]);

        $withdrawal = $this->walletService->requestWithdrawal(
            $request->user()->id,
            $request->amount,
            $request->method,
            $request->phone_number
        );

        return response()->json([
            'success'    => true,
            'message'    => 'Demande de retrait soumise avec succès.',
            'data'       => $withdrawal,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $withdrawal = Withdrawal::where('id', $id)
                                ->where('driver_id', $request->user()->id)
                                ->firstOrFail();

        return response()->json(['success' => true, 'data' => $withdrawal]);
    }
}