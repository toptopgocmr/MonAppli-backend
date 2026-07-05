@extends('company.layouts.app')
@section('title', 'Ajouter un Véhicule')

@section('content')

<div class="aws-crumb">
    <a href="{{ route('company.dashboard') }}">Dashboard</a> ›
    <a href="{{ route('company.vehicles.index') }}">Flotte Véhicules</a> ›
    Nouveau
</div>
<div class="aws-page-title" style="margin-bottom:16px">Ajouter un véhicule à la flotte</div>

<div style="max-width:780px">

    @if($errors->any())
    <div class="aws-alert aws-alert-error">
        <div>@foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach</div>
    </div>
    @endif

    <div class="aws-alert" style="background:#eef7ff;border:1px solid #b8daf5;color:#0073bb;margin-bottom:16px">
        Vous pourrez assigner un ou plusieurs chauffeurs (avec leurs créneaux) après la création du véhicule.
    </div>

    <form method="POST" action="{{ route('company.vehicles.store') }}">
    @csrf

    <div class="aws-panel">
        <div class="aws-panel-header"><span class="aws-panel-title">Informations du véhicule</span></div>
        <div class="aws-panel-body">

            <div class="aws-grid-3">
                <div class="aws-field">
                    <label class="aws-label">Plaque *</label>
                    <input type="text" name="plate" value="{{ old('plate') }}" required class="aws-input" style="font-family:monospace" placeholder="LT-1234-A">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Marque</label>
                    <input type="text" name="brand" value="{{ old('brand') }}" class="aws-input" placeholder="Toyota">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Modèle</label>
                    <input type="text" name="model" value="{{ old('model') }}" class="aws-input" placeholder="Corolla">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Couleur</label>
                    <input type="text" name="color" value="{{ old('color') }}" class="aws-input" placeholder="Blanc">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Type</label>
                    <select name="type" class="aws-input">
                        <option value="">— Choisir —</option>
                        @foreach(['Standard','Confort','Van','PMR'] as $type)
                            <option value="{{ $type }}" {{ old('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="aws-field">
                    <label class="aws-label">Ville d'opération</label>
                    <input type="text" name="city" value="{{ old('city') }}" class="aws-input" placeholder="Yaoundé">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Pays</label>
                    <input type="text" name="country" value="{{ old('country') }}" class="aws-input" placeholder="Cameroun">
                </div>
            </div>

            <div class="aws-field">
                <label class="aws-label">Notes</label>
                <textarea name="notes" class="aws-input" rows="2">{{ old('notes') }}</textarea>
            </div>

        </div>
    </div>

    <div style="display:flex;align-items:center;gap:14px;padding:4px 0 20px">
        <button type="submit" class="aws-btn aws-btn-primary">Ajouter le véhicule</button>
        <a href="{{ route('company.vehicles.index') }}" style="font-size:13px;color:var(--aws-blue);text-decoration:none">Annuler</a>
    </div>

    </form>
</div>
@endsection
