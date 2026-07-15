@extends('company.layouts.app')
@section('title', 'Chauffeurs')

@section('content')

<div class="aws-crumb"><a href="{{ route('company.dashboard') }}">Dashboard</a> › Chauffeurs</div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <div class="aws-page-title">Mes Chauffeurs</div>
    <a href="{{ route('company.drivers.create') }}" class="aws-btn aws-btn-primary">+ Nouveau chauffeur</a>
</div>

<div class="aws-panel">
    <!-- Filters -->
    <div style="padding:12px 20px;border-bottom:1px solid var(--aws-border);display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;background:#fafafa;border-radius:4px 4px 0 0">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher..."
                   class="aws-input" style="width:220px">
            <select name="status" class="aws-input" style="width:160px">
                <option value="">Tous les statuts</option>
                <option value="approved"  {{ request('status') === 'approved'  ? 'selected' : '' }}>Approuvés</option>
                <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>En attente</option>
                <option value="rejected"  {{ request('status') === 'rejected'  ? 'selected' : '' }}>Rejetés</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspendus</option>
            </select>
            <button type="submit" class="aws-btn aws-btn-primary" style="padding:7px 14px">Filtrer</button>
        </form>
        <span style="font-size:12px;color:var(--aws-sub)">{{ $drivers->total() }} chauffeur(s)</span>
    </div>

    <!-- Table -->
    <div style="overflow-x:auto">
        <table class="aws-table">
            <thead>
                <tr>
                    <th>Chauffeur</th>
                    <th>Téléphone</th>
                    <th>Véhicule / Plaque</th>
                    <th>Statut KYC</th>
                    <th>Présence</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($drivers as $driver)
                @php
                    $kycMap = ['approved'=>['aws-badge-green','Approuvé'],'pending'=>['aws-badge-yellow','En attente'],'rejected'=>['aws-badge-red','Rejeté'],'suspended'=>['aws-badge-red','Suspendu']];
                    $kc = $kycMap[$driver->status] ?? ['aws-badge-gray',$driver->status];
                @endphp
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            @if($driver->profile_photo)
                                <img src="{{ $driver->profile_photo }}" style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:1px solid var(--aws-border)">
                            @else
                                <div style="width:32px;height:32px;border-radius:50%;background:#0073bb;color:#fff;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:center">
                                    {{ strtoupper(substr($driver->first_name, 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <div style="font-weight:600;font-size:13px">{{ $driver->first_name }} {{ $driver->last_name }}</div>
                                <div style="font-size:12px;color:var(--aws-sub)">{{ $driver->vehicle_city ?? '—' }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="color:var(--aws-sub)">{{ $driver->phone }}</td>
                    <td>
                        <div style="font-size:13px">{{ $driver->vehicle_brand ?? '—' }} {{ $driver->vehicle_model ?? '' }}</div>
                        @if($driver->vehicle_plate)
                            <code style="background:#f2f3f3;padding:2px 6px;border-radius:3px;font-size:11px;border:1px solid var(--aws-border)">{{ $driver->vehicle_plate }}</code>
                        @endif
                    </td>
                    <td><span class="aws-badge {{ $kc[0] }}">{{ $kc[1] }}</span></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                            @if($driver->driver_status === 'online')
                                <span style="display:flex;align-items:center;gap:5px;font-size:12px;color:#1d8102;font-weight:600">
                                    <span style="width:8px;height:8px;background:#1d8102;border-radius:50%"></span>En ligne
                                </span>
                            @else
                                <span style="font-size:12px;color:var(--aws-sub)">Hors ligne</span>
                            @endif

                            @if($driver->status === 'approved')
                                <form method="POST" action="{{ route('company.drivers.presence', $driver->id) }}" style="display:inline">
                                    @csrf
                                    <button type="submit" class="aws-btn aws-btn-normal" style="padding:3px 9px;font-size:11px">
                                        {{ $driver->driver_status === 'online' ? 'Mettre hors ligne' : 'Mettre en ligne' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            <a href="{{ route('company.drivers.show', $driver->id) }}" class="aws-btn aws-btn-normal" style="padding:4px 10px;font-size:12px">Voir</a>
                            <a href="{{ route('company.drivers.edit', $driver->id) }}" class="aws-btn aws-btn-normal" style="padding:4px 10px;font-size:12px">Modifier</a>
                            @if($driver->status === 'approved')
                                <form method="POST" action="{{ route('company.drivers.suspend', $driver->id) }}" style="display:inline" onsubmit="return confirm('Suspendre ce chauffeur ?')">
                                    @csrf
                                    <button type="submit" class="aws-btn" style="padding:4px 10px;font-size:12px;background:#fff;border-color:#df8244;color:#df8244">Suspendre</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('company.drivers.activate', $driver->id) }}" style="display:inline">
                                    @csrf
                                    <button type="submit" class="aws-btn aws-btn-normal" style="padding:4px 10px;font-size:12px">Activer</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:40px;color:var(--aws-sub)">
                        Aucun chauffeur dans votre société —
                        <a href="{{ route('company.drivers.create') }}" style="color:var(--aws-blue)">Créer un chauffeur</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($drivers->hasPages())
    <div style="padding:12px 20px;border-top:1px solid var(--aws-border)">
        {{ $drivers->links() }}
    </div>
    @endif
</div>
@endsection
