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
                <div class="aws-field" style="position:relative">
                    <label class="aws-label">Ville de départ</label>
                    <input type="text" id="departure" name="departure" value="{{ old('departure') }}" required
                        class="aws-input city-autocomplete" placeholder="Yaoundé" autocomplete="off">
                    <div class="city-dropdown" id="departure-dropdown"></div>
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
                <div class="aws-field" style="position:relative">
                    <label class="aws-label">Ville de destination</label>
                    <input type="text" id="destination" name="destination" value="{{ old('destination') }}" required
                        class="aws-input city-autocomplete" placeholder="Douala" autocomplete="off">
                    <div class="city-dropdown" id="destination-dropdown"></div>
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
            <div class="aws-field">
                <label class="aws-label">Grille tarifaire <span class="aws-label-opt">— facultatif</span></label>
                <select name="pricing_grid_id" id="pricing_grid_id" class="aws-input" style="max-width:320px">
                    <option value="">— Aucune / tarif libre —</option>
                    @foreach($pricingGrids as $g)
                        <option value="{{ $g->id }}" {{ old('pricing_grid_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                    @endforeach
                </select>
                <p class="aws-hint">
                    Sélectionnez une grille pour afficher ses tarifs de référence ci-dessous — le champ "Tarif" reste à remplir manuellement.
                    <a href="{{ route('company.pricing-grids.index') }}" style="color:var(--aws-blue)">Gérer les grilles →</a>
                </p>
                <div id="grid-rates-ref" style="display:none;margin-top:8px;padding:10px 14px;background:#fafafa;border:1px solid var(--aws-border);border-radius:6px"></div>
            </div>
            <div class="aws-grid-3">
                <div class="aws-field">
                    <label class="aws-label">Tarif (FCFA)</label>
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
                <label class="aws-label">Places disponibles</label>
                <input type="number" name="seats" value="{{ old('seats', 4) }}" min="1" max="60" class="aws-input" style="max-width:160px">
                <p class="aws-hint">Nombre de places qu'un client peut réserver et payer directement dans l'app sur cet itinéraire.</p>
            </div>
            <div class="aws-field">
                <label class="aws-label">Type de véhicule recommandé</label>
                <select name="vehicle_type" id="vehicle_type_select" class="aws-input" style="max-width:260px">
                    <option value="">— Tous types —</option>
                    @foreach($vehicleTypes ?? [] as $type)
                        <option value="{{ $type }}" {{ old('vehicle_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                    <option value="__other__" {{ old('vehicle_type') === '__other__' ? 'selected' : '' }}>+ Autre (nouveau type)…</option>
                </select>
                <input type="text" name="new_vehicle_type" id="new_vehicle_type_input" value="{{ old('new_vehicle_type') }}"
                    class="aws-input" style="max-width:260px;margin-top:6px;{{ old('vehicle_type') === '__other__' ? '' : 'display:none' }}"
                    placeholder="Nom du nouveau type de véhicule">
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
// ── Référence tarifs de la grille sélectionnée (remplissage manuel du prix) ──
(function () {
    const grids = @json($pricingGridsForJs);

    const select = document.getElementById('pricing_grid_id');
    const box    = document.getElementById('grid-rates-ref');

    function render() {
        const grid = grids.find(g => String(g.id) === select.value);
        if (!grid || !grid.rates.length) { box.style.display = 'none'; box.innerHTML = ''; return; }

        box.innerHTML = '<div style="font-size:12px;font-weight:700;color:var(--aws-header);margin-bottom:6px">Tarifs de référence de cette grille :</div>' +
            grid.rates.map(r => `
                <div style="display:flex;justify-content:space-between;font-size:12px;padding:3px 0;border-bottom:1px solid #eee">
                    <span style="color:var(--aws-sub)">${r.label}${r.vehicle_type ? ' — ' + r.vehicle_type : ''}</span>
                    <span style="font-weight:700;color:var(--aws-header)">${new Intl.NumberFormat('fr-FR').format(r.price)} FCFA</span>
                </div>`).join('');
        box.style.display = 'block';
    }

    select.addEventListener('change', render);
    render();
})();

(function () {
    const sel = document.getElementById('vehicle_type_select');
    const inp = document.getElementById('new_vehicle_type_input');
    if (!sel || !inp) return;
    sel.addEventListener('change', () => {
        inp.style.display = sel.value === '__other__' ? '' : 'none';
    });
})();
</script>
<style>
.city-dropdown {
    display: none;
    position: absolute;
    top: 100%;
    left: 0; right: 0;
    background: #fff;
    border: 1px solid var(--aws-border);
    border-top: none;
    border-radius: 0 0 6px 6px;
    box-shadow: 0 4px 12px rgba(0,0,0,.10);
    z-index: 999;
    max-height: 240px;
    overflow-y: auto;
}
.city-dropdown.open { display: block; }
.city-item {
    padding: 9px 14px;
    cursor: pointer;
    border-bottom: 1px solid #f5f5f5;
    display: flex;
    align-items: center;
    gap: 10px;
}
.city-item:last-child { border-bottom: none; }
.city-item:hover { background: #f0f6ff; }
.city-item .city-name { font-size: 13px; font-weight: 600; color: var(--aws-header); }
.city-item .city-country { font-size: 11px; color: var(--aws-sub); }
.city-loading { padding: 10px 14px; font-size: 12px; color: var(--aws-sub); }
</style>
<script>
// ── Autocomplétion villes ──────────────────────────────────────────
(function () {
    function initCityAutocomplete(inputId, dropdownId) {
        const input    = document.getElementById(inputId);
        const dropdown = document.getElementById(dropdownId);
        let timer = null;

        input.addEventListener('input', function () {
            const q = this.value.trim();
            clearTimeout(timer);
            if (q.length < 2) { dropdown.className = 'city-dropdown'; return; }
            dropdown.className = 'city-dropdown open';
            dropdown.innerHTML = '<div class="city-loading">Recherche…</div>';
            timer = setTimeout(() => search(q), 350);
        });

        document.addEventListener('click', function (e) {
            if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.className = 'city-dropdown';
            }
        });

        async function search(q) {
            try {
                const url  = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(q)}&format=json&limit=8&addressdetails=1&accept-language=fr`;
                const res  = await fetch(url, { headers: { 'User-Agent': 'TopTopGo/1.0 toptopgoinfo@gmail.com' } });
                const data = await res.json();

                const seen = new Set();
                const items = [];
                for (const item of data) {
                    const addr    = item.address || {};
                    const city    = addr.city || addr.town || addr.village || addr.county || addr.municipality || item.name || '';
                    const country = addr.country || '';
                    const key     = city + '|' + country;
                    if (city && !seen.has(key)) { seen.add(key); items.push({ city, country }); }
                }

                if (!items.length) {
                    dropdown.innerHTML = '<div class="city-loading">Aucun résultat</div>';
                    return;
                }

                dropdown.innerHTML = items.map(m => `
                    <div class="city-item" data-city="${m.city}" data-country="${m.country}">
                        <svg width="12" height="14" viewBox="0 0 12 16" fill="none" style="flex-shrink:0">
                            <path d="M6 0C3.24 0 1 2.24 1 5c0 3.75 5 11 5 11s5-7.25 5-11c0-2.76-2.24-5-5-5zm0 6.5C5.17 6.5 4.5 5.83 4.5 5S5.17 3.5 6 3.5 7.5 4.17 7.5 5 6.83 6.5 6 6.5z" fill="#ec7211"/>
                        </svg>
                        <div>
                            <div class="city-name">${m.city}</div>
                            <div class="city-country">${m.country}</div>
                        </div>
                    </div>`).join('');

                dropdown.querySelectorAll('.city-item').forEach(el => {
                    el.addEventListener('click', function () {
                        input.value = this.dataset.city;
                        dropdown.className = 'city-dropdown';
                        // Déclencher le calcul de route
                        input.dispatchEvent(new Event('blur'));
                    });
                });

            } catch (e) {
                dropdown.innerHTML = '<div class="city-loading">Erreur de connexion</div>';
            }
        }
    }

    initCityAutocomplete('departure',   'departure-dropdown');
    initCityAutocomplete('destination', 'destination-dropdown');
})();

// ── Calcul de route ──────────────────────────────────────────────
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
