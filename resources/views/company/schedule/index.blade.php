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
                <label class="aws-label">He