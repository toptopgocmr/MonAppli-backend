@extends('admin.layouts.app')
@section('title','Retraits chauffeurs')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px">

    {{-- Header --}}
    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px">
        <div>
            <h1 class="page-title">Retraits chauffeurs</h1>
            <p class="page-sub">
                Approuver un retrait déclenche le paiement automatique via Peex si le pays du chauffeur est couvert
                (sinon il reste manuel, comme avant). Rejeter un retrait rembourse immédiatement le wallet du chauffeur.
            </p>
        </div>
        <div style="display:flex;gap:8px">
            <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary">← Retour aux paiements</a>
            <a href="{{ route('admin.payments.withdrawals.export') }}?period={{ $period }}&status={{ request('status') }}" class="btn btn-success">Exporter CSV</a>
        </div>
    </div>

    {{-- Période --}}
    <div style="display:flex;flex-wrap:wrap;gap:8px">
        @foreach(['today'=>"Aujourd'hui",'week'=>'Cette semaine','month'=>'Ce mois','year'=>'Cette année'] as $key=>$label)
        <a href="?period={{ $key }}{{ request('status') ? '&status='.request('status') : '' }}"
           style="padding:7px 16px;border-radius:9px;font-size:13px;font-weight:600;text-decoration:none;transition:all .2s;
           {{ $period===$key ? 'background:#1DA1F2;color:#fff' : 'background:#fff;color:#475569;border:1.5px solid #e2e8f0' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    {{-- KPI --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px">
        <div class="stat-card" style="border-left:4px solid #f59e0b">
            <div class="lbl">EN ATTENTE</div>
            <div class="val" style="color:#d97706;font-size:22px">{{ $pendingCount }}</div>
            <div class="sub">{{ number_format($pendingAmount,0,',',' ') }} XAF</div>
        </div>
        <div class="stat-card" style="border-left:4px solid #dc2626">
            <div class="lbl">EN RETARD (&gt;24H)</div>
            <div class="val" style="color:#dc2626;font-size:22px">{{ $lateCount }}</div>
            <div class="sub">objectif interne : sous 24h</div>
        </div>
        <div class="stat-card" style="border-left:4px solid #22c55e">
            <div class="lbl">PAYÉS (période)</div>
            <div class="val" style="color:#16a34a;font-size:22px">{{ $paidCount }}</div>
            <div class="sub">{{ number_format($paidAmount,0,',',' ') }} XAF</div>
        </div>
        <div class="stat-card" style="border-left:4px solid #94a3b8">
            <div class="lbl">REJETÉS (période)</div>
            <div class="val" style="color:#475569;font-size:22px">{{ $rejectedCount }}</div>
            <div class="sub">remboursés automatiquement</div>
        </div>
    </div>

    {{-- Filtre statut --}}
    <form method="GET" class="filter-bar" style="gap:10px">
        <input type="hidden" name="period" value="{{ $period }}">
        <div style="display:flex;flex-direction:column;gap:4px">
            <label class="ttg-label">Statut</label>
            <select name="status" class="ttg-select" onchange="this.form.submit()">
                <option value="">Tous</option>
                @foreach(['pending'=>'⏳ En attente','success'=>'Payé','failed'=>'Rejeté / Échoué'] as $val=>$lbl)
                    <option value="{{ $val }}" {{ request('status')===$val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
    </form>

    {{-- Tableau --}}
    <div class="panel" style="padding:0;overflow:hidden">
        <div class="panel-header" style="display:flex;justify-content:space-between;align-items:center">
            <span>Retraits</span>
            <span style="font-size:12px;color:#94a3b8;font-weight:400">{{ $withdrawals->total() }} retraits</span>
        </div>
        <div style="overflow-x:auto">
            <table class="ttg-table">
                <thead>
                    <tr>
                        <th>Chauffeur</th>
                        <th>Pays véhicule</th>
                        <th>Méthode</th>
                        <th>Téléphone</th>
                        <th style="text-align:right">Montant</th>
                        <th>Demandé le</th>
                        <th style="text-align:center">Statut</th>
                        <th style="text-align:center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($withdrawals as $w)
                    @php
                        $country = strtoupper($w->driver->vehicle_country ?? '');
                        $peexCovered = in_array($country, array_map('strtoupper', config('payments.peex.disbursement_countries', ['CM'])), true);
                        $isLate = $w->status === 'pending' && $w->created_at->diffInHours(now()) >= 24;
                    @endphp
                    <tr>
                        <td class="px-4 py-3 font-medium">
                            {{ optional($w->driver)->first_name }} {{ optional($w->driver)->last_name }}
                        </td>
                        <td class="px-4 py-3 text-xs">
                            {{ $country ?: '—' }}
                            @if($w->status === 'pending')
                                <span style="font-size:10px;color:{{ $peexCovered ? '#16a34a' : '#94a3b8' }}">
                                    ({{ $peexCovered ? 'Peex auto' : 'manuel' }})
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="badge badge-warning" style="font-size:10px">{{ strtoupper($w->method) }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $w->phone_number }}</td>
                        <td class="px-4 py-3 text-right font-bold">{{ number_format($w->amount, 0, ',', ' ') }} XAF</td>
                        <td class="px-4 py-3 text-xs text-gray-400">
                            {{ $w->created_at->format('d/m/Y H:i') }}
                            @if($isLate)
                                <div style="color:#dc2626;font-weight:700;font-size:10px">⏰ en retard (&gt;24h)</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge {{ $w->status==='success' ? 'badge-success' : ($w->status==='pending' ? 'badge-warning' : 'badge-danger') }}">
                                @if($w->status==='success') Payé
                                @elseif($w->status==='pending') ⏳ Attente
                                @else Rejeté @endif
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($w->status === 'pending')
                            <div style="display:flex;justify-content:center;gap:6px">
                                <form action="{{ route('admin.payments.approve-withdrawal', $w->id) }}" method="POST">
                                    @csrf
                                    <button class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded-lg transition"
                                        onclick="return confirm('{{ $peexCovered ? 'Payer automatiquement via Peex' : 'Marquer comme payé manuellement' }} — {{ number_format($w->amount, 0) }} XAF ?')">
                                        Approuver
                                    </button>
                                </form>
                                <form action="{{ route('admin.payments.reject-withdrawal', $w->id) }}" method="POST">
                                    @csrf
                                    <button class="bg-red-500 hover:bg-red-600 text-white text-xs px-3 py-1 rounded-lg transition"
                                        onclick="return confirm('Rejeter et rembourser le wallet du chauffeur ?')">
                                        Rejeter
                                    </button>
                                </form>
                            </div>
                            @else
                            <span style="color:#94a3b8;font-size:12px">{{ optional($w->processed_at)->format('d/m/Y H:i') ?? '—' }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-gray-400 py-10">Aucun retrait</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($withdrawals->hasPages())
        <div style="padding:14px 20px;border-top:1px solid #f1f5f9">{{ $withdrawals->links() }}</div>
        @endif
    </div>
</div>
@endsection
