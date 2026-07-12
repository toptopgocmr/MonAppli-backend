@extends('company.layouts.app')
@section('title', 'Planning chauffeurs')

@section('content')

<div class="aws-crumb"><a href="{{ route('company.dashboard') }}">Dashboard</a> › <a href="{{ route('company.vehicles.index') }}">Flotte Véhicules</a> › Planning</div>
<div class="aws-page-title" style="margin-bottom:16px">Planning chauffeurs</div>

@if(session('success'))
<div style="background:#f0f9ec;border:1px solid #b7e0a0;color:#1d8102;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:16px">{{ session('success') }}</div>
@endif
@if($errors->any())
<div style="background:#fdf3f1;border:1px solid #f5b6a7;color:#d13212;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:16px">
    @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
</div>
@endif

<div class="aws-alert" style="background:#eef7ff;border:1px solid #b8daf5;color:#0073bb;margin-bottom:16px">
    Ce planning ne concerne que les chauffeurs rattachés à votre société.
</div>

<!-- Planifier un chauffeur -->
<div class="aws-panel" style="margin-bottom:16px">
    <div class="aws-panel-header"><span class="aws-panel-title">Planifier un chauffeur</span></div>
    <div class="aws-panel-body">

        @if($vehicles->isEmpty() || $drivers->isEmpty())
        <div class="aws-alert" style="background:#fef9f0;border:1px solid #f5d798;color:#8a6116">
            Il vous faut au moins un véhicule et un chauffeur pour créer un créneau.
        </div>
        @else
        <form method="POST" action="{{ route('company.schedule.store') }}" id="scheduleForm">
        @csrf

        <div class="aws-grid-2">
            <div class="aws-field">
                <label class="aws-label">Véhicule</label>
                <select name="vehicle_id" required class="aws-input">
                    <option value="">— Sélectionner —</option>
                    @foreach($vehicles as $v)
                        <option value="{{ $v->id }}" {{ old('vehicle_id') == $v->id ? 'selected' : '' }}>{{ $v->plate }} @if($v->brand || $v->model)— {{ trim($v->brand.' '.$v->model) }}@endif</option>
                    @endforeach
                </select>
            </div>
            <div class="aws-field">
                <label class="aws-label">Chauffeur</label>
                <select name="driver_id" required class="aws-input">
                    <option value="">— Sélectionner —</option>
                    @foreach($drivers as $d)
                        <option value="{{ $d->id }}" {{ old('driver_id') == $d->id ? 'selected' : '' }}>{{ $d->first_name }} {{ $d->last_name }} ({{ $d->phone }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="aws-field">
            <label class="aws-label">Type de planning</label>
            <div style="display:flex;gap:16px;font-size:13px">
                <label><input type="radio" name="schedule_mode" value="recurring" checked onchange="toggleScheduleMode()"> Récurrent (jour de semaine)</label>
                <label><input type="radio" name="schedule_mode" value="specific" onchange="toggleScheduleMode()"> Date précise</label>
            </div>
        </div>

        <div class="aws-grid-2">
            <div class="aws-field" id="dayOfWeekField">
                <label class="aws-label">Jour de la semaine</label>
                <select name="day_of_week" class="aws-input">
                    @foreach($days as $i => $label)
                        <option value="{{ $i }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="aws-field" id="specificDateField" style="display:none">
                <label class="aws-label">Date</label>
                <input type="date" name="specific_date" class="aws-input">
            </div>
        </div>

        <div class="aws-grid-2">
            <div class="aws-field">
                <label class="aws-label">Heure de début</label>
                <input type="time" name="start_time" class="aws-input">
            </div>
            <div class="aws-field">
                <label class="aws-label">Heure de fin</label>
                <input type="time" name="end_time" class="aws-input">
            </div>
        </div>

        <div class="aws-field">
            <label style="font-size:13px"><input type="checkbox" name="is_primary" value="1"> Chauffeur principal de ce véhicule</label>
        </div>

        <button type="submit" class="aws-btn aws-btn-primary">Planifier ce créneau</button>
        </form>

        <script>
            function toggleScheduleMode() {
                const recurring = document.querySelector('input[name="schedule_mode"][value="recurring"]').checked;
                document.getElementById('dayOfWeekField').style.display = recurring ? 'block' : 'none';
                document.getElementById('specificDateField').style.display = recurring ? 'none' : 'block';
            }
        </script>
        @endif
    </div>
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
