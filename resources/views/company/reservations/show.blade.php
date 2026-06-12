@extends('company.layouts.app')
@section('title', 'Course #' . $trip->id)

@section('content')

<style>
@media print {
    .aws-topbar, .aws-sidebar, .aws-footer, .no-print { display: none !important; }
    .aws-main { margin: 0 !important; }
    .aws-content { padding: 0 !important; }
    .receipt-card { box-shadow: none !important; border: 1px solid #ccc !important; }
    body { background: white !important; }
}
</style>

<div class="aws-crumb">
    <a href="{{ route('company.dashboard') }}">Dashboard</a> ›
    <a href="{{ route('company.reservations.index') }}">Réservations</a> ›
    Course #{{ $trip->id }}
</div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px" class="no-print">
    <div class="aws-page-title">Course #{{ $trip->id }}</div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('company.reservations.index') }}" class="aws-btn aws-btn-normal">← Retour</a>
        <button onclick="window.print()" class="aws-btn aws-btn-primary">🖨 Imprimer le reçu</button>
    </div>
</div>

@php
    $statusMap = [
        'pending'     => ['aws-badge-yellow', 'En attente'],
        'accepted'    => ['aws-badge-blue',   'Acceptée'],
        'in_progress' => ['aws-badge-blue',   'En cours'],
        'completed'   => ['aws-badge-green',  'Terminée'],
        'cancelled'   => ['aws-badge-red',    'Annulée'],
    ];
    $sc = $statusMap[$trip->status] ?? ['aws-badge-gray', $trip->status];
    $company = auth('company')->user();
    $commission = ($trip->amount ?? 0) * $company->commission_rate / 100;
    $net = ($trip->amount ?? 0) - $commission;
@endphp

<div style="max-width:720px" id="receipt-area">

    <!-- REÇU IMPRIMABLE -->
    <div class="aws-panel receipt-card">
        <!-- En-tête reçu -->
        <div style="background:var(--aws-nav);color:#fff;padding:20px 24px;border-radius:4px 4px 0 0;display:flex;align-items:center;justify-content:space-between">
            <div>
                <div style="font-size:20px;font-weight:900;letter-spacing:-0.5px">
                    <span style="color:#1DA1F2">TopTop</span><span style="color:var(--aws-orange)">Go</span>
                </div>
                <div style="font-size:12px;color:rgba(255,255,255,.7);margin-top:2px">{{ $company->name }}</div>
            </div>
            <div style="text-align:right">
                <div style="font-size:13px;font-weight:700">REÇU DE COURSE</div>
                <div style="font-size:12px;color:rgba(255,255,255,.7)">#{{ $trip->id }}</div>
                <div style="font-size:11px;color:rgba(255,255,255,.6);margin-top:2px">{{ $trip->created_at->format('d/m/Y à H:i') }}</div>
            </div>
        </div>

        <!-- Statut -->
        <div style="padding:12px 24px;border-bottom:1px solid var(--aws-border);display:flex;align-items:center;justify-content:space-between;background:#fafafa">
            <span style="font-size:13px;color:var(--aws-sub)">Statut de la course</span>
            <span class="aws-badge {{ $sc[0] }}">{{ $sc[1] }}</span>
        </div>

        <!-- Trajet -->
        <div style="padding:20px 24px;border-bottom:1px solid var(--aws-border)">
            <div style="font-size:12px;font-weight:700;color:var(--aws-sub);text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px">Trajet</div>
            <div style="display:flex;gap:12px;align-items:flex-start">
                <div style="display:flex;flex-direction:column;align-items:center;gap:4px;margin-top:3px">
                    <div style="width:10px;height:10px;border-radius:50%;background:#1d8102;flex-shrink:0"></div>
                    <div style="width:2px;height:30px;background:#d5dbdb"></div>
                    <div style="width:10px;height:10px;border-radius:50%;background:#d13212;flex-shrink:0"></div>
                </div>
                <div style="flex:1">
                    <div style="margin-bottom:14px">
                        <div style="font-size:11px;color:var(--aws-sub);text-transform:uppercase">Départ</div>
                        <div style="font-size:14px;font-weight:600;color:var(--aws-header)">{{ $trip->departure ?? '—' }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px;color:var(--aws-sub);text-transform:uppercase">Arrivée</div>
                        <div style="font-size:14px;font-weight:600;color:var(--aws-header)">{{ $trip->destination ?? '—' }}</div>
                    </div>
                </div>
                @if($trip->distance_km || $trip->duration)
                <div style="text-align:right">
                    @if($trip->distance_km)
                    <div style="font-size:11px;color:var(--aws-sub)">Distance</div>
                    <div style="font-size:14px;font-weight:700;color:var(--aws-header)">{{ $trip->distance_km }} km</div>
                    @endif
                    @if($trip->duration)
                    <div style="font-size:11px;color:var(--aws-sub);margin-top:8px">Durée</div>
                    <div style="font-size:14px;font-weight:700;color:var(--aws-header)">{{ $trip->duration }} min</div>
                    @endif
                </div>
                @endif
            </div>
        </div>

        <!-- Chauffeur -->
        @if($trip->driver)
        <div style="padding:16px 24px;border-bottom:1px solid var(--aws-border)">
            <div style="font-size:12px;font-weight:700;color:var(--aws-sub);text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px">Chauffeur</div>
            <div style="display:flex;align-items:center;gap:12px">
                @if($trip->driver->profile_photo)
                    <img src="{{ $trip->driver->profile_photo }}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:1px solid var(--aws-border)">
                @else
                    <div style="width:36px;height:36px;border-radius:50%;background:#0073bb;color:#fff;font-size:14px;font-weight:700;display:flex;align-items:center;justify-content:center">
                        {{ strtoupper(substr($trip->driver->first_name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <div style="font-weight:600;font-size:14px;color:var(--aws-header)">{{ $trip->driver->first_name }} {{ $trip->driver->last_name }}</div>
                    <div style="font-size:12px;color:var(--aws-sub)">{{ $trip->driver->phone }}</div>
                    @if($trip->driver->vehicle_plate)
                        <code style="font-size:11px;background:#f2f3f3;padding:1px 6px;border-radius:2px;border:1px solid var(--aws-border);margin-top:2px;display:inline-block">{{ $trip->driver->vehicle_plate }}</code>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Détail financier -->
        <div style="padding:20px 24px">
            <div style="font-size:12px;font-weight:700;color:var(--aws-sub);text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px">Détail financier</div>
            <div style="display:flex;flex-direction:column;gap:8px">
                <div style="display:flex;justify-content:space-between;font-size:13px">
                    <span style="color:var(--aws-sub)">Montant brut de la course</span>
                    <span style="font-weight:600;color:var(--aws-header)">{{ number_format($trip->amount ?? 0, 0, ',', ' ') }} FCFA</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px">
                    <span style="color:var(--aws-sub)">Commission plateforme ({{ $company->commission_rate }}%)</span>
                    <span style="font-weight:600;color:#d13212">− {{ number_format($commission, 0, ',', ' ') }} FCFA</span>
                </div>
                <div style="border-top:2px solid var(--aws-border);margin-top:6px;padding-top:10px;display:flex;justify-content:space-between;font-size:15px">
                    <span style="font-weight:700;color:var(--aws-header)">Net société</span>
                    <span style="font-weight:700;color:#1d8102;font-size:17px">{{ number_format($net, 0, ',', ' ') }} FCFA</span>
                </div>
            </div>
        </div>

        <!-- Footer reçu -->
        <div style="background:#fafafa;border-top:1px solid var(--aws-border);padding:12px 24px;border-radius:0 0 4px 4px;text-align:center;font-size:11px;color:var(--aws-sub)">
            Document généré le {{ now()->format('d/m/Y à H:i') }} — {{ $company->name }} — Panel Société TopTopGo
        </div>
    </div>

</div>
@endsection
