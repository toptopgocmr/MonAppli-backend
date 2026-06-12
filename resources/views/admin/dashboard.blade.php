@extends('admin.layouts.app')
@section('title','Dashboard')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px">

    {{-- KPI Cards --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px">
        <div class="stat-card" style="border-left:4px solid #1DA1F2">
            <div class="lbl">👤 Clients inscrits</div>
            <div class="val" style="color:#1DA1F2">{{ $stats['total_users'] }}</div>
            <div class="sub" style="color:#22c55e">+{{ $stats['new_users_today'] }} aujourd'hui</div>
        </div>
        <div class="stat-card" style="border-left:4px solid #FFC107">
            <div class="lbl">🚗 Chauffeurs actifs</div>
            <div class="val" style="color:#d97706">{{ $stats['active_drivers'] }}</div>
            <div class="sub" style="color:#22c55e">{{ $stats['online_drivers'] }} en ligne maintenant</div>
        </div>
        <div class="stat-card" style="border-left:4px solid #6366f1">
            <div class="lbl">📍 Courses aujourd'hui</div>
            <div class="val" style="color:#4f46e5">{{ $stats['today_rides'] }}</div>
            <div class="sub">{{ $stats['active_rides'] }} en cours</div>
        </div>
        <div class="stat-card" style="border-left:4px solid #22c55e">
            <div class="lbl">💰 Revenus du jour</div>
            <div class="val" style="color:#16a34a;font-size:22px">{{ number_format($stats['today_revenue'],0,',',' ') }} XAF</div>
            <div class="sub">Commission : {{ number_format($stats['today_commission'],0,',',' ') }} XAF</div>
        </div>
    </div>

    {{-- Carte temps réel --}}
    <div class="panel">
        <div class="panel-header">
            <div style="display:flex;align-items:center;gap:12px">
                <div>
                    <h2 style="font-size:15px;font-weight:700;color:#0f172a">📍 Suivi des chauffeurs en temps réel</h2>
                    <p style="font-size:12px;color:#94a3b8;margin-top:2px">Mis à jour toutes les 10 secondes</p>
                </div>
                <div style="display:flex;align-items:center;gap:6px;background:#f0fdf4;border:1px solid #bbf7d0;padding:4px 12px;border-radius:99px">
                    <span style="width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block;animation:pulse 1.5s infinite"></span>
                    <span style="font-size:12px;color:#16a34a;font-weight:600" id="onlineCount">— en ligne</span>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:14px;font-size:12px;color:#64748b">
                <span style="display:flex;align-items:center;gap:5px"><span style="width:10px;height:10px;border-radius:50%;background:#22c55e;display:inline-block"></span>En ligne</span>
                <span style="display:flex;align-items:center;gap:5px"><span style="width:10px;height:10px;border-radius:50%;background:#facc15;display:inline-block"></span>En pause</span>
                <span style="display:flex;align-items:center;gap:5px"><span style="width:10px;height:10px;border-radius:50%;background:#9ca3af;display:inline-block"></span>Hors ligne</span>
            </div>
        </div>

        {{-- Filtres --}}
        <div style="padding:14px 20px;background:#f8fafc;border-bottom:1px solid #e2e8f0">
            <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
                <div style="flex:1;min-width:160px">
                    <label class="ttg-label">👤 Nom chauffeur</label>
                    <input type="text" id="search_chauffeur" placeholder="Ex: Jean Dupont" class="ttg-input">
                </div>
                <div style="flex:1;min-width:140px">
                    <label class="ttg-label">🔢 Immatriculation</label>
                    <input type="text" id="search_matricule" placeholder="Ex: AB-1234-CD" class="ttg-input">
                </div>
                <div style="flex:1;min-width:120px">
                    <label class="ttg-label">🎨 Couleur</label>
                    <input type="text" id="search_couleur" placeholder="Ex: Blanc" class="ttg-input">
                </div>
                <div>
                    <label class="ttg-label">📡 Statut</label>
                    <select id="search_status" class="ttg-select" style="min-width:120px">
                        <option value="all">Tous</option>
                        <option value="online">En ligne</option>
                        <option value="pause">En pause</option>
                        <option value="offline">Hors ligne</option>
                    </select>
                </div>
                <button onclick="searchAndZoom()" class="btn btn-primary">🔍 Rechercher & Zoomer</button>
                <button onclick="resetSearch()" class="btn btn-gray">✕ Reset</button>
            </div>
            <div id="searchResults" class="hidden" style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap"></div>
        </div>

        <div id="map" style="height:480px;z-index:1"></div>
    </div>

</div>
@endsection

@push('scripts')
<script>
// ================================================================
// CONFIGURATION
// ================================================================
const LIVE_URL  = "{{ route('admin.drivers.live') }}";
const REFRESH_MS = 10000; // 10 secondes

// ================================================================
// INITIALISATION CARTE
// ================================================================
const map = L.map('map', { zoomControl: true }).setView([4.0, 15.0], 5);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap',
    maxZoom: 19,
}).addTo(map);

// ================================================================
// ICÔNES PAR STATUT
// ================================================================
function makeIcon(status) {
    const colors = { online: '#22c55e', pause: '#facc15', offline: '#9ca3af' };
    const color  = colors[status] || '#9ca3af';
    const svg = `
        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="44" viewBox="0 0 36 44">
            <ellipse cx="18" cy="42" rx="8" ry="3" fill="rgba(0,0,0,0.2)"/>
            <path d="M18 0 C8 0 0 8 0 18 C0 30 18 44 18 44 C18 44 36 30 36 18 C36 8 28 0 18 0Z"
                  fill="${color}" stroke="white" stroke-width="2"/>
            <text x="18" y="23" text-anchor="middle" font-size="14" fill="white">🚗</text>
        </svg>`;
    return L.divIcon({
        html: svg,
        iconSize: [36, 44],
        iconAnchor: [18, 44],
        popupAnchor: [0, -44],
        className: '',
    });
}

// ================================================================
// GESTION DES MARQUEURS
// ================================================================
let markers = {};
let allDrivers = [];

function updateMarkers(drivers) {
    allDrivers = drivers;
    const seen = new Set();

    drivers.forEach(d => {
        if (!d.lat || !d.lng) return;
        seen.add(d.id);

        const popup = `
            <div style="min-width:200px; font-family: sans-serif;">
                <div style="font-weight:bold; font-size:14px; margin-bottom:6px;">
                    🚗 ${d.first_name} ${d.last_name}
                </div>
                <div style="font-size:12px; color:#555; line-height:1.8;">
                    📱 ${d.phone ?? '—'}<br>
                    🔢 <b>${d.vehicle_plate ?? '—'}</b><br>
                    🎨 ${d.vehicle_color ?? '—'} · ${d.vehicle_brand ?? ''} ${d.vehicle_model ?? ''}<br>
                    🏷️ ${d.vehicle_type ?? '—'}<br>
                    📡 <span style="color:${d.driver_status === 'online' ? '#22c55e' : (d.driver_status === 'pause' ? '#f59e0b' : '#9ca3af')}; font-weight:bold;">
                        ${d.driver_status === 'online' ? '● En ligne' : d.driver_status === 'pause' ? '● En pause' : '● Hors ligne'}
                    </span><br>
                    🕐 ${d.updated_at}
                </div>
            </div>`;

        if (markers[d.id]) {
            markers[d.id].setLatLng([d.lat, d.lng])
                         .setIcon(makeIcon(d.driver_status))
                         .setPopupContent(popup);
        } else {
            markers[d.id] = L.marker([d.lat, d.lng], { icon: makeIcon(d.driver_status) })
                             .addTo(map)
                             .bindPopup(popup);
        }
    });

    // Supprimer marqueurs disparus
    Object.keys(markers).forEach(id => {
        if (!seen.has(parseInt(id))) {
            map.removeLayer(markers[id]);
            delete markers[id];
        }
    });

    // Compteur en ligne
    const onlineCount = drivers.filter(d => d.driver_status === 'online').length;
    document.getElementById('onlineCount').textContent = `${onlineCount} en ligne · ${drivers.length} total`;
}

// ================================================================
// RECHERCHE + ZOOM
// ================================================================
function getFilters() {
    return {
        chauffeur: document.getElementById('search_chauffeur').value.trim(),
        matricule: document.getElementById('search_matricule').value.trim(),
        couleur:   document.getElementById('search_couleur').value.trim(),
        status:    document.getElementById('search_status').value,
    };
}

function searchAndZoom() {
    const f = getFilters();
    fetchDrivers(f, true);
}

function resetSearch() {
    document.getElementById('search_chauffeur').value = '';
    document.getElementById('search_matricule').value = '';
    document.getElementById('search_couleur').value   = '';
    document.getElementById('search_status').value    = 'all';
    document.getElementById('searchResults').classList.add('hidden');
    document.getElementById('searchResults').innerHTML = '';
    fetchDrivers({}, false);
    map.setView([4.0, 15.0], 5);
}

function fetchDrivers(filters = {}, zoom = false) {
    const params = new URLSearchParams();
    if (filters.chauffeur) params.append('chauffeur', filters.chauffeur);
    if (filters.matricule) params.append('matricule', filters.matricule);
    if (filters.couleur)   params.append('couleur',   filters.couleur);
    if (filters.status && filters.status !== 'all') params.append('status', filters.status);

    fetch(`${LIVE_URL}?${params.toString()}`, {
        headers: { 'Accept': 'application/json',
                   'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        updateMarkers(data.drivers);

        if (zoom && data.drivers.length > 0) {
            showSearchResults(data.drivers);

            if (data.drivers.length === 1) {
                // Un seul résultat → zoom maximal + popup
                const d = data.drivers[0];
                map.setView([d.lat, d.lng], 17);
                if (markers[d.id]) markers[d.id].openPopup();
            } else {
                // Plusieurs → ajuster la vue pour tout afficher
                const bounds = L.latLngBounds(
                    data.drivers.map(d => [d.lat, d.lng])
                );
                map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
            }
        } else if (zoom && data.drivers.length === 0) {
            showNoResult();
        }
    })
    .catch(err => console.error('Erreur fetch drivers:', err));
}

function showSearchResults(drivers) {
    const box = document.getElementById('searchResults');
    box.innerHTML = drivers.map(d => `
        <button onclick="zoomToDriver(${d.id})"
            class="bg-white border border-gray-200 hover:border-blue-400 hover:bg-blue-50
                   rounded-lg px-3 py-2 text-xs flex items-center gap-2 transition shadow-sm">
            <span class="w-2 h-2 rounded-full ${d.driver_status === 'online' ? 'bg-green-500' : (d.driver_status === 'pause' ? 'bg-yellow-400' : 'bg-gray-400')}"></span>
            <span class="font-medium">${d.first_name} ${d.last_name}</span>
            <span class="text-gray-400">${d.vehicle_plate ?? '—'}</span>
        </button>
    `).join('');
    box.classList.remove('hidden');
}

function showNoResult() {
    const box = document.getElementById('searchResults');
    box.innerHTML = `<span class="text-sm text-red-500">⚠️ Aucun chauffeur trouvé avec ces critères</span>`;
    box.classList.remove('hidden');
}

function zoomToDriver(driverId) {
    const d = allDrivers.find(x => x.id === driverId);
    if (!d) return;
    map.setView([d.lat, d.lng], 17);
    if (markers[d.id]) markers[d.id].openPopup();
}

// ================================================================
// LANCEMENT + REFRESH AUTO
// ================================================================
fetchDrivers(); // Chargement initial

setInterval(() => {
    fetchDrivers(getFilters()); // Refresh avec filtres actifs
}, REFRESH_MS);

// Recherche au Enter
['search_chauffeur', 'search_matricule', 'search_couleur'].forEach(id => {
    document.getElementById(id).addEventListener('keydown', e => {
        if (e.key === 'Enter') searchAndZoom();
    });
});
</script>
@endpush