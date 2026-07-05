@extends('company.layouts.app')
@section('title', 'Planning chauffeurs')

@section('content')

<div class="aws-crumb"><a href="{{ route('company.dashboard') }}">Dashboard</a> › <a href="{{ route('company.vehicles.index') }}">Flotte Véhicules</a> › Planning</div>
<div class="aws-page-title" style="margin-bottom:16px">Planning chauffeurs</div>

<div class="aws-alert" style="background:#eef7ff;border:1px solid #b8daf5;color:#0073bb;margin-bottom:16px">
    Ce planning ne concerne que les chauffeurs rattachés à votre société. Pour assigner un créneau, ouvrez la fiche du véhicule concerné.
</div>

<!-- Semaine type (récurrent) -->
<div class="aws-panel" style="margin-bottom:16px">
    <div class="aws-panel-header"><span class="aws-panel-title">Semaine type (créneaux récurrents)</span></div>
    <div style="overflow-x:auto">
        <table class="aws-table">
            <thead>
                <tr>
                    @foreach($days as $i => $label)
                        <th style="text-align:center">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    @foreach($days as $i => $label)
                    <td style="vertical-align:top;min-width:150px">
                        @forelse(($recurring[$i] ?? collect()) as $shift)
                        <div style="background:#eef7ff;border:1px solid #b8daf5;border-radius:6px;padding:6px 8px;margin-bottom:6px;font-size:12px">
                            <div style="font-weight:700;color:var(--aws-header)">{{ $shift->driver->first_name ?? '—' }} {{ $shift->driver->last_name ?? '' }}</div>
                            <div style="color:var(--aws-sub)">{{ $shift->vehicle->plate ?? '—' }}</div>
                            <div style="color:var(--aws-sub)">
                                {{ $shift->start_time ? \Carbon\Carbon::parse($shift->start_time)->format('H:i') : '—' }}
                                – {{ $shift->end_time ? \Carbon\Carbon::parse($shift->end_time)->format('H:i') : '—' }}
                            </div>
                        </div>
                        @empty
                        <div style="color:var(--aws-sub);font-size:12px;text-align:center;padding:8px 0">—</div>
                        @endforelse
                    </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Dates précises à venir -->
<div class="aws-panel">
    <div class="aws-panel-header"><span class="aws-panel-title">Créneaux à date précise (30 prochains jours)</span></div>
    <div style="overflow-x:auto">
        <table class="aws-table">
            <thead>
                <tr><th>Date</th><th>Chauffeur</th><th>Véhicule</th><th>Horaire</th></tr>
            </thead>
            <tbody>
                @forelse($upcoming as $date => $shifts)
                    @foreach($shifts as $shift)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</td>
                        <td style="font-weight:600">{{ $shift->driver->first_name ?? '—' }} {{ $shift->driver->last_name ?? '' }}</td>
                        <td><code style="font-size:12px">{{ $shift->vehicle->plate ?? '—' }}</code></td>
                        <td style="color:var(--aws-sub)">
                            {{ $shift->start_time ? \Carbon\Carbon::parse($shift->start_time)->format('H:i') : '—' }}
                            – {{ $shift->end_time ? \Carbon\Carbon::parse($shift->end_time)->format('H:i') : '—' }}
                        </td>
                    </tr>
                    @endforeach
                @empty
                <tr><td colspan="4" style="text-align:center;padding:30px;color:var(--aws-sub)">Aucun créneau à date précise programmé.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
