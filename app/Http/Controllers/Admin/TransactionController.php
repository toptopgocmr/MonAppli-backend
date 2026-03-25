<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['user', 'ride']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by provider
        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }

        // Filter by date
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->latest()->paginate(20);

        // Stats
        $today = Carbon::today();
        $stats = [
            'total_today' => Transaction::whereDate('created_at', $today)->sum('amount'),
            'payments_today' => Transaction::whereDate('created_at', $today)
                ->where('type', 'payment')
                ->where('status', 'completed')
                ->sum('amount'),
            'commissions_today' => Transaction::whereDate('created_at', $today)
                ->where('type', 'commission')
                ->sum('amount'),
            'withdrawals_today' => Transaction::whereDate('created_at', $today)
                ->where('type', 'withdrawal')
                ->where('status', 'completed')
                ->sum('amount'),
        ];

        return view('admin.transactions.index', compact('transactions', 'stats'));
    }

    public function show(Transaction $transaction)
    {
        $transaction->load(['user', 'ride']);

        return view('admin.transactions.show', compact('transaction'));
    }
}
