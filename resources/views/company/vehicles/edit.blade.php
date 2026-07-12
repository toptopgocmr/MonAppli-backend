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
                    <select name="type" id="type_select" class="aws-input">
                        <option value="">— Sélectionner —</option>
                        @foreach($vehicleTypes ?? [] as $type)
                            <option value="{{ $type }}" {{ old('type', $vehicle->type) === $type ? 'selected' : '' }}>{{ $type }}</option>
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
                            <option value="{{ $c['name'] }}" data-code="{{ $c['code'] }}"
                                data-cities='@json($citiesByCountry[$c['code']] ?? [])'
                                {{ old('country', $vehicle->country) === $c['name'] ? 'selected' : '' }}>{{ $c['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="aws-field" style="position:relative">
                    <label class="aws-label">Ville d'opération</label>
                    <input type="text" name="city" id="city" value="{{ old('city', $vehicle->city) }}" class="aws-input" autocomplete="off"
                        placeholder="Choisir un pays, puis taper le nom de la ville">
                    <div class="city-dropdown" id="city-dropdown"></div>
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

@push('scripts')
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
.city-item { padding: 9px 14px; cursor: pointer; border-bottom: 1px solid #f5f5f5; display: flex; align-items: center; gap: 10px; }
.city-item:last-child { border-bottom: none; }
.city-item:hover { background: #f0f6ff; }
.city-item .city-name { font-size: 13px; font-weight: 600; color: var(--aws-header); }
.city-item .city-country { font-size: 11px; color: var(--aws-sub); }
.city-loading { padding: 10px 14px; font-size: 12px; color: var(--aws-sub); }
</style>
<script>
// ── Autocomplétion ville (OpenStreetMap/Nominatim), filtrée par pays choisi ──
(function () {
    const countrySelect = document.getElementById('country');
    const cityInput     = document.getElementById('city');
    const dropdown      = document.getElementById('city-dropdown');
    let timer = null;

    function selectedCountryCode() {
        const opt = countrySelect.options[countrySelect.selectedIndex];
        return opt ? opt.dataset.code : '';
    }

    // ✅ Liste de villes toute prête pour le pays choisi (config/geo.php).
    function selectedCountryCities() {
        const opt = countrySelect.options[countrySelect.selectedIndex];
        if (!opt || !opt.dataset.cities) return [];
        try { return JSON.parse(opt.dataset.cities); } catch (e) { return []; }
    }

    function renderCityList(cities) {
        if (!cities.length) { dropdown.className = 'city-dropdown'; return; }
        dropdown.className = 'city-dropdown open';
        dropdown.innerHTML = cities.map(city => `
            <div class="city-item" data-city="${city}">
                <svg width="12" height="14" viewBox="0 0 12 16" fill="none" style="flex-shrink:0">
                    <path d="M6 0C3.24 0 1 2.24 1 5c0 3.75 5 11 5 11s5-7.25 5-11c0-2.76-2.24-5-5-5zm0 6.5C5.17 6.5 4.5 5.83 4.5 5S5.17 3.5 6 3.5 7.5 4.17 7.5 5 6.83 6.5 6 6.5z" fill="#ec7211"/>
                </svg>
                <div class="city-name">${city}</div>
            </div>`).join('');
        dropdown.querySelectorAll('.city-item').forEach(el => {
            el.addEventListener('click', function () {
                cityInput.value = this.dataset.city;
                dropdown.className = 'city-dropdown';
            });
        });
    }

    // ✅ Pour un pays SANS liste courte codée en dur, on récupère TOUTES ses
    // villes/communes via Overpass (OpenStreetMap) dès qu'il est choisi.
    // Résultat mis en cache par pays pour ne l'interroger qu'une fois.
    const overpassCache = {};
    let activeCityList = [];
    let activeCityListCode = null;

    async function fetchAllCitiesForCountry(code) {
        const query = `[out:json][timeout:25];area["ISO3166-1"="${code}"][admin_level=2]->.a;(node["place"~"^(city|town)$"](area.a););out body 400;`;
        const res  = await fetch('https://overpass-api.de/api/interpreter', {
            method: 'POST', body: 'data=' + encodeURIComponent(query),
        });
        const data = await res.json();
        const names = new Set();
        (data.elements || []).forEach(el => { if (el.tags && el.tags.name) names.add(el.tags.name); });
        return Array.from(names).sort((a, b) => a.localeCompare(b, 'fr'));
    }

    function loadCitiesForSelectedCountry(openDropdown) {
        const code = selectedCountryCode();
        const curated = selectedCountryCities();

        if (curated.length) {
            activeCityList = curated; activeCityListCode = code;
            if (openDropdown) renderCityList(activeCityList);
            return;
        }

        if (!code) { activeCityList = []; activeCityListCode = null; return; }

        if (overpassCache[code]) {
            activeCityList = overpassCache[code]; activeCityListCode = code;
            if (openDropdown && activeCityList.length) renderCityList(activeCityList);
            return;
        }

        activeCityList = []; activeCityListCode = code;
        if (openDropdown) {
            dropdown.className = 'city-dropdown open';
            dropdown.innerHTML = '<div class="city-loading">Chargement des villes…</div>';
        }
        fetchAllCitiesForCountry(code).then(list => {
            overpassCache[code] = list;
            if (selectedCountryCode() !== code) return;
            activeCityList = list;
            if (!openDropdown) return;
            if (list.length) renderCityList(list);
            else dropdown.innerHTML = '<div class="city-loading">Aucune ville trouvée — tapez pour rechercher</div>';
        }).catch(() => {
            if (selectedCountryCode() !== code || !openDropdown) return;
            dropdown.innerHTML = '<div class="city-loading">Chargement impossible — tapez pour rechercher</div>';
        });
    }

    // Dès qu'un pays est choisi, affiche directement toutes ses villes.
    countrySelect.addEventListener('change', function () {
        loadCitiesForSelectedCountry(true);
    });

    cityInput.addEventListener('focus', function () {
        if (this.value.trim()) return;
        if (activeCityList.length && activeCityListCode === selectedCountryCode()) {
            renderCityList(activeCityList);
        } else {
            loadCitiesForSelectedCountry(true);
        }
    });

    cityInput.addEventListener('input', function () {
        const q = this.value.trim();
        clearTimeout(timer);

        if (activeCityList.length && activeCityListCode === selectedCountryCode()) {
            const filtered = q.length
                ? activeCityList.filter(c => c.toLowerCase().includes(q.toLowerCase()))
                : activeCityList;
            renderCityList(filtered);
            return;
        }

        if (q.length < 2) { dropdown.className = 'city-dropdown'; return; }
        dropdown.className = 'city-dropdown open';
        dropdown.innerHTML = '<div class="city-loading">Recherche…</div>';
        timer = setTimeout(() => search(q), 350);
    });

    document.addEventListener('click', function (e) {
        if (!cityInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.className = 'city-dropdown';
        }
    });

    async function search(q) {
        try {
            const code = selectedCountryCode();
            let url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(q)}&format=json&limit=8&addressdetails=1&accept-language=fr`;
            if (code) url += `&countrycodes=${code.toLowerCase()}`;

            const res  = await fetch(url);
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
                    cityInput.value = this.dataset.city;
                    dropdown.className = 'city-dropdown';
                });
            });
        } catch (e) {
            dropdown.innerHTML = '<div class="city-loading">Erreur de connexion</div>';
        }
    }

    // Pré-charge silencieusement (sans ouvrir le menu) : sur la page de
    // modification, un pays est déjà sélectionné au chargement.
    if (countrySelect.value) loadCitiesForSelectedCountry(false);
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
