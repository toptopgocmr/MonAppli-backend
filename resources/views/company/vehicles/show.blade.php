@extends('company.layouts.app')
@section('title', 'Véhicule — ' . $vehicle->plate)

@section('content')

<div class="aws-crumb">
    <a href="{{ route('company.dashboard') }}">Dashboard</a> ›
    <a href="{{ route('company.vehicles.index') }}">Flotte Véhicules</a> ›
    {{ $vehicle->plate }}
</div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <div class="aws-page-title">{{ $vehicle->brand }} {{ $vehicle->model }} — <code style="font-size:16px">{{ $vehicle->plate }}</code></div>
    <a href="{{ route('company.vehicles.edit', $vehicle->id) }}" class="aws-btn aws-btn-normal">Modifier le véhicule</a>
</div>

@if(session('success'))
<div style="background:#f0f9ec;border:1px solid #b7e0a0;color:#1d8102;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:16px">{{ session('success') }}</div>
@endif
@if($errors->any())
<div style="background:#fdf3f1;border:1px solid #f5b6a7;color:#d13212;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:16px">
    @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
</div>
@endif

<div style="display:grid;grid-template-columns:1.3fr 1fr;gap:16px">

    <!-- Chauffeurs assignés + créneaux -->
    <div class="aws-panel">
        <div class="aws-panel-header"><span class="aws-panel-title">Chauffeurs assignés & créneaux</span></div>

        <div style="overflow-x:auto">
            <table class="aws-table">
                <thead>
                    <tr>
                        <th>Chauffeur</th>
                        <th>Créneau</th>
                        <th>Horaire</th>
                        <th>Principal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicle->shifts->where('status', 'active') as $shift)
                    <tr>
                        <td style="font-weight:600">{{ $shift->driver->first_name ?? '—' }} {{ $shift->driver->last_name ?? '' }}</td>
                        <td>
                            @if($shift->specific_date)
                                <span class="aws-badge aws-badge-blue">{{ \Carbon\Carbon::parse($shift->specific_date)->format('d/m/Y') }}</span>
                            @else
                                <span class="aws-badge aws-badge-gray">{{ \App\Models\VehicleDriverShift::DAYS[$shift->day_of_week] ?? '—' }} (récurrent)</span>
                            @endif
                        </td>
                        <td style="color:var(--aws-sub)">
                            {{ $shift->start_time ? \Carbon\Carbon::parse($shift->start_time)->format('H:i') : '—' }}
                            –
                            {{ $shift->end_time ? \Carbon\Carbon::parse($shift->end_time)->format('H:i') : '—' }}
                        </td>
                        <td>{{ $shift->is_primary ? '★' : '—' }}</td>
                        <td>
                            <form method="POST" action="{{ route('company.vehicles.shifts.destroy', [$vehicle->id, $shift->id]) }}" onsubmit="return confirm('Retirer ce créneau ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="aws-btn aws-btn-danger" style="padding:4px 10px;font-size:12px">Retirer</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--aws-sub)">Aucun chauffeur assigné à ce véhicule.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Formulaire d'assignation -->
    <div class="aws-panel">
        <div class="aws-panel-header"><span class="aws-panel-title">Assigner un chauffeur</span></div>
        <div class="aws-panel-body">

            @if($availableDrivers->isEmpty())
            <div class="aws-alert" style="background:#fef9f0;border:1px solid #f5d798;color:#8a6116">
                Tous vos chauffeurs sont déjà assignés à ce véhicule, ou vous n'avez pas encore de chauffeur.
            </div>
            @else
            <form method="POST" action="{{ route('company.vehicles.shifts.store', $vehicle->id) }}" id="shiftForm">
            @csrf

            <div class="aws-field">
                <label class="aws-label">Chauffeur</label>
                <select name="driver_id" required class="aws-input">
                    <option value="">— Sélectionner —</option>
                    @foreach($availableDrivers as $driver)
                        <option value="{{ $driver->id }}">{{ $driver->first_name }} {{ $driver->last_name }} ({{ $driver->phone }})</option>
                    @endforeach
                </select>
            </div>

            <div class="aws-field">
                <label class="aws-label">Type de planning</label>
                <div style="display:flex;gap:16px;font-size:13px">
                    <label><input type="radio" name="schedule_mode" value="recurring" checked onchange="toggleScheduleMode()"> Récurrent (jour de semaine)</label>
                    <label><input type="radio" name="schedule_mode" value="specific" onchange="toggleScheduleMode()"> Date précise</label>
                </div>
            </div>

            <div class="aws-field" id="dayOfWeekField">
                <label class="aws-label">Jour de la semaine</label>
                <select name="day_of_week" class="aws-input">
                    @foreach(['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'] as $i => $label)
                        <option value="{{ $i }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="aws-field" id="specificDateField" style="display:none">
                <label class="aws-label">Date</label>
                <input type="date" name="specific_date" class="aws-input">
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

            <button type="submit" class="aws-btn aws-btn-primary" style="width:100%">Assigner</button>
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

</div>
@endsection
