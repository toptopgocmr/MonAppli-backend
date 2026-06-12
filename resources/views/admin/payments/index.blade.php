@extends('admin.layouts.app')
@section('title','Partenaires Payeurs')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px">

    {{-- Header --}}
    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px">
        <div>
            <h1 class="page-title">Partenaires Payeurs</h1>
            <p class="page-sub">Suivi en temps réel des paiements, retraits et wallets</p>
        </div>
        <a href="{{ route('admin.payments.export') }}?period={{ $period }}" class="btn btn-success">Exporter CSV</a>
    </div>

    {{-- Période --}}
    <div style="display:flex;flex-wrap:wrap;gap:8px">
        @foreach(['today'=>"Aujourd'hui",'week'=>'Cette semaine','month'=>'Ce mois','year'=>'Cette année'] as $key=>$label)
        <a href="?period={{ $key }}"
           style="padding:7px 16px;border-radius:9px;font-size:13px;font-weight:600;text-decoration:none;transition:all .2s;
           {{ $period===$key ? 'background:#1DA1F2;color:#fff' : 'background:#fff;color:#475569;border:1.5px solid #e2e8f0' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    {{-- KPI --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px">
        <div class="stat-card" style="border-left:4px solid #0f172a">
            <div class="lbl">REVENUS TOTAL</div>
            <div class="val" style="color:#0f172a;font-size:22px">{{ number_format($totalRevenue,0,',',' ') }}</div>
            <div class="sub">FCFA</div>
        </div>
        <div class="stat-card" style="border-left:4px solid #1DA1F2">
            <div class="lbl">COMMISSION TTG</div>
            <div class="val" style="color:#1DA1F2;font-size:22px">{{ number_format($totalCommission,0,',',' ') }}</div>
            <div class="sub">FCFA</div>
        </div>
        <div class="stat-card" style="border-left:4px solid #22c55e">
            <div class="lbl">NET CHAUFFEURS</div>
            <div class="val" style="color:#16a34a;font-size:22px">{{ number_format($totalDriverNet,0,',',' ') }}</div>
            <div class="sub">FCFA</div>
        </div>
        <div class="stat-card" style="border-left:4px solid #f59e0b">
            <div class="lbl">EN ATTENTE</div>
            <div class="val" style="color:#d97706;font-size:22px">{{ $totalPending }}</div>
            <div class="sub" style="color:#ef4444">{{ $totalFailed }} échoués</div>
        </div>
    </div>

    {{-- ===== PARTENAIRES (tableau compact) ===== --}}
    <div class="panel">
        <div class="panel-header" style="font-size:13px">Répartition par partenaire de paiement</div>
        <div style="overflow-x:auto">
            <table class="ttg-table">
                <thead>
                    <tr>
                        <th>Partenaire</th>
                        <th style="text-align:right">Total (FCFA)</th>
                        <th style="text-align:right">Paiements</th>
                        <th style="text-align:right">En attente</th>
                        <th style="text-align:right">Échoués</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($partnerStats as $key => $partner)
                    @php
                        $pColor = match($key) {
                            'mtn'  => '#f59e0b', 'orange' => '#f97316', 'airtel' => '#ef4444',
                            'moov' => '#1DA1F2', 'visa'   => '#6366f1', default  => '#8b5cf6'
                        };
                    @endphp
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <span style="font-size:18px">{{ $partner['icon'] }}</span>
                                <span style="font-weight:600;color:{{ $pColor }}">{{ $partner['name'] }}</span>
                            </div>
                        </td>
                        <td style="text-align:right;font-weight:700;color:{{ $pColor }}">{{ number_format($partner['total'],0,',',' ') }}</td>
                        <td style="text-align:right">{{ $partner['count'] }}</td>
                        <td style="text-align:right;color:#f97316">{{ $partner['pending'] }}</td>
                        <td style="text-align:right;color:#ef4444">{{ $partner['failed'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== WALLET APPLICATION + RETRAITS ===== --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px">

        {{-- Wallet App --}}
        <div class="stat-card" style="background:linear-gradient(135deg,#1DA1F2,#0d6eb5);color:#fff;border:none">
            <div class="lbl" style="color:rgba(255,255,255,.8)">Wallet Application</div>
            <div class="val" style="color:#fff;font-size:26px">{{ number_format($totalWalletBalance,0,',',' ') }}</div>
            <div class="sub" style="color:rgba(255,255,255,.7)">FCFA — {{ $totalWallets }} wallets actifs</div>
            <div style="display:flex;justify-content:space-between;margin-top:12px;font-size:13px">
                <div><div style="opacity:.7">Crédits</div><div style="font-weight:700;color:#86efac">+{{ number_format($totalCredits,0,',',' ') }}</div></div>
                <div><div style="opacity:.7">Débits</div><div style="font-weight:700;color:#fca5a5">-{{ number_format($totalDebits,0,',',' ') }}</div></div>
            </div>
        </div>

        {{-- Retraits --}}
        <div class="stat-card">
            <div class="lbl">Retraits Chauffeurs</div>
            <div style="display:flex;flex-direction:column;gap:10px;margin-top:8px">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:12px;color:#f97316;font-weight:600">⏳ En attente</span>
                    <span style="font-size:18px;font-weight:700;color:#f97316">{{ $withdrawalsPending }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:12px;color:#16a34a;font-weight:600">Validés</span>
                    <span style="font-size:14px;font-weight:700;color:#16a34a">{{ number_format($withdrawalsSuccess,0,',',' ') }} FCFA</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:12px;color:#dc2626;font-weight:600">Échoués</span>
                    <span style="font-size:18px;font-weight:700;color:#dc2626">{{ $withdrawalsFailed }}</span>
                </div>
            </div>
        </div>

        {{-- Top Wallets --}}
        <div class="stat-card">
            <div class="lbl">Top Wallets</div>
            <div style="display:flex;flex-direction:column;gap:8px;margin-top:8px">
                @forelse($topWallets as $wallet)
                <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px">
                    <span style="color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:60%">
                        {{ optional($wallet->driver)->first_name }} {{ optional($wallet->driver)->last_name }}
                    </span>
                    <span style="font-weight:700;color:#1DA1F2;white-space:nowrap;margin-left:8px">
                        {{ number_format($wallet->balance,0,',',' ') }} {{ $wallet->currency }}
                    </span>
                </div>
                @empty
                <p style="color:#94a3b8;font-size:12px">Aucun wallet</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ===== RETRAITS EN ATTENTE ===== --}}
    @if($withdrawals->where('status', 'pending')->count() > 0)
    <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 mb-6">
        <h3 class="text-sm font-bold text-orange-700 mb-3">Retraits en attente d'approbation</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-orange-600 uppercase">
                        <th class="text-left py-2 px-3">Chauffeur</th>
                        <th class="text-left py-2 px-3">Méthode</th>
                        <th class="text-left py-2 px-3">Téléphone</th>
                        <th class="text-right py-2 px-3">Montant</th>
                        <th class="text-left py-2 px-3">Date</th>
                        <th class="text-center py-2 px-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-orange-100">
                    @foreach($withdrawals->where('status', 'pending') as $w)
                    <tr>
                        <td class="py-2 px-3 font-medium">
                            {{ optional($w->driver)->first_name }} {{ optional($w->driver)->last_name }}
                        </td>
                        <td class="py-2 px-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                @if($w->method === 'mtn') bg-yellow-100 text-yellow-700
                                @elseif($w->method === 'orange') bg-orange-100 text-orange-700
                                @elseif($w->method === 'airtel') bg-red-100 text-red-700
                                @else bg-blue-100 text-blue-700 @endif">
                                {{ strtoupper($w->method) }}
                            </span>
                        </td>
                        <td class="py-2 px-3 text-gray-500">{{ $w->phone_number }}</td>
                        <td class="py-2 px-3 text-right font-bold text-gray-800">
                            {{ number_format($w->amount, 0, ',', ' ') }} XAF
                        </td>
                        <td class="py-2 px-3 text-gray-400 text-xs">
                            {{ $w->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="py-2 px-3 text-center">
                            <div class="flex justify-center gap-1">
                                <form action="{{ route('admin.payments.approve-withdrawal', $w->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded-lg transition"
                                        onclick="return confirm('Approuver ce retrait de {{ number_format($w->amount, 0) }} XAF ?')">
                                        Approuver
                                    </button>
                                </form>
                                <form action="{{ route('admin.payments.reject-withdrawal', $w->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button class="bg-red-500 hover:bg-red-600 text-white text-xs px-3 py-1 rounded-lg transition"
                                        onclick="return confirm('Rejeter ce retrait ?')">
                                        Rejeter
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ===== FILTRES PAIEMENTS ===== --}}
    <form method="GET" action="{{ route('admin.payments.index') }}" class="filter-bar" style="flex-wrap:wrap;gap:10px">
        <input type="hidden" name="period" value="{{ $period }}">
        <div style="display:flex;flex-direction:column;gap:4px">
            <label class="ttg-label">Méthode</label>
            <select name="method" class="ttg-select">
                <option value="">Toutes</option>
                @foreach(['mtn'=>'MTN Money','orange'=>'Orange Money','airtel'=>'Airtel Money','moov'=>'Moov Money','visa'=>'Visa/Stripe','mastercard'=>'Mastercard'] as $val=>$lbl)
                    <option value="{{ $val }}" {{ request('method')===$val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;flex-direction:column;gap:4px">
            <label class="ttg-label">Statut</label>
            <select name="status" class="ttg-select">
                <option value="">Tous</option>
                @foreach(['pending'=>'⏳ En attente','success'=>'Succès','failed'=>'Échoué','cancelled'=>'Annulé','refunded'=>'Remboursé'] as $val=>$lbl)
                    <option value="{{ $val }}" {{ request('status')===$val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;flex-direction:column;gap:4px">
            <label class="ttg-label">Pays</label>
            <select name="country" class="ttg-select">
                <option value="">Tous</option>
                @foreach($countries as $country)
                    <option value="{{ $country }}" {{ request('country')===$country ? 'selected' : '' }}>{{ $country }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="align-self:flex-end">Filtrer</button>
        <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary" style="align-self:flex-end">Reset</a>
    </form>

    {{-- ===== TABLEAU PAIEMENTS ===== --}}
    <div class="panel" style="padding:0;overflow:hidden">
        <div class="panel-header" style="display:flex;justify-content:space-between;align-items:center">
            <span>Transactions récentes</span>
            <span style="font-size:12px;color:#94a3b8;font-weight:400">{{ $payments->total() }} transactions</span>
        </div>
        <div style="overflow-x:auto">
            <table class="ttg-table">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Chauffeur</th>
                        <th>Méthode</th>
                        <th style="text-align:right">Montant</th>
                        <th style="text-align:right">Commission</th>
                        <th style="text-align:right">Net</th>
                        <th style="text-align:center">Statut</th>
                        <th>Pays</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 font-mono text-xs text-gray-400">
                            {{ Str::limit($payment->transaction_ref, 12) ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">
                            {{ ($payment->paid_at ?? $payment->created_at)?->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3">
                            {{ optional($payment->user)->name ?? (optional($payment->user)->first_name . ' ' . optional($payment->user)->last_name) ?? '—' }}
                        </td>
                        <td class="px-4 py-3 font-medium">
                            {{ optional($payment->driver)->first_name }} {{ optional($payment->driver)->last_name }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold
                                @if($payment->method === 'mtn') bg-yellow-100 text-yellow-700
                                @elseif($payment->method === 'orange') bg-orange-100 text-orange-700
                                @elseif($payment->method === 'airtel') bg-red-100 text-red-700
                                @elseif($payment->method === 'moov') bg-blue-100 text-blue-700
                                @elseif($payment->method === 'visa') bg-indigo-100 text-indigo-700
                                @else bg-purple-100 text-purple-700 @endif">
                                {{ strtoupper($payment->method) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-medium">{{ number_format($payment->amount, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-right text-blue-600">{{ number_format($payment->commission, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-right text-green-600 font-semibold">{{ number_format($payment->driver_net, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                @if($payment->status === 'success') bg-green-100 text-green-700
                                @elseif($payment->status === 'pending') bg-orange-100 text-orange-700
                                @elseif($payment->status === 'failed') bg-red-100 text-red-700
                                @elseif($payment->status === 'refunded') bg-purple-100 text-purple-700
                                @else bg-gray-100 text-gray-600 @endif">
                                @if($payment->status === 'success') Succès
                                @elseif($payment->status === 'pending') ⏳ Attente
                                @elseif($payment->status === 'failed') Échoué
                                @elseif($payment->status === 'refunded') ↩ Remboursé
                                @else {{ $payment->status }} @endif
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $payment->country ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-gray-400 py-10">Aucune transaction pour cette période</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())
        <div style="padding:14px 20px;border-top:1px solid #f1f5f9">{{ $payments->links() }}</div>
        @endif
    </div>

    {{-- ===== WALLET TRANSACTIONS RÉCENTES ===== --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px">

        <div class="panel" style="padding:0;overflow:hidden">
            <div class="panel-header">Mouvements Wallet</div>
            <div>
                @forelse($walletTransactions as $wt)
                <div style="padding:12px 20px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #f8fafc">
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#1e293b">
                            {{ optional(optional($wt->wallet)->driver)->first_name }}
                            {{ optional(optional($wt->wallet)->driver)->last_name }}
                        </div>
                        <div style="font-size:11px;color:#94a3b8">{{ $wt->description ?? $wt->reference ?? '—' }}</div>
                        <div style="font-size:11px;color:#cbd5e1">{{ $wt->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-weight:700;color:{{ $wt->type==='credit'?'#16a34a':'#dc2626' }}">
                            {{ $wt->type==='credit' ? '+' : '-' }}{{ number_format($wt->amount,0,',',' ') }} XAF
                        </div>
                        <div style="font-size:11px;color:#94a3b8">Solde : {{ number_format($wt->balance_after,0,',',' ') }}</div>
                    </div>
                </div>
                @empty
                <div style="text-align:center;padding:32px;color:#94a3b8">Aucun mouvement</div>
                @endforelse
            </div>
        </div>

        <div class="panel" style="padding:0;overflow:hidden">
            <div class="panel-header">Derniers Retraits</div>
            <div>
                @forelse($withdrawals as $w)
                <div style="padding:12px 20px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #f8fafc">
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#1e293b">
                            {{ optional($w->driver)->first_name }} {{ optional($w->driver)->last_name }}
                        </div>
                        <div style="display:flex;gap:6px;align-items:center;margin-top:3px">
                            <span class="badge badge-warning" style="font-size:10px">{{ strtoupper($w->method) }}</span>
                            <span style="font-size:11px;color:#94a3b8">{{ $w->phone_number }}</span>
                        </div>
                        <div style="font-size:11px;color:#cbd5e1">{{ $w->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-weight:700;color:#1e293b">{{ number_format($w->amount,0,',',' ') }} XAF</div>
                        <span class="badge {{ $w->status==='success' ? 'badge-success' : ($w->status==='pending' ? 'badge-warning' : 'badge-danger') }}" style="font-size:10px">
                            @if($w->status==='success') Validé
                            @elseif($w->status==='pending') ⏳ Attente
                            @else Échoué @endif
                        </span>
                    </div>
                </div>
                @empty
                <div style="text-align:center;padding:32px;color:#94a3b8">Aucun retrait</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
