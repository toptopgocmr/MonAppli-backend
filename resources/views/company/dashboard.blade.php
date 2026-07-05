@extends('company.layouts.app')
@section('title', 'Dashboard')

@section('content')

<div class="aws-crumb"><a href="{{ route('company.dashboard') }}">Dashboard</a></div>
<div class="aws-page-title" style="margin-bottom:16px">
    {{ auth('company')->user()->name }}
    <span style="font-size:13px;font-weight:400;color:var(--aws-sub);margin-left:8px">{{ auth('company')->user()->type_libelle }}</span>
</div>

<!-- KPI Cards -->
<div class="aws-stat-grid" style="grid-template-columns:repeat(4,1fr)">

    <a href="{{ route('company.drivers.index') }}" class="aws-stat-card aws-stat-card-link" style="border-top:3px solid #0073bb">
        <div class="aws-stat-label">Chauffeurs total</div>
        <div class="aws-stat-value">{{ $totalDrivers }}</div>
        <div class="aws-stat-sub" style="color:#1d8102">{{ $approvedDrivers }} approuvés</div>
    </a>

    <a href="{{ route('company.drivers.index', ['driver_status' => 'online']) }}" class="aws-stat-card aws-stat-card-link" style="border-top:3px solid #1d8102">
        <div class="aws-stat-label">En ligne maintenant</div>
        <div class="aws-stat-value" style="display:flex;align-items:center;gap:8px">
            {{ $activeDrivers }}
            <span style="width:10px;height:10px;background:#1d8102;border-radius:50%;display:inline-block"></span>
        </div>
        <div class="aws-stat-sub">chauffeurs actifs</div>
    </a>

    <a href="{{ route('company.reservations.index', ['period' => 'month']) }}" class="aws-stat-card aws-stat-card-link" style="border-top:3px solid var(--aws-orange)">
        <div class="aws-stat-label">Courses ce mois</div>
        <div class="aws-stat-value">{{ $tripsThisMonth }}</div>
        <div class="aws-stat-sub">{{ $totalTrips }} au total</div>
    </a>

    <a href="{{ route('company.revenus.index') }}" class="aws-stat-card aws-stat-card-link" style="border-top:3px solid #8a6116">
        <div class="aws-stat-label">Revenus ce mois</div>
        <div class="aws-stat-value" style="font-size:20px">{{ number_format($revenueThisMonth, 0, ',', ' ') }}<span style="font-size:12px;font-weight:400;margin-left:4px">FCFA</span></div>
        <div class="aws-stat-sub">commission {{ $company->commission_rate }}% déduite</div>
    </a>

</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

    <!-- Chauffeurs récents -->
    <div class="aws-panel">
        <div class="aws-panel-header">
            <span class="aws-panel-title">Chauffeurs récents</span>
            <a href="{{ route('company.drivers.index') }}" style="font-size:12px;color:var(--aws-blue);text-decoration:none">Voir tout →</a>
        </div>
        @forelse($recentDrivers as $driver)
        @php
            $kycMap = ['approved'=>['aws-badge-green','Approuvé'],'pending'=>['aws-badge-yellow','En attente'],'rejected'=>['aws-badge-red','Rejeté'],'suspended'=>['aws-badge-red','Suspendu']];
            $kc = $kycMap[$driver->status] ?? ['aws-badge-gray',$driver->status];
        @endphp
        <div style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid #f2f3f3">
            @if($driver->profile_photo)
                <img src="{{ $driver->profile_photo }}" style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:1px solid var(--aws-border)">
            @else
                <div style="width:34px;height:34px;border-radius:50%;background:#0073bb;color:#fff;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    {{ strtoupper(substr($driver->first_name, 0, 1)) }}
                </div>
            @endif
            <div style="flex:1;min-width:0">
                <div style="font-size:13px;font-weight:600;color:var(--aws-header)">{{ $driver->first_name }} {{ $driver->last_name }}</div>
                <div style="font-size:12px;color:var(--aws-sub)">{{ $driver->phone }}</div>
            </div>
            <span class="aws-badge {{ $kc[0] }}">{{ $kc[1] }}</span>
        </div>
        @empty
        <div style="padding:32px 20px;text-align:center;color:var(--aws-sub);font-size:13px">Aucun chauffeur pour le moment</div>
        @endforelse
        <div style="padding:12px 20px;background:#fafafa;border-top:1px solid var(--aws-border);border-radius:0 0 4px 4px">
            <a href="{{ route('company.drivers.create') }}" class="aws-btn aws-btn-primary" style="font-size:12px;padding:5px 12px">+ Nouveau chauffeur</a>
        </div>
    </div>

    <!-- Courses récentes -->
    <div class="aws-panel">
        <div class="aws-panel-header">
            <span class="aws-panel-title">Courses récentes</span>
            <a href="{{ route('company.reservations.index') }}" style="font-size:12px;color:var(--aws-blue);text-decoration:none">Voir tout →</a>
        </div>
        @forelse($recentTrips as $trip)
        @php
            $stMap = ['pending'=>['aws-badge-yellow','En attente'],'accepted'=>['aws-badge-blue','Acceptée'],'in_progress'=>['aws-badge-blue','En cours'],'completed'=>['aws-badge-green','Terminée'],'cancelled'=>['aws-badge-red','Annulée']];
            $sc = $stMap[$trip->status] ?? ['aws-badge-gray',$trip->status];
        @endphp
        <div style="padding:12px 20px;border-bottom:1px solid #f2f3f3">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
                <span style="font-size:13px;font-weight:600;color:var(--aws-header)">{{ $trip->driver->first_name ?? '—' }} {{ $trip->driver->last_name ?? '' }}</span>
                <span style="font-size:13px;font-weight:700;color:#0073bb">{{ number_format($trip->amount ?? 0, 0, ',', ' ') }} FCFA</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:12px;color:var(--aws-sub);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:200px">{{ $trip->departure ?? '—' }} → {{ $trip->destination ?? '—' }}</span>
                <span class="aws-badge {{ $sc[0] }}" style="font-size:10px">{{ $sc[1] }}</span>
            </div>
        </div>
        @empty
        <div style="padding:32px 20px;text-align:center;color:var(--aws-sub);font-size:13px">Aucune course pour le moment</div>
        @endforelse
    </div>

</div>

@endsection
