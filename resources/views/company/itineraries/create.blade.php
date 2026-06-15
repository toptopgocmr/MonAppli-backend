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

    <!-- Calcul automatique -->
    <div id="route-status" style="display:none;margin:-8px 0 16px;padding:10px 14px;background:#e8f4fd;border:1px solid #b8d8f0;border-radius:6px;font-size:13px;color:#0073bb;display:flex;align-items:center;gap:8px">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span id="route-status-text">Calcul en cours...</span>
    </div>

    <div style="display:flex;align-items:center;gap:14px;padding:4px 0 20px">
        <button type="submit" class="aws-btn aws-btn-primary">Créer l'itinéraire</button>
        <a href="{{ route('company.itineraries.index') }}" style="font-size:13px;color:var(--aws-blue);text-decoration:none">Annuler</a>
    </div>

    </form>
</div>

@push('scripts')
<script>
(function () {
    const fDep    = document.querySelector('input[name="departure"]');
    const fDest   = document.querySelector('input[name="destination"]');
    const fDepT   = document.querySelector('input[name="departure_time"]');
    const fArrT   = document.querySelector('input[name="arrival_time"]');
    const fDist   = document.querySelector('input[name="distance_km"]');
    const fDur    = document.querySelector('input[name="duration_min"]');
    const status  = document.getElementById('route-status');
    const statusT = document.getElementById('route-status-text');

    let timer = null;

    function showStatus(msg, color) {
        status.style.display = 'flex';
        status.style.background = color === 'green' ? '#e6f4ea' : color === 'red' ? '#fdf0ed' : '#e8f4fd';
        status.style.borderColor = color === 'green' ? '#a8d5b0' : color === 'red' ? '#f5c6bc' : '#b8d8f0';
        status.style.color = color === 'green' ? '#1d8102' : color === 'red' ? '#d13212' : '#0073bb';
        statusT.textContent = msg;
    }

    function hideStatus() { status.style.display = 'none'; }

    async function geocode(city) {
        const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(city)}&format=json&limit=1&accept-language=fr`;
        const res = await fetch(url, { headers: { 'User-Agent': 'TopTopGo/1.0 toptopgoinfo@gmail.com' } });
        const data = await res.json();
        if (!data.length) return null;
        return { lat: parseFloat(data[0].lat), lon: parseFloat(data[0].lon), name: data[0].display_name };
    }

    async function calcRoute() {
        const dep  = fDep.value.trim();
        const dest = fDest.value.trim();
        if (dep.length < 2 || dest.length < 2) { hideStatus(); return; }

        showStatus('Recherche des coordonnées…', 'blue');

        const [from, to] = await Promise.all([geocode(dep), geocode(dest)]);
        if (!from) { showStatus(`Ville "${dep}" introuvable.`, 'red'); return; }
        if (!to)   { showStatus(`Ville "${dest}" introuvable.`, 'red'); return; }

        showStatus('Calcul de l\'itinéraire…', 'blue');

        const osrm = `https://router.project-osrm.org/route/v1/driving/${from.lon},${from.lat};${to.lon},${to.lat}?overview=false`;
        const res  = await fetch(osrm);
        const data = await res.json();

        if (data.code !== 'Ok' || !data.routes?.length) {
            showStatus('Impossible de calculer la route.', 'red');
            return;
        }

        const route   = data.routes[0];
        const distKm  = (route.distance / 1000).toFixed(1);
        const durMin  = Math.round(route.duration / 60);

        fDist.value = distKm;
        fDur.value  = durMin;

        // Calcul heure d'arrivée si heure de départ renseignée
        if (fDepT.value) {
            const [h, m] = fDepT.value.split(':').map(Number);
            const arrDate = new Date(2000, 0, 1, h, m + durMin);
            fArrT.value = String(arrDate.getHours()).padStart(2,'0') + ':' + String(arrDate.getMinutes()).padStart(2,'0');
        }

        showStatus(`✓ ${distKm} km — environ ${durMin} min de route.`, 'green');
    }

    function debounce() {
        clearTimeout(timer);
        timer = setTimeout(calcRoute, 600);
    }

    fDep.addEventListener('blur', calcRoute);
    fDest.addEventListener('blur', calcRoute);
    fDepT.addEventListener('change', () => {
        if (fDist.value && fDur.value) {
            const [h, m] = fDepT.value.split(':').map(Number);
            const dur    = parseInt(fDur.value);
            const arr    = new Date(2000, 0, 1, h, m + dur);
            fArrT.value  = String(arr.getHours()).padStart(2,'0') + ':' + String(arr.getMinutes()).padStart(2,'0');
        }
    });
})();
</script>
@endpush
@endsection
