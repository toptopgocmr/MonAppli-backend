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

    <form method="POST" action="{{ route('company.vehicles.update', $vehicle->id) }}">
    @csrf @method('PUT')

    <div class="aws-panel">
        <div class="aws-panel-header"><span class="aws-panel-title">Informations du véhicule</span></div>
        <div class="aws-panel-body">

            @if($errors->any())
            <div style="background:#fdf3f1;border:1px solid #f5b6a7;color:#d13212;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:16px">
                @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
            </div>
            @endif

            <div class="aws-grid-3">
                <div class="aws-field">
                    <label class="aws-label">Plaque *</label>
                    <input type="text" name="plate" value="{{ old('plate', $vehicle->plate) }}" required class="aws-input" style="font-family:monospace">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Marque</label>
                    <input type="text" name="brand" value="{{ old('brand', $vehicle->brand) }}" class="aws-input" placeholder="Toyota, Renault...">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Modèle</label>
                    <input type="text" name="model" value="{{ old('model', $vehicle->model) }}" class="aws-input" placeholder="Corolla, Clio...">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Couleur</label>
                    <input type="text" name="color" value="{{ old('color', $vehicle->color) }}" class="aws-input" placeholder="Blanc, Noir...">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Type de véhicule</label>
                    <select name="type" class="aws-input">
                        <option value="">— Sélectionner —</option>
                        @foreach(['Standard','Confort','Van','PMR'] as $type)
                            <option value="{{ $type }}" {{ old('type', $vehicle->type) === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="aws-field">
                    <label class="aws-label">Ville d'opération</label>
                    <input type="text" name="city" value="{{ old('city', $vehicle->city) }}" class="aws-input" placeholder="Yaoundé, Douala...">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Pays</label>
                    <input type="text" name="country" value="{{ old('country', $vehicle->country) }}" class="aws-input">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Statut</label>
                    <select name="status" class="aws-input">
                        @foreach(['active'=>'Actif','maintenance'=>'Maintenance','inactive'=>'Inactif'] as $val => $label)
                            <option value="{{ $val }}" {{ old('status', $vehicle->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="aws-field">
                <label class="aws-label">Notes</label>
                <textarea name="notes" class="aws-input" rows="2">{{ old('notes', $vehicle->notes) }}</textarea>
            </div>

        </div>
    </div>

    <div style="display:flex;align-items:center;gap:14px;padding:4px 0 20px">
        <button type="submit" class="aws-btn aws-btn-primary">Enregistrer les modifications</button>
        <a href="{{ route('company.vehicles.show', $vehicle->id) }}" style="font-size:13px;color:var(--aws-blue);text-decoration:none">Annuler</a>
    </div>

    </form>
</div>
@endsection
