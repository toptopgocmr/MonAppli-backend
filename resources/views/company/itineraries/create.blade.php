@extends('company.layouts.app')
@section('title', 'Nouvel Itinéraire')

@section('content')

<div class="aws-crumb">
    <a href="{{ route('company.dashboard') }}">Dashboard</a> ›
    <a href="{{ route('company.itineraries.index') }}">Itinéraires</a> ›
    Nouveau
</div>
<div class="aws-page-title" style="margin-bottom:16px">Créer un itinéraire</div>

<div style="max-width:860px">

    @if($errors->any())
    <div class="aws-alert aws-alert-error">
        <div>@foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach</div>
    </div>
    @endif

    <form method="POST" action="{{ route('company.itineraries.store') }}">
    @csrf

    <!-- Départ -->
    <div class="aws-panel">
        <div class="aws-panel-header">
            <span class="aws-panel-title">Point de départ</span>
            <span style="font-size:12px;color:var(--aws-sub)">Ville et lieu précis d'embarquement</span>
        </div>
        <div class="aws-panel-body">
            <div class="aws-grid-2">
                <div class="aws-field">
                    <label class="aws-label">Ville de départ</label>
                    <input type="text" name="departure" value="{{ old('departure') }}" required class="aws-input" placeholder="Yaoundé">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Heure de départ</label>
                    <input type="time" name="departure_time" value="{{ old('departure_time') }}" class="aws-input">
                    <p class="aws-hint">Heure de prise en charge des passagers.</p>
                </div>
                <div class="aws-field" style="grid-column:span 2">
                    <label class="aws-label">Point précis d'embarquement <span class="aws-label-opt">— facultatif</span></label>
                    <input type="text" name="departure_point" value="{{ old('departure_point') }}" class="aws-input" placeholder="Ex: Gare Routière de Mvan, Carrefour Total Nlongkak...">
                    <p class="aws-hint">Adresse ou lieu-dit exact où les passagers sont pris en charge.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Arrivée -->
    <div class="aws-panel">
        <div class="aws-panel-header">
            <span class="aws-panel-title">Point d'arrivée</span>
            <span style="font-size:12px;color:var(--aws-sub)">Ville et lieu précis de débarquement</span>
        </div>
        <div class="aws-panel-body">
            <div class="aws-grid-2">
                <div class="aws-field">
                    <label class="aws-label">Ville de destination</label>
                    <input type="text" name="destination" value="{{ old('destination') }}" required class="aws-input" placeholder="Douala">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Heure d'arrivée estimée</label>
                    <input type="time" name="arrival_time" value="{{ old('arrival_time') }}" class="aws-input">
                    <p class="aws-hint">Heure d'arrivée prévue à destination.</p>
                </div>
                <div class="aws-field" style="grid-column:span 2">
                    <label class="aws-label">Point précis de débarquement <span class="aws-label-opt">— facultatif</span></label>
                    <input type="text" name="arrival_point" value="{{ old('arrival_point') }}" class="aws-input" placeholder="Ex: Gare de Bessengue, Carrefour Akwa-Nord...">
                    <p class="aws-hint">Adresse ou lieu-dit exact où les passagers sont déposés.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Détails -->
    <div class="aws-panel">
        <div class="aws-panel-header">
            <span class="aws-panel-title">Détails <span style="font-size:12px;font-weight:400;color:var(--aws-sub)">— facultatif</span></span>
        </div>
        <div class="aws-panel-body">
            <div class="aws-grid-3">
                <div class="aws-field">
                    <label class="aws-label">Tarif indicatif (FCFA)</label>
                    <input type="number" name="price" value="{{ old('price') }}" min="0" step="100" class="aws-input" placeholder="5000">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Distance (km)</label>
                    <input type="number" name="distance_km" value="{{ old('distance_km') }}" min="0" step="0.1" class="aws-input" placeholder="250">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Durée estimée (min)</label>
                    <input type="number" name="duration_min" value="{{ old('duration_min') }}" min="0" class="aws-input" placeholder="180">
                </div>
            </div>
            <div class="aws-field">
                <label class="aws-label">Type de véhicule recommandé</label>
                <select name="vehicle_type" class="aws-input" style="max-width:260px">
                    <option value="">— Tous types —</option>
                    @foreach(['Berline','SUV','Monospace','Pickup','Minibus','Moto'] as $type)
                        <option value="{{ $type }}" {{ old('vehicle_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aws-field">
                <label class="aws-label">Notes <span class="aws-label-opt">— arrêts, conditions, tarifs spéciaux...</span></label>
                <textarea name="notes" rows="3" class="aws-input" style="resize:vertical" placeholder="Ex: Arrêt possible à Bafoussam. Bagages supplémentaires : 500 FCFA/kg.">{{ old('notes') }}</textarea>
            </div>
        </div>
    </div>

    <div style="display:flex;align-items:center;gap:14px;padding:4px 0 20px">
        <button type="submit" class="aws-btn aws-btn-primary">Créer l'itinéraire</button>
        <a href="{{ route('company.itineraries.index') }}" style="font-size:13px;color:var(--aws-blue);text-decoration:none">Annuler</a>
    </div>

    </form>
</div>
@endsection
