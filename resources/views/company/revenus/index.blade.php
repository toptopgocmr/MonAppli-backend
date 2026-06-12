@extends('company.layouts.app')
@section('title', 'Revenus & Analyses')

@section('content')

<div class="aws-crumb"><a href="{{ route('company.dashboard') }}">Dashboard</a> › Revenus</div>
<div class="aws-page-title" style="margin-bottom:16px">Revenus & Analyses</div>

<!-- KPI -->
<div class="aws-stat-grid" style="grid-template-columns:repeat(3,1fr)">
    <div class="aws-stat-card" style="border-top:3px solid #1d8102">
        <div class="aws-stat-label">Revenus ce mois</div>
        <div class="aws-stat-value" style="font-size:22px">{{ number_format($revenueThisMonth, 0, ',', ' ') }}<span style="font-size:12px;font-weight:400;margin-left:4px">FCFA</span></div>
        <div class="aws-stat-sub">{{ $tripsThisMonth }} course(s)</div>
    </div>
    <div class="aws-stat-card" style="border-top:3px solid #0073bb">
        <div class="aws-stat-label">Revenus total</div>
        <div class="aws-stat-value" style="font-size:22px">{{ number_format($revenueTotal, 0, ',', ' ') }}<span style="font-size:12px;font-weight:400;margin-left:4px">FCFA</span></div>
        <div class="aws-stat-sub">{{ $tripsTotal }} course(s) au total</div>
    </div>
    <div class="aws-stat-card" style="border-top:3px solid #d13212">
        <div class="aws-stat-label">Commission plateforme</div>
        <div class="aws-stat-value" style="color:#d13212">{{ $company->commission_rate }}%</div>
        <div class="aws-stat-sub">déduite sur chaque course</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

    <!-- Évolution mensuelle -->
    <div class="aws-panel">
        <div class="aws-panel-header"><span class="aws-panel-title">Évolution mensuelle (12 derniers mois)</span></div>
        <div class="aws-panel-body">
            @php $maxMonth = $monthlyRevenue->max('total') ?: 1; @endphp
            @forelse($monthlyRevenue as $month)
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px">
                <span style="font-size:12px;color:var(--aws-sub);width:70px;flex-shrink:0">
                    {{ \Carbon\Carbon::createFromFormat('Y-m', $month->month)->locale('fr')->translatedFormat('M Y') }}
                </span>
                <div style="flex:1;background:#f2f3f3;border-radius:2px;height:8px">
                    <div style="width:{{ ($month->total/$maxMonth)*100 }}%;background:var(--aws-orange);height:8px;border-radius:2px"></div>
                </div>
                <span style="font-size:12px;font-weight:600;color:var(--aws-header);width:110px;text-align:right;white-space:nowrap">
                    {{ number_format($month->total, 0, ',', ' ') }} FCFA
                </span>
            </div>
            @empty
            <p style="text-align:center;color:var(--aws-sub);font-size:13px;padding:20px 0">Aucune donnée disponible</p>
            @endforelse
        </div>
    </div>

    <!-- Top Chauffeurs -->
    <div class="aws-panel">
        <div class="aws-panel-header"><span class="aws-panel-title">Top Chauffeurs (ce mois)</span></div>
        <div class="aws-panel-body">
            @forelse($topDrivers as $index => $item)
            @php $driver = $item['driver']; $total = $item['total']; $count = $item['count']; @endphp
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
                <div style="width:22px;height:22px;border-radius:50%;background:var(--aws-orange);color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    {{ $index + 1 }}
                </div>
                @if($driver->profile_photo)
                    <img src="{{ $driver->profile_photo }}" style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:1px solid var(--aws-border);flex-shrink:0">
                @else
                    <div style="width:32px;height:32px;border-radius:50%;background:#0073bb;color:#fff;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        {{ strtoupper(substr($driver->first_name, 0, 1)) }}
                    </div>
                @endif
                <div style="flex:1">
                    <div style="font-size:13px;font-weight:600;color:var(--aws-header)">{{ $driver->first_name }} {{ $driver->last_name }}</div>
                    <div style="font-size:12px;color:var(--aws-sub)">{{ $count }} course(s)</div>
                </div>
                <div style="text-align:right">
                    <div style="font-size:13px;font-weight:700;color:#0073bb">{{ number_format($total, 0, ',', ' ') }}</div>
                    <div style="font-size:11px;color:var(--aws-sub)">FCFA</div>
                </div>
            </div>
            @empty
            <p style="text-align:center;color:var(--aws-sub);font-size:13px;padding:20px 0">Aucune course ce mois</p>
            @endforelse
        </div>
    </div>

</div>
@endsection
