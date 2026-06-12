@extends('company.layouts.app')
@section('title', 'Modifier Chauffeur')

@section('content')

<div class="aws-crumb">
    <a href="{{ route('company.dashboard') }}">Dashboard</a> ›
    <a href="{{ route('company.drivers.index') }}">Chauffeurs</a> ›
    <a href="{{ route('company.drivers.show', $driver->id) }}">{{ $driver->first_name }} {{ $driver->last_name }}</a> ›
    Modifier
</div>
<div class="aws-page-title" style="margin-bottom:16px">Modifier le profil</div>

<div style="max-width:860px">

    @if($errors->any())
    <div class="aws-alert aws-alert-error">
        <div>@foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach</div>
    </div>
    @endif

    <form method="POST" action="{{ route('company.drivers.update', $driver->id) }}">
    @csrf @method('PUT')

    <!-- Identité -->
    <div class="aws-panel">
        <div class="aws-panel-header">
            <span class="aws-panel-title">Identité</span>
        </div>
        <div class="aws-panel-body">
            <div class="aws-grid-2">
                <div class="aws-field">
                    <label class="aws-label">Prénom</label>
                    <input type="text" name="first_name" value="{{ old('first_name', $driver->first_name) }}" required class="aws-input">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Nom</label>
                    <input type="text" name="last_name" value="{{ old('last_name', $driver->last_name) }}" required class="aws-input">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Téléphone</label>
                    <input type="text" name="phone" value="{{ old('phone', $driver->phone) }}" required class="aws-input">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Date de naissance <span class="aws-label-opt">— facultatif</span></label>
                    <input type="date" name="birth_date" value="{{ old('birth_date', $driver->birth_date) }}" class="aws-input">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Nouveau mot de passe <span class="aws-label-opt">— vide = inchangé</span></label>
                    <input type="password" name="password" class="aws-input">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Confirmer le mot de passe</label>
                    <input type="password" name="password_confirmation" class="aws-input">
                </div>
                <div class="aws-field" style="grid-column:span 2">
                    <label class="aws-label">Lieu de naissance <span class="aws-label-opt">— facultatif</span></label>
                    <input type="text" name="birth_place" value="{{ old('birth_place', $driver->birth_place) }}" class="aws-input">
                </div>
            </div>
        </div>
    </div>

    <!-- Véhicule -->
    <div class="aws-panel">
        <div class="aws-panel-header"><span class="aws-panel-title">Véhicule</span></div>
        <div class="aws-panel-body">
            <div class="aws-grid-3">
                <div class="aws-field">
                    <label class="aws-label">Marque</label>
                    <input type="text" name="vehicle_brand" value="{{ old('vehicle_brand', $driver->getRawOriginal('vehicle_brand')) }}" class="aws-input" placeholder="Toyota">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Modèle</label>
                    <input type="text" name="vehicle_model" value="{{ old('vehicle_model', $driver->getRawOriginal('vehicle_model')) }}" class="aws-input" placeholder="Corolla">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Plaque</label>
                    <input type="text" name="vehicle_plate" value="{{ old('vehicle_plate', $driver->vehicle_plate) }}" class="aws-input" style="font-family:monospace">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Couleur</label>
                    <input type="text" name="vehicle_color" value="{{ old('vehicle_color', $driver->getRawOriginal('vehicle_color')) }}" class="aws-input">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Type</label>
                    <select name="vehicle_type" class="aws-input">
                        <option value="">— Choisir —</option>
                        @foreach(['Berline','SUV','Monospace','Pickup','Minibus','Moto'] as $type)
                            <option value="{{ $type }}" {{ old('vehicle_type', $driver->getRawOriginal('vehicle_type')) === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="aws-field">
                    <label class="aws-label">Ville d'opération</label>
                    <input type="text" name="vehicle_city" value="{{ old('vehicle_city', $driver->getRawOriginal('vehicle_city')) }}" class="aws-input">
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;align-items:center;gap:14px;padding:4px 0 20px">
        <button type="submit" class="aws-btn aws-btn-primary">Enregistrer les modifications</button>
        <a href="{{ route('company.drivers.show', $driver->id) }}" style="font-size:13px;color:var(--aws-blue);text-decoration:none">Annuler</a>
    </div>

    </form>
</div>
@endsection
