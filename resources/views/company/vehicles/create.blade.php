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
                    <select name="type" id="type_select" class="aws-input">
                        <option value="">— Choisir —</option>
                        @foreach($vehicleTypes ?? [] as $type)
                            <option value="{{ $type }}" {{ old('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                        <option value="__other__" {{ old('type') === '__other__' ? 'selected' : '' }}>+ Autre (nouveau type)…</option>
                    </select>
                    <input type="text" name="new_type" id="new_type_input" value="{{ old('new_type') }}"
                        class="aws-input" style="margin-top:6px;{{ old('type') === '__other__' ? '' : 'display:none' }}"
                        placeholder="Nom du nouveau type de véhicule">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Pays</label>
                    <select name="country" id="country" class="aws-input">
                        <option value="">— Choisir —</option>
                        @foreach($countries as $c)
                            <option value="{{ $c['name'] }}" {{ old('country') === $c['name'] ? 'selected' : '' }}>{{ $c['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="aws-field">
                    <label class="aws-label">Ville d'opération</label>
                    <select name="city" id="city" class="aws-input">
                        <option value="">— Choisir un pays d'abord —</option>
                    </select>
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

@push('scripts')
<script>
(function () {
    const citiesByCountry = @json(collect($countries)->mapWithKeys(fn($c) => [$c['name'] => $c['cities']]));
    const oldCity = @json(old('city'));

    const countrySelect = document.getElementById('country');
    const citySelect     = document.getElementById('city');

    function populateCities(selectedCity) {
        const cities = citiesByCountry[countrySelect.value] || [];
        citySelect.innerHTML = cities.length
            ? cities.map(c => `<option value="${c}" ${c === selectedCity ? 'selected' : ''}>${c}</option>`).join('')
            : '<option value="">— Aucune ville pour ce pays —</option>';
    }

    countrySelect.addEventListener('change', () => populateCities(null));
    if (countrySelect.value) populateCities(oldCity);
})();

(function () {
    const sel = document.getElementById('type_select');
    const inp = document.getElementById('new_type_input');
    if (!sel || !inp) return;
    sel.addEventListener('change', () => {
        inp.style.display = sel.value === '__other__' ? '' : 'none';
    });
})();
</script>
@endpush
@endsection
