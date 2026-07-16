<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Models\Driver\Driver;
use App\Notifications\WithdrawalProcessedNotification;
use App\Services\Payment\PeexService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentPartnerController extends Controller
{
    public function __construct(protected PeexService $peex)
    {
    }

    public function index(Request $request)
    {
        $period = $request->get('period', 'month');
        [$startDate, $endDate] = $this->getDateRange($period, $request);

        // ── Stats par partenaire ──────────────────────────────────
        // Visa/Mastercard retirés : Stripe n'est plus une passerelle active,
        // Peex couvre aussi la collecte bancaire.
        $partners = ['mtn', 'orange', 'airtel', 'moov', 'wave', 'free', 'mobicash', 'vodafone', 'mpesa', 'zamtel', 'tnm', 'halopesa', 'airteltigo', 'tigo'];

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

        // ── Stats par passerelle (provider réel : peex, flutterwave) ──
        // Contrairement à $partnerStats (par opérateur mobile money), ce
        // regroupement se base sur la colonne `provider`, qui est toujours
        // renseignée correctement — c'est la vue fiable pour suivre Peex.
        // Stripe retiré de l'affichage : Peex couvre aussi la collecte
        // bancaire (Bank Payment Request), Stripe n'est plus une passerelle
        // active en pratique.
        $gateways = [
            'peex'        => [
                'label' => 'Peex',
                'color' => '#16a34a',
                'icon'  => '🟢',
                'logo'  => 'https://peex-api-docs.peexit.com/_next/image?url=%2Fimages%2Flogo_peex.png&w=384&q=75',
            ],
            'flutterwave' => [
                'label' => 'Flutterwave',
                'color' => '#f97316',
                'icon'  => '🟠',
                'logo'  => 'https://cdn.worldvectorlogo.com/logos/flutterwave-1.svg',
            ],
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
        $totalCancelled   = Payment::where('status', 'cancelled')->whereBetween('created_at', [$startDate, $endDate])->count();

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
            'totalCancelled',
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
     * Page dédiée à la gestion des retraits chauffeurs (approbation/rejet).
     * Séparée du dashboard principal pour le garder concis.
     */
    public function withdrawalsIndex(Request $request)
    {
        $period = $request->get('period', 'month');
        [$startDate, $endDate] = $this->getDateRange($period, $request);

        $query = Withdrawal::with(['driver', 'wallet'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $withdrawals = $query->latest()->paginate(20)->withQueryString();

        // ── KPI ────────────────────────────────────────────────────
        // "En attente" et "en retard" regardent TOUTE la base (peu importe la
        // période choisie) : un retrait en attente reste un problème actif
        // même s'il a été demandé le mois dernier.
        $pendingCount  = Withdrawal::where('status', 'pending')->count();
        $pendingAmount = Withdrawal::where('status', 'pending')->sum('amount');
        $lateCount     = Withdrawal::where('status', 'pending')
            ->where('created_at', '<=', now()->subHours(24))
            ->count();

        // Le reste est bien filtré sur la période sélectionnée.
        $paidCount     = Withdrawal::where('status', 'success')
            ->whereBetween('processed_at', [$startDate, $endDate])
            ->count();
        $paidAmount    = Withdrawal::where('status', 'success')
            ->whereBetween('processed_at', [$startDate, $endDate])
            ->sum('amount');
        $rejectedCount = Withdrawal::where('status', 'failed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // ✅ Récap CA brut / commission TopTopGo / solde réel PAR chauffeur,
        // pour que l'admin voie d'un coup d'œil si le montant demandé est
        // cohérent avec le solde réellement disponible avant d'approuver
        // (même logique que pour les retraits société).
        $recapByDriver = [];
        foreach ($withdrawals->getCollection()->pluck('driver')->filter()->unique('id') as $driver) {
            $recapByDriver[$driver->id] = $driver->withdrawalRecap();
        }

        return view('admin.payments.withdrawals', compact(
            'withdrawals',
            'pendingCount',
            'pendingAmount',
            'lateCount',
            'paidCount',
            'paidAmount',
            'rejectedCount',
            'recapByDriver',
            'period'
        ));
    }

    /**
     * Export CSV des retraits chauffeurs (respecte la période + le filtre statut).
     */
    public function exportWithdrawals(Request $request)
    {
        $period = $request->get('period', 'month');
        [$startDate, $endDate] = $this->getDateRange($period, $request);

        $query = Withdrawal::with('driver')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $withdrawals = $query->latest()->get();

        $filename = "retraits-chauffeurs-" . now()->format('Y-m-d') . ".csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
        ];

        $callback = function () use ($withdrawals) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Chauffeur', 'Pays véhicule', 'Méthode', 'Téléphone', 'Montant (XAF)', 'Référence', 'Statut', 'Demandé le', 'Traité le']);

            foreach ($withdrawals as $w) {
                fputcsv($file, [
                    trim(($w->driver->first_name ?? '') . ' ' . ($w->driver->last_name ?? '')),
                    strtoupper($w->driver->vehicle_country ?? ''),
                    strtoupper($w->method),
                    $w->phone_number,
                    $w->amount,
                    $w->transaction_ref,
                    $w->status,
                    $w->created_at,
                    $w->processed_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Approuver un retrait.
     *
     * IMPORTANT sur l'argent : le wallet du chauffeur a déjà été débité au
     * moment de la DEMANDE de retrait (WalletService::requestWithdrawal).
     * Cette méthode ne doit donc plus re-débiter le wallet ici (c'était un
     * bug — double débit) : elle se contente de déclencher le vrai paiement
     * (via Peex Disbursement quand le pays du chauffeur est couvert), puis
     * de marquer le retrait comme traité.
     */
    public function approveWithdrawal(Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'Ce retrait ne peut plus être modifié.');
        }

        $driver = $withdrawal->driver;
        $country = strtoupper($driver->vehicle_country ?? '');
        $eligibleForPeex = $this->peex->supportsDisbursementFor($country);

        if ($eligibleForPeex) {
            $result = $this->peex->payout([
                'reference'   => 'WD-' . $withdrawal->id . '-' . strtoupper(Str::random(6)),
                'phone'       => $withdrawal->phone_number,
                'amount'      => (int) $withdrawal->amount,
                'currency'    => 'XAF',
                'country'     => $country,
                'first_name'  => $driver->first_name ?? 'Chauffeur',
                'last_name'   => $driver->last_name ?? 'TopTopGo',
                'purpose'     => 'BUSINESS',
                'fund_origin' => 'SALES_AND_BUSINESS_DEVELOPMENT',
            ]);

            if (!($result['success'] ?? false)) {
                Log::error('Peex payout failed on withdrawal approval', [
                    'withdrawal_id' => $withdrawal->id,
                    'driver_id'     => $driver->id ?? null,
                    'error'         => $result['error'] ?? 'unknown',
                ]);

                // Volontairement laissé en 'pending' : le wallet chauffeur reste
                // débité (comme depuis la demande), rien n'est perdu, mais le
                // retrait attend une intervention manuelle (relancer ou payer
                // par un autre moyen) plutôt que d'être marqué traité à tort.
                return back()->with('error', 'Échec du paiement Peex : ' . ($result['error'] ?? 'erreur inconnue') . '. Le retrait reste en attente pour vérification manuelle.');
            }

            $withdrawal->update([
                'status'          => 'success',
                'processed_at'    => now(),
                'transaction_ref' => $result['reference'] ?? $result['transaction_id'] ?? $withdrawal->transaction_ref,
            ]);

            $driver?->notify(new WithdrawalProcessedNotification($withdrawal));

            return back()->with('success', 'Retrait payé automatiquement via Peex.');
        }

        // Pays non couvert par Peex Disbursement → process manuel inchangé :
        // l'admin paie lui-même le chauffeur en dehors de la plateforme,
        // et se contente ici de marquer le retrait comme traité.
        $withdrawal->update([
            'status'       => 'success',
            'processed_at' => now(),
        ]);

        $driver?->notify(new WithdrawalProcessedNotification($withdrawal));

        return back()->with('success', 'Retrait marqué comme payé (paiement manuel — pays non couvert par Peex).');
    }

    /**
     * Rejeter un retrait — rembourse le wallet du chauffeur, puisqu'il avait
     * été débité dès la demande (sinon l'argent disparaissait sans retour :
     * bug corrigé ici).
     */
    public function rejectWithdrawal(Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'Ce retrait ne peut plus être modifié.');
        }

        $withdrawal->update(['status' => 'failed', 'processed_at' => now()]);

        $wallet = $withdrawal->wallet;
        if ($wallet) {
            $before = $wallet->balance;
            $wallet->increment('balance', $withdrawal->amount);

            WalletTransaction::create([
                'wallet_id'      => $wallet->id,
                'type'           => 'credit',
                'amount'         => $withdrawal->amount,
                'balance_before' => $before,
                'balance_after'  => $wallet->fresh()->balance,
                'description'    => 'Remboursement retrait rejeté #' . $withdrawal->id,
                'reference'      => $withdrawal->transaction_ref,
            ]);
        }

        $withdrawal->driver?->notify(new WithdrawalProcessedNotification($withdrawal));

        return back()->with('success', 'Retrait rejeté et solde remboursé au chauffeur.');
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
            'wave'       => 'Wave',
            'free'       => 'Free Money',
            'mobicash'   => 'Mobicash',
            'vodafone'   => 'Telecel (Vodafone)',
            'mpesa'      => 'M-Pesa',
            'zamtel'     => 'Zamtel',
            'tnm'        => 'TNM Mpamba',
            'halopesa'   => 'Halopesa',
            'airteltigo' => 'AirtelTigo',
            'tigo'       => 'Tigo Pesa',
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
            'wave'       => '🌊',
            'free'       => '🟢',
            'mobicash'   => '🟡',
            'vodafone'   => '🔴',
            'mpesa'      => '🟢',
            'zamtel'     => '🟢',
            'tnm'        => '🔵',
            'halopesa'   => '🩵',
            'airteltigo' => '🔵',
            'tigo'       => '🔵',
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
            'wave'       => 'cyan',
            'free'       => 'green',
            'mobicash'   => 'yellow',
            'vodafone'   => 'red',
            'mpesa'      => 'green',
            'zamtel'     => 'green',
            'tnm'        => 'blue',
            'halopesa'   => 'cyan',
            'airteltigo' => 'blue',
            'tigo'       => 'blue',
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
