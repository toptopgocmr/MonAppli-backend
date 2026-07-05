@extends('admin.layouts.app')
@section('title', 'Retraits sociétés')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <div>
        <div class="stat-label" style="font-size:12px;color:var(--aws-sub)"><a href="{{ route('admin.payments.index') }}" style="color:inherit;text-decoration:none">Paiements</a> › Retraits sociétés</div>
        <h1 style="font-size:20px;font-weight:700;margin:4px 0 0">Retraits sociétés</h1>
    </div>
</div>

@if(session('success'))
<div style="background:#f0f9ec;border:1px solid #b7e0a0;color:#1d8102;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:16px">{{ session('success') }}</div>
@endif
@if(session('error'))
<div style="background:#fdf3f1;border:1px solid #f5b6a7;color:#d13212;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:16px">{{ session('error') }}</div>
@endif

<div class="stat-grid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:16px">
    <div class="stat-card" style="border-top:3px solid #d97706;background:#fff;border-radius:6px;padding:16px;border:1px solid #e5e7eb;border-top-width:3px">
        <div style="font-size:12px;color:#6b7280">En attente</div>
        <div style="font-size:22px;font-weight:700">{{ $pendingCount }}</div>
    </div>
    <div class="stat-card" style="border-top:3px solid #d97706;background:#fff;border-radius:6px;padding:16px;border:1px solid #e5e7eb;border-top-width:3px">
        <div style="font-size:12px;color:#6b7280">Montant en attente</div>
        <div style="font-size:22px;font-weight:700">{{ number_format($pendingAmount, 0, ',', ' ') }} FCFA</div>
    </div>
</div>

<div style="background:#fff;border:1px solid #e5e7eb;border-radius:6px">
    <div style="padding:12px 16px;border-bottom:1px solid #e5e7eb;display:flex;gap:8px">
        <form method="GET" style="display:flex;gap:8px">
            <select name="status" onchange="this.form.submit()" style="border:1px solid #d1d5db;border-radius:4px;padding:6px 10px;font-size:13px">
                <option value="">Tous statuts</option>
                <option value="pending" {{ request('status')==='pending'?'selected':'' }}>En attente</option>
                <option value="success" {{ request('status')==='success'?'selected':'' }}>Payé</option>
                <option value="failed" {{ request('status')==='failed'?'selected':'' }}>Rejeté</option>
            </select>
        </form>
    </div>
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:13px">
            <thead>
                <tr style="background:#f9fafb;text-align:left">
                    <th style="padding:10px 16px">Société</th>
                    <th style="padding:10px 16px">Montant</th>
                    <th style="padding:10px 16px">Méthode</th>
                    <th style="padding:10px 16px">Statut</th>
                    <th style="padding:10px 16px">Demandé le</th>
                    <th style="padding:10px 16px">Référence</th>
                    <th style="padding:10px 16px">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($withdrawals as $w)
                @php
                    $stMap = ['pending'=>['#fef3c7;color:#92400e','En attente'],'success'=>['#dcfce7;color:#166534','Payé'],'failed'=>['#fee2e2;color:#991b1b','Rejeté']];
                    $sc = $stMap[$w->status] ?? ['#f3f4f6;color:#374151', $w->status];
                    $methodLabel = ['mobile_money'=>'Mobile Money','bank'=>'Virement bancaire','manual'=>'Manuel'][$w->method] ?? ($w->method ?? '—');
                @endphp
                <tr style="border-top:1px solid #f0f0f0">
                    <td style="padding:10px 16px;font-weight:600">{{ $w->company->name ?? '—' }}</td>
                    <td style="padding:10px 16px">{{ number_format($w->amount, 0, ',', ' ') }} FCFA</td>
                    <td style="padding:10px 16px">
                        {{ $methodLabel }}
                        @if($w->country)<div style="font-size:11px;color:#6b7280">{{ $w->country }}{{ $w->phone_number ? ' · '.$w->phone_number : '' }}</div>@endif
                    </td>
                    <td style="padding:10px 16px"><span style="background:{{ $sc[0] }};padding:2px 8px;border-radius:10px;font-size:12px">{{ $sc[1] }}</span></td>
                    <td style="padding:10px 16px">{{ $w->created_at->format('d/m/Y H:i') }}</td>
                    <td style="padding:10px 16px">{{ $w->transaction_ref ?? '—' }}</td>
                    <td style="padding:10px 16px">
                        @if($w->status === 'pending')
                        <div style="display:flex;gap:6px">
                            <form method="POST" action="{{ route('admin.company-withdrawals.approve', $w->id) }}">
                                @csrf
                                <button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:4px;padding:5px 10px;font-size:12px;cursor:pointer">Approuver</button>
                            </form>
                            <form method="POST" action="{{ route('admin.company-withdrawals.reject', $w->id) }}" onsubmit="return confirm('Rejeter ce retrait ?')">
                                @csrf
                                <button type="submit" style="background:#dc2626;color:#fff;border:none;border-radius:4px;padding:5px 10px;font-size:12px;cursor:pointer">Rejeter</button>
                            </form>
                        </div>
                        @else
                        <span style="color:#9ca3af;font-size:12px">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:30px;color:#9ca3af">Aucune demande de retrait société.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($withdrawals->hasPages())
    <div style="padding:12px 16px;border-top:1px solid #e5e7eb">{{ $withdrawals->links() }}</div>
    @endif
</div>
@endsection
