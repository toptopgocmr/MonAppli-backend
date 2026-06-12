@extends('company.layouts.app')
@section('title', 'Modifier Véhicule')

@section('content')

<div class="aws-crumb">
    <a href="{{ route('company.dashboard') }}">Dashboard</a> ›
    <a href="{{ route('company.vehicles.index') }}">Flotte Véhicules</a> ›
    Modifier
</div>
<div class="aws-page-title" style="margin-bottom:16px">Modifier le véhicule</div>

<div style="max-width:780px">

    <!-- Chauffeur card -->
    <div class="aws-panel" style="margin-bottom:16px">
        <div class="aws-panel-body" style="display:flex;align-items:center;gap:14px;padding:14px 20px">
            @if($driver->profile_photo)
                <img src="{{ $driver->profile_photo }}" style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:1px solid var(--aws-border)">
            @else
                <div style="width:44px;height:44px;border-radius:50%;background:#0073bb;color:#fff;font-size:18px;font-weight:700;display:flex;align-items:center;justify-content:center">
                    {{ strtoupper(substr($driver->first_name, 0, 1)) }}
                </div>
            @endif
            <div>
                <div style="font-size:15px;font-weight:700;color:var(--aws-header)">{{ $driver->first_name }} {{ $driver->last_name }}</div>
                <div style="font-size:13px;color:var(--aws-sub)">{{ $driver->phone }}</div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('company.vehicles.update', $driver->id) }}">
    @csrf @method('PUT')

    <div class="aws-panel">
        <div class="aws-panel-header"><span class="aws-panel-title">Informations du véhicule</span></div>
        <div class="aws-panel-body">

            @if($errors->any())
            <div style="background:#fdf3f1;border:1px solid #f5b6a7;color:#d13212;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:16px">
                @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
            </div>
            @endif

            <div class="aws-grid-2">
                <div class="aws-field">
                    <label class="aws-label">Marque</label>
                    <input type="text" name="vehicle_brand" value="{{ old('vehicle_brand', $driver->vehicle_brand) }}" class="aws-input" placeholder="Toyota, Renault...">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Modèle</label>
                    <input type="text" name="vehicle_model" value="{{ old('vehicle_model', $driver->vehicle_model) }}" class="aws-input" placeholder="Corolla, Clio...">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Plaque d'immatriculation</label>
                    <input type="text" name="vehicle_plate" value="{{ old('vehicle_plate', $driver->vehicle_plate) }}" class="aws-input" style="font-family:monospace" placeholder="AB-1234-CD">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Couleur</label>
                    <input type="text" name="vehicle_color" value="{{ old('vehicle_color', $driver->vehicle_color) }}" class="aws-input" placeholder="Blanc, Noir...">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Type de véhicule</label>
                    <select name="vehicle_type" class="aws-input">
                        <option value="">— Sélectionner —</option>
                        @foreach(['Berline','SUV','Monospace','Pickup','Minibus','Moto'] as $type)
                            <option value="{{ $type }}" {{ old('vehicle_type', $driver->vehicle_type) === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="aws-field">
                    <label class="aws-label">Ville d'opération</label>
                    <input type="text" name="vehicle_city" value="{{ old('vehicle_city', $driver->vehicle_city) }}" class="aws-input" placeholder="Yaoundé, Douala...">
                </div>
            </div>

        </div>
    </div>

    <div style="display:flex;align-items:center;gap:14px;padding:4px 0 20px">
        <button type="submit" class="aws-btn aws-btn-primary">Enregistrer les modifications</button>
        <a href="{{ route('company.vehicles.index') }}" style="font-size:13px;color:var(--aws-blue);text-decoration:none">Annuler</a>
    </div>

    </form>
</div>
@endsection
