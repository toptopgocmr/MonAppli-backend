@extends('company.layouts.app')
@section('title', 'Profil — ' . $driver->first_name . ' ' . $driver->last_name)

@section('content')

<div class="aws-crumb">
    <a href="{{ route('company.dashboard') }}">Dashboard</a> ›
    <a href="{{ route('company.drivers.index') }}">Chauffeurs</a> ›
    {{ $driver->first_name }} {{ $driver->last_name }}
</div>

<!-- Header -->
<div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px">
    <div style="display:flex;align-items:center;gap:14px">
        @if($driver->profile_photo)
            <img src="{{ $driver->profile_photo }}" style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid var(--aws-border)">
        @else
            <div style="width:48px;height:48px;border-radius:50%;background:#0073bb;color:#fff;font-size:20px;font-weight:700;display:flex;align-items:center;justify-content:center">
                {{ strtoupper(substr($driver->first_name, 0, 1)) }}
            </div>
        @endif
        <div>
            <div class="aws-page-title">{{ $driver->first_name }} {{ $driver->last_name }}</div>
            <div style="font-size:13px;color:var(--aws-sub)">{{ $driver->phone }}</div>
        </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="{{ route('company.drivers.edit', $driver->id) }}" class="aws-btn aws-btn-primary">✎ Modifier</a>
        @if($driver->status === 'approved')
            <form method="POST" action="{{ route('company.drivers.suspend', $driver->id) }}" style="display:inline" onsubmit="return confirm('Suspendre ce chauffeur ?')">
                @csrf
                <button type="submit" class="aws-btn" style="background:#fff;border-color:#df8244;color:#df8244">⏸ Suspendre</button>
            </form>
        @else
            <form method="POST" action="{{ route('company.drivers.activate', $driver->id) }}" style="display:inline">
                @csrf
                <button type="submit" class="aws-btn aws-btn-normal">▶ Activer</button>
            </form>
        @endif
        <form method="POST" action="{{ route('company.drivers.remove', $driver->id) }}" style="display:inline" onsubmit="return confirm('Retirer ce chauffeur de votre société ?')">
            @csrf
            <button type="submit" class="aws-btn aws-btn-danger">✕ Retirer</button>
        </form>
    </div>
</div>

<!-- Details -->
<div class="aws-panel">
    <div class="aws-panel-header">
        <span class="aws-panel-title">Détails</span>
        @php
            $kycMap = ['approved'=>['aws-badge-green','Approuvé'],'pending'=>['aws-badge-yellow','En attente'],'rejected'=>['aws-badge-red','Rejeté'],'suspended'=>['aws-badge-red','Suspendu']];
            $kc = $kycMap[$driver->status] ?? ['aws-badge-gray',$driver->status];
        @endphp
        <div style="display:flex;gap:8px;align-items:center">
            <span class="aws-badge {{ $kc[0] }}">{{ $kc[1] }}</span>
            @if($driver->driver_status === 'online')
                <span class="aws-badge aws-badge-green">En ligne</span>
            @else
                <span class="aws-badge aws-badge-gray">Hors ligne</span>
            @endif
        </div>
    </div>
    <div class="aws-panel-body">
        <div class="aws-detail-grid">
            @php $fields = [
                ['Véhicule',   ($driver->getRawOriginal('vehicle_brand') ?? '—') . ' ' . ($driver->getRawOriginal('vehicle_model') ?? '')],
                ['Plaque',     $driver->vehicle_plate ?? '—'],
                ['Couleur',    $driver->getRawOriginal('vehicle_color') ?? '—'],
                ['Type',       $driver->getRawOriginal('vehicle_type') ?? '—'],
                ['Ville',      $driver->getRawOriginal('vehicle_city') ?? '—'],
                ['Naissance',  $driver->birth_date ? \Carbon\Carbon::parse($driver->birth_date)->format('d/m/Y') : '—'],
                ['Lieu naiss.', $driver->birth_place ?? '—'],
                ['Inscrit le', $driver->created_at->format('d/m/Y')],
            ]; @endphp
            @foreach($fields as [$label, $value])
            <div class="aws-detail-item">
                <div class="aws-detail-label">{{ $label }}</div>
                <div class="aws-detail-value">{{ $value }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Documents KYC -->
<div class="aws-panel">
    <div class="aws-panel-header"><span class="aws-panel-title">Documents KYC</span></div>
    <div class="aws-panel-body">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px">
            @foreach([
                ['CNI Recto','id_card_front'],['CNI Verso','id_card_back'],
                ['Permis Recto','license_front'],['Permis Verso','license_back'],
                ['Carte grise','vehicle_registration'],['Assurance','insurance']
            ] as [$label, $field])
            <div style="border:1px solid var(--aws-border);border-radius:4px;overflow:hidden">
                <div style="background:#fafafa;padding:8px 12px;border-bottom:1px solid var(--aws-border);font-size:12px;font-weight:700;color:var(--aws-header)">{{ $label }}</div>
                <div style="padding:8px">
                    @if($driver->{$field})
                        @php $ext = strtolower(pathinfo(parse_url($driver->{$field}, PHP_URL_PATH), PATHINFO_EXTENSION)); @endphp
                        @if(in_array($ext, ['jpg','jpeg','png','webp']))
                            <a href="{{ $driver->{$field} }}" target="_blank">
                                <img src="{{ $driver->{$field} }}" style="width:100%;height:90px;object-fit:cover;border-radius:3px">
                            </a>
                        @else
                            <a href="{{ $driver->{$field} }}" target="_blank" style="font-size:12px;color:var(--aws-blue)">Voir le fichier →</a>
                        @endif
                    @else
                        <div style="height:90px;background:#f2f3f3;border-radius:3px;display:flex;align-items:center;justify-content:center;font-size:12px;color:var(--aws-sub)">Non fourni</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

@endsection
