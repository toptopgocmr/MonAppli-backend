@extends('company.layouts.app')
@section('title', 'Modifier Itinéraire')

@section('content')

<div class="aws-crumb">
    <a href="{{ route('company.dashboard') }}">Dashboard</a> ›
    <a href="{{ route('company.itineraries.index') }}">Itinéraires</a> ›
    Modifier
</div>
<div class="aws-page-title" style="margin-bottom:16px">Modifier l'itinéraire</div>

<div style="max-width:780px">

    @if($errors->any())
    <div class="aws-alert aws-alert-error">
        <div>@foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach</div>
    </div>
    @endif

    <form method="POST" action="{{ route('company.itineraries.update', $itinerary->id) }}">
    @csrf @method('PUT')

    <div class="aws-panel">
        <div class="aws-panel-header"><span class="aws-panel-title">Trajet</span></div>
        <div class="aws-panel-body">
            <div class="aws-grid-2">
                <div class="aws-field">
                    <label class="aws-label">Lieu de départ</label>
                    <input type="text" name="departure" value="{{ old('departure', $itinerary->departure) }}" required class="aws-input">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Destination</label>
                    <input type="text" name="destination" value="{{ old('destination', $itinerary->destination) }}" required class="aws-input">
                </div>
            </div>
        </div>
    </div>

    <div class="aws-panel">
        <div class="aws-panel-header"><span class="aws-panel-title">Détails</span></div>
        <div class="aws-panel-body">
            <div class="aws-grid-3">
                <div class="aws-field">
                    <label class="aws-label">Tarif (FCFA)</label>
                    <input type="number" name="price" value="{{ old('price', $itinerary->price) }}" min="0" step="100" class="aws-input">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Distance (km)</label>
                    <input type="number" name="distance_km" value="{{ old('distance_km', $itinerary->distance_km) }}" min="0" step="0.1" class="aws-input">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Durée (min)</label>
                    <input type="number" name="duration_min" value="{{ old('duration_min', $itinerary->duration_min) }}" min="0" class="aws-input">
                </div>
            </div>
            <div class="aws-field">
                <label class="aws-label">Type de véhicule</label>
                <select name="vehicle_type" class="aws-input" style="max-width:260px">
                    <option value="">— Tous types —</option>
                    @foreach(['Berline','SUV','Monospace','Pickup','Minibus','Moto'] as $type)
                        <option value="{{ $type }}" {{ old('vehicle_type', $itinerary->vehicle_type) === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aws-field">
                <label class="aws-label">Notes</label>
                <textarea name="notes" rows="3" class="aws-input" style="resize:vertical">{{ old('notes', $itinerary->notes) }}</textarea>
            </div>
        </div>
    </div>

    <div style="display:flex;align-items:center;gap:14px;padding:4px 0 20px">
        <button type="submit" class="aws-btn aws-btn-primary">Enregistrer</button>
        <a href="{{ route('company.itineraries.index') }}" style="font-size:13px;color:var(--aws-blue);text-decoration:none">Annuler</a>
    </div>

    </form>
</div>
@endsection
