@extends('company.layouts.app')
@section('title', 'Nouveau Chauffeur')

@section('content')

<div class="aws-crumb">
    <a href="{{ route('company.dashboard') }}">Dashboard</a> ›
    <a href="{{ route('company.drivers.index') }}">Chauffeurs</a> ›
    Nouveau
</div>
<div class="aws-page-title" style="margin-bottom:16px">Créer un profil chauffeur</div>

<div style="max-width:860px">

    @if($errors->any())
    <div class="aws-alert aws-alert-error">
        <div>@foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach</div>
    </div>
    @endif

    <form method="POST" action="{{ route('company.drivers.store') }}">
    @csrf

    <!-- Identité -->
    <div class="aws-panel">
        <div class="aws-panel-header">
            <span class="aws-panel-title">Identité du chauffeur</span>
            <span style="font-size:12px;color:var(--aws-sub)">Informations personnelles et accès</span>
        </div>
        <div class="aws-panel-body">
            <div class="aws-grid-2">
                <div class="aws-field">
                    <label class="aws-label">Prénom</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required class="aws-input" placeholder="Jean">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Nom</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required class="aws-input" placeholder="Dupont">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Téléphone <span class="aws-label-opt">— identifiant de connexion</span></label>
                    <input type="text" name="phone" value="{{ old('phone') }}" required class="aws-input" placeholder="+237 6XX XXX XXX">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Date de naissance <span class="aws-label-opt">— facultatif</span></label>
                    <input type="date" name="birth_date" value="{{ old('birth_date') }}" class="aws-input">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Mot de passe</label>
                    <input type="password" name="password" required class="aws-input">
                    <p class="aws-hint">Minimum 8 caractères. Le chauffeur peut le changer depuis l'app.</p>
                </div>
                <div class="aws-field">
                    <label class="aws-label">Confirmer le mot de passe</label>
                    <input type="password" name="password_confirmation" required class="aws-input">
                </div>
                <div class="aws-field" style="grid-column:span 2">
                    <label class="aws-label">Lieu de naissance <span class="aws-label-opt">— facultatif</span></label>
                    <input type="text" name="birth_place" value="{{ old('birth_place') }}" class="aws-input" placeholder="Yaoundé, Cameroun">
                </div>
            </div>
        </div>
    </div>

    <!-- Véhicule -->
    <div class="aws-panel">
        <div class="aws-panel-header">
            <span class="aws-panel-title">Véhicule <span style="font-size:12px;font-weight:400;color:var(--aws-sub)">— facultatif, modifiable plus tard</span></span>
        </div>
        <div class="aws-panel-body">
            <div class="aws-grid-3">
                <div class="aws-field">
                    <label class="aws-label">Marque</label>
                    <input type="text" name="vehicle_brand" value="{{ old('vehicle_brand') }}" class="aws-input" placeholder="Toyota">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Modèle</label>
                    <input type="text" name="vehicle_model" value="{{ old('vehicle_model') }}" class="aws-input" placeholder="Corolla">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Plaque</label>
                    <input type="text" name="vehicle_plate" value="{{ old('vehicle_plate') }}" class="aws-input" style="font-family:monospace" placeholder="LT-1234-A">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Couleur</label>
                    <input type="text" name="vehicle_color" value="{{ old('vehicle_color') }}" class="aws-input" placeholder="Blanc">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Type</label>
                    <select name="vehicle_type" class="aws-input">
                        <option value="">— Choisir —</option>
                        @foreach(['Berline','SUV','Monospace','Pickup','Minibus','Moto'] as $type)
                            <option value="{{ $type }}" {{ old('vehicle_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="aws-field">
                    <label class="aws-label">Ville d'opération</label>
                    <input type="text" name="vehicle_city" value="{{ old('vehicle_city') }}" class="aws-input" placeholder="Yaoundé">
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;align-items:center;gap:14px;padding:4px 0 20px">
        <button type="submit" class="aws-btn aws-btn-primary">Créer le chauffeur</button>
        <a href="{{ route('company.drivers.index') }}" style="font-size:13px;color:var(--aws-blue);text-decoration:none">Annuler</a>
    </div>

    </form>
</div>
@endsection
