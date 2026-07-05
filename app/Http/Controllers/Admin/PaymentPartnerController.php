<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Models\Driver\Driver;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PaymentPartnerController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'month');
        [$startDate, $endDate] = $this->getDateRange($period, $request);

        // ── Stats par partenaire ──────────────────────────────────
        $partners = ['mtn', 'orange', 'airtel', 'moov', 'visa', 'mastercard'];

        $partnerStats = [];
        foreach ($partners as $partner) {
            $query = Payment::where('method', $partner)
                ->whereBetween('paid_at', [$startDate, $endDate]);

            $partnerStats[$partner] = [
                'name'        => $this->partnerName($partner),
                'icon'        => $this->partnerIcon($partner),
                'color'       => $this->partnerColor($partner),
                'total'       => (clone $query)->where('status', 'success')->sum('amount'),
                'count'       => (clone $query)->where('status', 'success')->count(),
                'pending'     => (clone $query)->where('status', 'pending')->count(),
                'failed'      => (clone $query)->where('status', 'failed')->count(),
                'refunded'    => (clone $query)->where('status', 'refunded')->count(),
            ];
        }

        // ── Stats par passerelle (provider réel : peex, flutterwave, stripe) ──
        // Contrairement à $partnerStats (par opérateur mobile money), ce
        // regroupement se base sur la colonne `provider`, qui est toujours
        // renseignée correctement — c'est la vue fiable pour suivre Peex.
        $gateways = [
            'peex'        => ['label' => 'Peex',        'color' => '#16a34a', 'icon' => '🟢'],
            'flutterwave' => ['label' => 'Flutterwave',  'color' => '#f97316', 'icon' => '🟠'],
            'stripe'      => ['label' => 'Stripe',       'color' => '#6366f1', 'icon' => '💳'],
        ];

        $gatewayStats = [];
        foreach ($gateways as $key => $meta) {
            $q = Payment::where('provider', $key)->whereBetween('created_at', [$startDate, $endDate]);

            $gatewayStats[$key] = array_merge($meta, [
                'total'   => (clone $q)->where('status', 'success')->sum('amount'),
                'count'   => (clone $q)->where('status', 'success')->count(),
                'pending' => (clone $q)->where('status', 'pending')->count(),
                'failed'  => (clone $q)->whereIn('status', ['failed', 'cancelled'])->count(),
            ]);
        }

        // ── Stats globales ────────────────────────────────────────
        $totalRevenue     = Payment::where('status', 'success')->whereBetween('paid_at', [$startDate, $endDate])->sum('amount');
        $totalCommission  = Payment::where('status', 'success')->whereBetween('paid_at', [$startDate, $endDate])->sum('commission');
        $totalDriverNet   = Payment::where('status', 'success')->whereBetween('paid_at', [$startDate, $endDate])->sum('driver_net');
        $totalPending     = Payment::where('status', 'pending')->whereBetween('created_at', [$startDate, $endDate])->count();
        $totalFailed      = Payment::where('status', 'failed')->whereBetween('created_at', [$startDate, $endDate])->count();

        // ── Wallet application ────────────────────────────────────
        $totalWalletBalance  = Wallet::sum('balance');
        $totalWallets        = Wallet::count();
        $totalCredits        = WalletTransaction::where('type', 'credit')
                                ->whereBetween('created_at', [$startDate, $endDate])
                                ->sum('amount');
        $totalDebits         = WalletTransaction::where('type', 'debit')
                                ->whereBetween('created_at', [$startDate, $endDate])
                                ->sum('amount');

        // ── Retraits ──────────────────────────────────────────────
        $withdrawalsPending  = Withdrawal::where('status', 'pending')->count();
        $withdrawalsSuccess  = Withdrawal::where('status', 'success')
                                ->whereBetween('processed_at', [$startDate, $endDate])
                                ->sum('amount');
        $withdrawalsFailed   = Withdrawal::where('status', 'failed')
                                ->whereBetween('created_at', [$startDate, $endDate])
                                ->count();

        // ── Transactions récentes (payments) ─────────────────────
        $paymentsQuery = Payment::with(['user', 'driver'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($request->filled('method')) {
            $paymentsQuery->where('method', $request->method);
        }
        if ($request->filled('status')) {
            $paymentsQuery->where('status', $request->status);
        }
        if ($request->filled('country')) {
            $paymentsQuery->where('country', $request->country);
        }

        $payments = $paymentsQuery->latest('created_at')->paginate(20);

        // ── Retraits récents ──────────────────────────────────────
        $withdrawals = Withdrawal::with(['driver', 'wallet'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($request->filled('withdrawal_status'), fn($q) => $q->where('status', $request->withdrawal_status))
            ->latest()
            ->take(10)
            ->get();

        // ── Wallet transactions récentes ──────────────────────────
        $walletTransactions = WalletTransaction::with('wallet.driver')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->take(10)
            ->get();

        // ── Top wallets ───────────────────────────────────────────
        $topWallets = Wallet::with('driver')
            ->orderByDesc('balance')
            ->take(5)
            ->get();

        // ── Pays disponibles ──────────────────────────────────────
        $countries = Payment::distinct()->pluck('country')->filter()->sort()->values();

        return view('admin.payments.index', compact(
            'partnerStats',
            'partners',
            'gatewayStats',
            'totalRevenue',
            'totalCommission',
            'totalDriverNet',
            'totalPending',
            'totalFailed',
            'totalWalletBalance',
            'totalWallets',
            'totalCredits',
            'totalDebits',
            'withdrawalsPending',
            'withdrawalsSuccess',
            'withdrawalsFailed',
            'payments',
            'withdrawals',
            'walletTransactions',
            'topWallets',
            'countries',
            'period',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Approuver un retrait
     */
    public function approveWithdrawal(Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'Ce retrait ne peut plus être modifié.');
        }

        $withdrawal->update([
            'status'       => 'success',
            'processed_at' => now(),
        ]);

        // Débiter le wallet
        $wallet = $withdrawal->wallet;
        $balanceBefore = $wallet->balance;
        $wallet->decrement('balance', $withdrawal->amount);

        WalletTransaction::create([
            'wallet_id'      => $wallet->id,
            'type'           => 'debit',
            'amount'         => $withdrawal->amount,
            'balance_before' => $balanceBefore,
            'balance_after'  => $wallet->fresh()->balance,
            'description'    => 'Retrait approuvé via ' . strtoupper($withdrawal->method),
            'reference'      => $withdrawal->transaction_ref,
        ]);

        return back()->with('success', 'Retrait approuvé et wallet débité.');
    }

    /**
     * Rejeter un retrait
     */
    public function rejectWithdrawal(Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'Ce retrait ne peut plus être modifié.');
        }

        $withdrawal->update(['status' => 'failed']);

        return back()->with('success', 'Retrait rejeté.');
    }

    /**
     * Export CSV
     */
    public function export(Request $request)
    {
        $period = $request->get('period', 'month');
        [$startDate, $endDate] = $this->getDateRange($period, $request);

        $payments = Payment::with(['user', 'driver'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->get();

        $filename = "partenaires-payeurs-" . now()->format('Y-m-d') . ".csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
        ];

        $callback = function () use ($payments) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Ref', 'Date', 'Client', 'Chauffeur', 'Méthode', 'Montant', 'Commission', 'Net chauffeur', 'Statut', 'Pays']);

            foreach ($payments as $p) {
                fputcsv($file, [
                    $p->transaction_ref,
                    $p->paid_at ?? $p->created_at,
                    optional($p->user)->name ?? optional($p->user)->first_name,
                    optional($p->driver)->first_name . ' ' . optional($p->driver)->last_name,
                    strtoupper($p->method),
                    $p->amount,
                    $p->commission,
                    $p->driver_net,
                    $p->status,
                    $p->country,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function partnerName(string $method): string
    {
        return match($method) {
            'mtn'        => 'MTN Money',
            'orange'     => 'Orange Money',
            'airtel'     => 'Airtel Money',
            'moov'       => 'Moov Money',
            'visa'       => 'Visa / Stripe',
            'mastercard' => 'Mastercard',
            default      => strtoupper($method),
        };
    }

    private function partnerIcon(string $method): string
    {
        return match($method) {
            'mtn'        => '🟡',
            'orange'     => '🟠',
            'airtel'     => '🔴',
            'moov'       => '🔵',
            'visa'       => '💳',
            'mastercard' => '💳',
            default      => '💰',
        };
    }

    private function partnerColor(string $method): string
    {
        return match($method) {
            'mtn'        => 'yellow',
            'orange'     => 'orange',
            'airtel'     => 'red',
            'moov'       => 'blue',
            'visa'       => 'indigo',
            'mastercard' => 'purple',
            default      => 'gray',
        };
    }

    private function getDateRange(string $period, Request $request): array
    {
        return match ($period) {
            'today'  => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            'week'   => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'month'  => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'year'   => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            'custom' => [
                Carbon::parse($request->start)->startOfDay(),
                Carbon::parse($request->end)->endOfDay(),
            ],
            default  => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
        };
    }
}