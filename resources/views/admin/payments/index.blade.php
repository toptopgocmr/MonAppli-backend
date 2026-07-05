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
        <div style="display:flex;gap:8px">
            <a href="{{ route('admin.payments.withdrawals') }}" class="btn btn-secondary">Retraits chauffeurs</a>
            <a href="{{ route('admin.payments.export') }}?period={{ $period }}" class="btn btn-success">Exporter CSV</a>
        </div>
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

    {{-- ===== PASSERELLES DE PAIEMENT (Peex / Flutterwave / Stripe) ===== --}}
    <div class="panel" style="padding:0;overflow:hidden">
        <div class="panel-header">Passerelles de paiement</div>
        <div style="overflow-x:auto">
            <table class="ttg-table">
                <thead>
                    <tr>
                        <th>Passerelle</th>
                        <th style="text-align:right">Montant collecté</th>
                        <th style="text-align:right">Paiements réussis</th>
                        <th style="text-align:right;color:#f97316">En attente</th>
                        <th style="text-align:right;color:#ef4444">Échoués</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gatewayStats as $key => $g)
                    <tr>
                        <td style="display:flex;align-items:center;gap:8px;font-weight:700;color:{{ $g['color'] }}">
                            <span>{{ $g['icon'] }}</span> {{ $g['label'] }}
                            @if($key === 'peex')
                                <span style="font-size:10px;font-weight:600;color:#94a3b8;border:1px solid #e2e8f0;border-radius:6px;padding:1px 6px">
                                    sandbox
                                </span>
                            @endif
                        </td>
                        <td style="text-align:right;font-weight:700;color:{{ $g['color'] }}">{{ number_format($g['total'],0,',',' ') }} FCFA</td>
                        <td style="text-align:right">{{ $g['count'] }}</td>
                        <td style="text-align:right;color:#f97316">{{ $g['pending'] }}</td>
                        <td style="text-align:right;color:#ef4444">{{ $g['failed'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== FILTRES PAIEMENTS ===== --}}
    <form method="GET" action="{{ route('admin.payments.index') }}" class="filter-bar" style="flex-wrap:wrap;gap:10px">
        <input type="hidden" name="period" value="{{ $period }}">
        <div style="display:flex;flex-direction:column;gap:4px">
            <label class="ttg-label">Méthode</label>
            <select name="method" class="ttg-select">
                <option value="">Toutes</option>
                @foreach(['mtn'=>'MTN Money','orange'=>'Orange Money','airtel'=>'Airtel Money','moov'=>'Moov Money'] as $val=>$lbl)
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

</div>
@endsection
