@extends('company.layouts.app')
@section('title', 'Modifier Itinéraire')

@section('content')

<div class="aws-crumb">
    <a href="{{ route('company.dashboard') }}">Dashboard</a> ›
    <a href="{{ route('company.itineraries.index') }}">Itinéraires</a> ›
    Modifier
</div>
<div class="aws-page-title" style="margin-bottom:16px">
    {{ $itinerary->departure }} → {{ $itinerary->destination }}
</div>

<div style="max-width:860px">

    @if($errors->any())
    <div class="aws-alert aws-alert-error">
        <div>@foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach</div>
    </div>
    @endif

    <form method="POST" action="{{ route('company.itineraries.update', $itinerary->id) }}">
    @csrf @method('PUT')

    <!-- Départ -->
    <div class="aws-panel">
        <div class="aws-panel-header"><span class="aws-panel-title">Point de départ</span></div>
        <div class="aws-panel-body">
            <div class="aws-grid-2">
                <div class="aws-field" style="position:relative">
                    <label class="aws-label">Ville de départ</label>
                    <input type="text" id="departure" name="departure" value="{{ old('departure', $itinerary->departure) }}" required class="aws-input" autocomplete="off">
                    <div class="city-dropdown" id="departure-dropdown"></div>
                </div>
                <div class="aws-field">
                    <label class="aws-label">Heure de départ</label>
                    <input type="time" name="departure_time" value="{{ old('departure_time', $itinerary->departure_time ? \Carbon\Carbon::parse($itinerary->departure_time)->format('H:i') : '') }}" class="aws-input">
                </div>
                <div class="aws-field" style="grid-column:span 2">
                    <label class="aws-label">Point précis d'embarquement <span class="aws-label-opt">— facultatif</span></label>
                    <input type="text" name="departure_point" value="{{ old('departure_point', $itinerary->departure_point) }}" class="aws-input" placeholder="Ex: Gare Routière de Mvan...">
                </div>
            </div>
        </div>
    </div>

    <!-- Arrivée -->
    <div class="aws-panel">
        <div class="aws-panel-header"><span class="aws-panel-title">Point d'arrivée</span></div>
        <div class="aws-panel-body">
            <div class="aws-grid-2">
                <div class="aws-field" style="position:relative">
                    <label class="aws-label">Ville de destination</label>
                    <input type="text" id="destination" name="destination" value="{{ old('destination', $itinerary->destination) }}" required class="aws-input" autocomplete="off">
                    <div class="city-dropdown" id="destination-dropdown"></div>
                </div>
                <div class="aws-field">
                    <label class="aws-label">Heure d'arrivée estimée</label>
                    <input type="time" name="arrival_time" value="{{ old('arrival_time', $itinerary->arrival_time ? \Carbon\Carbon::parse($itinerary->arrival_time)->format('H:i') : '') }}" class="aws-input">
                </div>
                <div class="aws-field" style="grid-column:span 2">
                    <label class="aws-label">Point précis de débarquement <span class="aws-label-opt">— facultatif</span></label>
                    <input type="text" name="arrival_point" value="{{ old('arrival_point', $itinerary->arrival_point) }}" class="aws-input" placeholder="Ex: Gare de Bessengue...">
                </div>
            </div>
        </div>
    </div>

    <!-- Détails -->
    <div class="aws-panel">
        <div class="aws-panel-header"><span class="aws-panel-title">Détails</span></div>
        <div class="aws-panel-body">
            <div class="aws-field">
                <label class="aws-label">Grille tarifaire <span class="aws-label-opt">— facultatif</span></label>
                <select name="pricing_grid_id" id="pricing_grid_id" class="aws-input" style="max-width:320px">
                    <option value="">— Aucune / tarif libre —</option>
                    @foreach($pricingGrids as $g)
                        <option value="{{ $g->id }}" {{ old('pricing_grid_id', $itinerary->pricing_grid_id) == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
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

    <!-- Calcul automatique -->
    <div id="route-status" style="display:none;margin:-8px 0 16px;padding:10px 14px;background:#e8f4fd;border:1px solid #b8d8f0;border-radius:6px;font-size:13px;color:#0073bb;display:flex;align-items:center;gap:8px">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span id="route-status-text"></span>
    </div>

    <div style="display:flex;align-items:center;gap:14px;padding:4px 0 20px">
        <button type="submit" class="aws-btn aws-btn-primary">Enregistrer</button>
        <a href="{{ route('company.itineraries.index') }}" style="font-size:13px;color:var(--aws-blue);text-decoration:none">Annuler</a>
    </div>

    </form>
</div>

@push('scripts')
<script>
// ── Référence tarifs de la grille sélectionnée (remplissage manuel du prix) ──
(function () {
    const grids = @json($pricingGrids->map(fn($g) => [
        'id' => $g->id,
        'rates' => $g->rates->map(fn($r) => [
            'label' => $r->label,
            'vehicle_type' => $r->vehicle_type,
            'price' => (float) $r->price,
        ]),
    ]));

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
</script>
<style>
.city-dropdown {
    display: none;
    position: absolute;
    top: 100%; left: 0; right: 0;
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
    padding: 9px 14px; cursor: pointer;
    border-bottom: 1px solid #f5f5f5;
    display: flex; align-items: center; gap: 10px;
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
                const seen = new Set(); const items = [];
                for (const item of data) {
                    const addr = item.address || {};
                    const city = addr.city || addr.town || addr.village || addr.county || addr.municipality || item.name || '';
                    const country = addr.country || '';
                    const key = city + '|' + country;
                    if (city && !seen.has(key)) { seen.add(key); items.push({ city, country }); }
                }
                if (!items.length) { dropdown.innerHTML = '<div class="city-loading">Aucun résultat</div>'; return; }
                dropdown.innerHTML = items.map(m => `
                    <div class="city-item" data-city="${m.city}">
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

    function showStatus(msg, color) {
        status.style.display = 'flex';
        status.style.background = color === 'green' ? '#e6f4ea' : color === 'red' ? '#fdf0ed' : '#e8f4fd';
        status.style.borderColor = color === 'green' ? '#a8d5b0' : color === 'red' ? '#f5c6bc' : '#b8d8f0';
        status.style.color = color === 'green' ? '#1d8102' : color === 'red' ? '#d13212' : '#0073bb';
        statusT.textContent = msg;
    }

    async function geocode(city) {
        const res  = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(city)}&format=json&limit=1&accept-language=fr`, { headers: { 'User-Agent': 'TopTopGo/1.0 toptopgoinfo@gmail.com' } });
        const data = await res.json();
        return data.length ? { lat: parseFloat(data[0].lat), lon: parseFloat(data[0].lon) } : null;
    }

    async function calcRoute() {
        const dep  = fDep.value.trim();
        const dest = fDest.value.trim();
        if (dep.length < 2 || dest.length < 2) return;

        showStatus('Calcul en cours…', 'blue');
        const [from, to] = await Promise.all([geocode(dep), geocode(dest)]);
        if (!from || !to) { showStatus('Ville introuvable.', 'red'); return; }

        const res  = await fetch(`https://router.project-osrm.org/route/v1/driving/${from.lon},${from.lat};${to.lon},${to.lat}?overview=false`);
        const data = await res.json();
        if (data.code !== 'Ok' || !data.routes?.length) { showStatus('Route introuvable.', 'red'); return; }

        const distKm = (data.routes[0].distance / 1000).toFixed(1);
        const durMin = Math.round(data.routes[0].duration / 60);
        fDist.value  = distKm;
        fDur.value   = durMin;

        if (fDepT.value) {
            const [h, m] = fDepT.value.split(':').map(Number);
            const arr    = new Date(2000, 0, 1, h, m + durMin);
            fArrT.value  = String(arr.getHours()).padStart(2,'0') + ':' + String(arr.getMinutes()).padStart(2,'0');
        }

        showStatus(`✓ ${distKm} km — environ ${durMin} min de route.`, 'green');
    }

    fDep.addEventListener('blur', calcRoute);
    fDest.addEventListener('blur', calcRoute);
    fDepT.addEventListener('change', () => {
        if (fDur.value) {
            const [h, m] = fDepT.value.split(':').map(Number);
            const arr    = new Date(2000, 0, 1, h, m + parseInt(fDur.value));
            fArrT.value  = String(arr.getHours()).padStart(2,'0') + ':' + String(arr.getMinutes()).padStart(2,'0');
        }
    });
})();
</script>
@endpush
@endsection
