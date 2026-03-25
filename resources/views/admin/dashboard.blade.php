@extends('admin.layouts.app')

@section('content')
<div class="space-y-6">

    {{-- ===== CARTE INTERACTIVE ===== --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">

        {{-- Header carte --}}
        <div class="p-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <span class="text-lg">📍</span>
                <div>
                    <h2 class="font-bold text-gray-800">Suivi des chauffeurs en temps réel</h2>
                    <p class="text-xs text-gray-400">Mis à jour toutes les 10 secondes</p>
                </div>
                <div class="flex items-center gap-2 ml-4">
                    <span class="w-2.5 h-2.5 rounded-full bg-green-400 animate-pulse"></span>
                    <span class="text-xs text-green-600 font-medium" id="onlineCount">— en ligne</span>
                </div>
            </div>

            {{-- Légende --}}
            <div class="flex items-center gap-4 text-xs">
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span> En ligne</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-yellow-400 inline-block"></span> En pause</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-gray-400 inline-block"></span> Hors ligne</span>
            </div>
        </div>

        {{-- Barre de recherche --}}
        <div class="p-4 bg-gray-50 border-b border-gray-100">
            <div class="flex gap-3 flex-wrap items-end">
                <div class="flex-1 min-w-40">
                    <label class="block text-xs font-medium text-gray-600 mb-1">👤 Nom chauffeur</label>
                    <input type="text" id="search_chauffeur" placeholder="Ex: Jean Dupont"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex-1 min-w-36">
                    <label class="block text-xs font-medium text-gray-600 mb-1">🔢 Immatriculation</label>
                    <input type="text" id="search_matricule" placeholder="Ex: AB-1234-CD"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex-1 min-w-32">
                    <label class="block text-xs font-medium text-gray-600 mb-1">🎨 Couleur</label>
                    <input type="text" id="search_couleur" placeholder="Ex: Blanc"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">📡 Statut</label>
                    <select id="search_status"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all">Tous</option>
                        <option value="online">En ligne</option>
                        <option value="pause">En pause</option>
                        <option value="offline">Hors ligne</option>
                    </select>
                </div>
                <button onclick="searchAndZoom()"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
                    🔍 Rechercher & Zoomer
                </button>
                <button onclick="resetSearch()"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm transition">
                    ✕ Reset
                </button>
            </div>

            {{-- Résultats recherche --}}
            <div id="searchResults" class="hidden mt-3 flex gap-2 flex-wrap"></div>
        </div>

        {{-- Carte Leaflet --}}
        <div id="map" style="height: 480px; z-index: 1;"></div>

    </div>

    {{-- ===== STATS ===== --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 border-l-4 border-l-blue-500">
            <div class="text-sm text-gray-500 mb-1">Utilisateurs</div>
            <div class="text-3xl font-bold text-gray-800">{{ $stats['total_users'] }}</div>
            <div class="text-xs text-green-500 mt-1">+{{ $stats['new_users_today'] }} aujourd'hui</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 border-l-4 border-l-yellow-400">
            <div class="text-sm text-gray-500 mb-1">Chauffeurs actifs</div>
            <div class="text-3xl font-bold text-yellow-500">{{ $stats['active_drivers'] }}</div>
            <div class="text-xs text-green-500 mt-1">{{ $stats['online_drivers'] }} en ligne</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 border-l-4 border-l-gray-800">
            <div class="text-sm text-gray-500 mb-1">Courses aujourd'hui</div>
            <div class="text-3xl font-bold text-gray-800">{{ $stats['today_rides'] }}</div>
            <div class="text-xs text-gray-400 mt-1">{{ $stats['active_rides'] }} en cours</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 border-l-4 border-l-green-500">
            <div class="text-sm text-gray-500 mb-1">Revenus du jour</div>
            <div class="text-2xl font-bold text-green-600">{{ number_format($stats['today_revenue'], 0, ',', ' ') }} XAF</div>
            <div class="text-xs text-gray-400 mt-1">Commission : {{ number_format($stats['today_commission'], 0, ',', ' ') }} XAF</div>
        </div>
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