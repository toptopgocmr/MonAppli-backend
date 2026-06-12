@extends('admin.layouts.app')
@section('title','Dashboard')

@section('content')
@php
/* Helper : retourne un badge HTML de tendance */
function kpiTrend($now, $prev, $label, $lowerIsBetter = false): string {
    $now  = (float) $now;
    $prev = (float) $prev;
    if ($prev == 0 && $now == 0) return '<span style="color:#879596;font-size:11px">— ' . $label . '</span>';
    if ($prev == 0) return '<span style="color:#067D49;font-size:11px;font-weight:600">↑ Nouveau ' . $label . '</span>';
    $pct  = round(abs($now - $prev) / $prev * 100, 1);
    if ($now > $prev) {
        $color = $lowerIsBetter ? '#D13212' : '#067D49';
        return '<span style="color:' . $color . ';font-size:11px;font-weight:600">↑ +' . $pct . '% ' . $label . '</span>';
    }
    if ($now < $prev) {
        $color = $lowerIsBetter ? '#067D49' : '#D13212';
        return '<span style="color:' . $color . ';font-size:11px;font-weight:600">↓ -' . $pct . '% ' . $label . '</span>';
    }
    return '<span style="color:#879596;font-size:11px">→ stable ' . $label . '</span>';
}
@endphp

<div style="display:flex;flex-direction:column;gap:20px">

    {{-- KPI Cards --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">

        {{-- Utilisateurs --}}
        <a href="{{ route('admin.users.index') }}" style="text-decoration:none;color:inherit;display:block">
        <div class="stat-card" style="border-top:3px solid #1DA1F2">
            <div class="stat-icon" style="background:linear-gradient(135deg,#EFF8FF,#dbeafe)">
                <svg width="19" height="19" fill="none" stroke="#1DA1F2" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="stat-val">{{ $stats['total_users'] }}</div>
            <div class="stat-lbl">Utilisateurs inscrits</div>
            <div class="stat-sub"><span style="color:#10B981;font-weight:600">+{{ $stats['new_users_today'] }}</span> aujourd'hui</div>
            <div class="stat-trend">{!! kpiTrend($stats['users_this_month'], $stats['users_last_month'], 'vs mois dernier') !!}</div>
        </div>
        </a>

        {{-- Sociétés --}}
        <a href="{{ route('admin.companies.index') }}" style="text-decoration:none;color:inherit;display:block">
        <div class="stat-card" style="border-top:3px solid #16A34A">
            <div class="stat-icon" style="background:linear-gradient(135deg,#F0FDF4,#dcfce7)">
                <svg width="19" height="19" fill="none" stroke="#16A34A" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
            </div>
            <div class="stat-val">{{ $stats['total_companies'] }}</div>
            <div class="stat-lbl">Sociétés inscrites</div>
            <div class="stat-sub">Partenaires actifs</div>
            <div class="stat-trend">{!! kpiTrend($stats['companies_this_month'], $stats['companies_last_month'], 'vs mois dernier') !!}</div>
        </div>
        </a>

        {{-- Chauffeurs --}}
        <a href="{{ route('admin.drivers.index') }}" style="text-decoration:none;color:inherit;display:block">
        <div class="stat-card" style="border-top:3px solid #F59E0B">
            <div class="stat-icon" style="background:linear-gradient(135deg,#FFFBEB,#fef3c7)">
                <svg width="19" height="19" fill="none" stroke="#D97706" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="10" r="3"/><path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 1 0-16 0c0 3 2.7 6.9 8 11.7z"/></svg>
            </div>
            <div class="stat-val">{{ $stats['active_drivers'] }}</div>
            <div class="stat-lbl">Chauffeurs actifs</div>
            <div class="stat-sub">{{ $stats['online_drivers'] }} en ligne · <span style="color:#D97706">{{ $stats['drivers_pending'] }} en attente</span></div>
            <div class="stat-trend">{!! kpiTrend($stats['active_drivers'], $stats['drivers_last_week'], 'vs sem. dernière') !!}</div>
        </div>
        </a>

        {{-- Courses --}}
        <a href="{{ route('admin.trips.index') }}" style="text-decoration:none;color:inherit;display:block">
        <div class="stat-card" style="border-top:3px solid #6366F1">
            <div class="stat-icon" style="background:linear-gradient(135deg,#EEF2FF,#e0e7ff)">
                <svg width="19" height="19" fill="none" stroke="#6366F1" stroke-width="2" viewBox="0 0 24 24"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
            </div>
            <div class="stat-val">{{ $stats['today_rides'] }}</div>
            <div class="stat-lbl">Courses aujourd'hui</div>
            <div class="stat-sub">{{ $stats['active_rides'] }} en cours · 7j : <strong>{{ $stats['rides_this_week'] }}</strong></div>
            <div class="stat-trend">{!! kpiTrend($stats['today_rides'], $stats['rides_yesterday'], 'vs hier') !!}</div>
        </div>
        </a>

        {{-- Revenus --}}
        <a href="{{ route('admin.revenus.index') }}" style="text-decoration:none;color:inherit;display:block">
        <div class="stat-card" style="border-top:3px solid #10B981">
            <div class="stat-icon" style="background:linear-gradient(135deg,#ECFDF5,#d1fae5)">
                <svg width="19" height="19" fill="none" stroke="#059669" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div class="stat-val" style="font-size:22px">{{ number_format($stats['today_revenue'],0,',',' ') }}</div>
            <div class="stat-lbl">Revenus du jour (XAF)</div>
            <div class="stat-sub">Commission : <strong>{{ number_format($stats['today_commission'],0,',',' ') }}</strong> XAF</div>
            <div class="stat-trend">{!! kpiTrend($stats['today_revenue'], $stats['revenue_yesterday'], 'vs hier') !!}</div>
        </div>
        </a>

        {{-- Commissions --}}
        <a href="{{ route('admin.commission-rates.index') }}" style="text-decoration:none;color:inherit;display:block">
        <div class="stat-card" style="border-top:3px solid #EA580C">
            <div class="stat-icon" style="background:linear-gradient(135deg,#FFF7ED,#fed7aa)">
                <svg width="19" height="19" fill="none" stroke="#EA580C" stroke-width="2" viewBox="0 0 24 24"><path d="M9 14l6-6m0 6l-6-6"/><circle cx="9" cy="9" r="1" fill="#EA580C" stroke="none"/><circle cx="15" cy="15" r="1" fill="#EA580C" stroke="none"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
            </div>
            <div class="stat-val" style="font-size:22px">{{ number_format($stats['total_commission'],0,',',' ') }}</div>
            <div class="stat-lbl">Commissions totales (XAF)</div>
            <div class="stat-sub">Ce mois : <strong>{{ number_format($stats['commission_this_month'],0,',',' ') }}</strong> XAF</div>
            <div class="stat-trend">{!! kpiTrend($stats['commission_this_month'], $stats['commission_last_month'], 'vs mois dernier') !!}</div>
        </div>
        </a>

        {{-- SOS --}}
        <a href="{{ route('admin.sos.index') }}" style="text-decoration:none;color:inherit;display:block">
        <div class="stat-card" style="border-top:3px solid {{ $stats['sos_active'] > 0 ? '#DC2626' : '#94A3B8' }};{{ $stats['sos_active'] > 0 ? 'background:linear-gradient(180deg,#fff5f5,#fff);' : '' }}">
            <div class="stat-icon" style="background:linear-gradient(135deg,#FEF2F2,#fecaca)">
                <svg width="19" height="19" fill="none" stroke="#DC2626" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div class="stat-val" style="{{ $stats['sos_active'] > 0 ? 'color:#DC2626' : '' }}">{{ $stats['sos_active'] }}</div>
            <div class="stat-lbl">Alertes SOS actives</div>
            <div class="stat-sub" style="{{ $stats['sos_active'] > 0 ? 'color:#DC2626;font-weight:700' : '' }}">
                {{ $stats['sos_active'] > 0 ? '⚠ Intervention requise' : '✓ Aucune alerte' }}
            </div>
            <div class="stat-trend">
                @if($stats['sos_week'] > 0)
                    <span style="color:#879596">7j : {{ $stats['sos_week'] }} · {{ $stats['sos_treated_week'] }} traitée(s)
                    <span style="color:{{ $stats['sos_treated_week'] >= $stats['sos_week'] ? '#067D49' : '#D13212' }};font-weight:700">
                        ({{ round($stats['sos_treated_week'] / $stats['sos_week'] * 100) }}% résolues)
                    </span></span>
                @else
                    <span style="color:#879596">Aucune alerte cette semaine</span>
                @endif
            </div>
        </div>
        </a>

        {{-- Administrateurs --}}
        <a href="{{ route('admin.profiles.index') }}" style="text-decoration:none;color:inherit;display:block">
        <div class="stat-card" style="border-top:3px solid #7C3AED">
            <div class="stat-icon" style="background:linear-gradient(135deg,#EDE9FE,#ddd6fe)">
                <svg width="19" height="19" fill="none" stroke="#7C3AED" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <div class="stat-val">{{ $stats['total_admins'] }}</div>
            <div class="stat-lbl">Administrateurs</div>
            <div class="stat-sub">Comptes admin actifs</div>
            <div class="stat-trend"><span style="color:#879596">— Accès sécurisé</span></div>
        </div>
        </a>

    </div>

    {{-- Carte --}}
    <div class="panel">
        <div class="panel-header">
            <div style="display:flex;align-items:center;gap:14px">
                <div>
                    <div style="font-size:14px;font-weight:600;color:#0F172A">Suivi des chauffeurs en temps réel</div>
                    <div style="font-size:12px;color:#94A3B8;margin-top:1px">Mis à jour toutes les 10 secondes</div>
                </div>
                <span style="display:inline-flex;align-items:center;gap:6px;background:#ECFDF5;border:1px solid #BBF7D0;padding:4px 12px;border-radius:99px;font-size:12px;color:#15803D;font-weight:600">
                    <span style="width:7px;height:7px;border-radius:50%;background:#22C55E;display:inline-block;animation:pulse 1.5s infinite"></span>
                    <span id="onlineCount">—</span>
                </span>
            </div>
            <div style="display:flex;align-items:center;gap:14px;font-size:12px;color:#64748B">
                <span style="display:flex;align-items:center;gap:5px"><span style="width:8px;height:8px;border-radius:50%;background:#22C55E;display:inline-block"></span>En ligne</span>
                <span style="display:flex;align-items:center;gap:5px"><span style="width:8px;height:8px;border-radius:50%;background:#FACC15;display:inline-block"></span>En pause</span>
                <span style="display:flex;align-items:center;gap:5px"><span style="width:8px;height:8px;border-radius:50%;background:#9CA3AF;display:inline-block"></span>Hors ligne</span>
            </div>
        </div>

        {{-- Filtres carte --}}
        <div style="padding:14px 20px;border-bottom:1px solid #F1F5F9;background:#FAFBFC">
            <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
                <div style="flex:1;min-width:150px">
                    <label class="ttg-label">Nom chauffeur</label>
                    <input type="text" id="search_chauffeur" placeholder="Jean Dupont" class="ttg-input">
                </div>
                <div style="flex:1;min-width:130px">
                    <label class="ttg-label">Immatriculation</label>
                    <input type="text" id="search_matricule" placeholder="AB-1234-CD" class="ttg-input">
                </div>
                <div style="flex:1;min-width:110px">
                    <label class="ttg-label">Couleur</label>
                    <input type="text" id="search_couleur" placeholder="Blanc" class="ttg-input">
                </div>
                <div>
                    <label class="ttg-label">Statut</label>
                    <select id="search_status" class="ttg-select" style="min-width:120px">
                        <option value="all">Tous</option>
                        <option value="online">En ligne</option>
                        <option value="pause">En pause</option>
                        <option value="offline">Hors ligne</option>
                    </select>
                </div>
                <button onclick="searchAndZoom()" class="btn btn-primary">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Rechercher
                </button>
                <button onclick="resetSearch()" class="btn btn-secondary">Réinitialiser</button>
            </div>
            <div id="searchResults" class="hidden" style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap"></div>
        </div>

        <div id="map" style="height:480px;z-index:1"></div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const LIVE_URL   = "{{ route('admin.drivers.live') }}";
const REFRESH_MS = 10000;

const map = L.map('map', { zoomControl: true }).setView([4.0, 15.0], 5);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap', maxZoom: 19,
}).addTo(map);

function makeIcon(status) {
    const colors = { online:'#22C55E', pause:'#FACC15', offline:'#9CA3AF' };
    const c = colors[status] || '#9CA3AF';
    return L.divIcon({
        html: `<div style="background:${c};border:3px solid #fff;border-radius:50%;width:34px;height:34px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,.25)">
            <svg width="14" height="14" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="10" r="3"/><path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 1 0-16 0c0 3 2.7 6.9 8 11.7z"/></svg>
        </div>`,
        iconSize: [34, 34], iconAnchor: [17, 17], popupAnchor: [0, -20], className: '',
    });
}

let markers = {};
let allDrivers = [];

function updateMarkers(drivers) {
    allDrivers = drivers;
    const seen = new Set();

    drivers.forEach(d => {
        if (!d.lat || !d.lng) return;
        seen.add(d.id);
        const sc = d.driver_status === 'online' ? '#22C55E' : d.driver_status === 'pause' ? '#F59E0B' : '#9CA3AF';
        const sl = d.driver_status === 'online' ? 'En ligne' : d.driver_status === 'pause' ? 'En pause' : 'Hors ligne';
        const popup = `
            <div style="font-family:Inter,sans-serif;min-width:190px;font-size:13px">
                <div style="font-weight:700;font-size:14px;margin-bottom:8px;color:#0F172A">${d.first_name} ${d.last_name}</div>
                <div style="display:flex;flex-direction:column;gap:4px;color:#475569">
                    <span>${d.phone ?? '—'}</span>
                    <span><strong>${d.vehicle_plate ?? '—'}</strong></span>
                    <span>${d.vehicle_color ?? '—'} · ${d.vehicle_brand ?? ''} ${d.vehicle_model ?? ''}</span>
                    <span style="color:${sc};font-weight:600;margin-top:4px">● ${sl}</span>
                </div>
            </div>`;

        if (markers[d.id]) {
            markers[d.id].setLatLng([d.lat, d.lng]).setIcon(makeIcon(d.driver_status)).setPopupContent(popup);
        } else {
            markers[d.id] = L.marker([d.lat, d.lng], { icon: makeIcon(d.driver_status) })
                             .addTo(map).bindPopup(popup);
        }
    });

    Object.keys(markers).forEach(id => {
        if (!seen.has(parseInt(id))) { map.removeLayer(markers[id]); delete markers[id]; }
    });

    const n = drivers.filter(d => d.driver_status === 'online').length;
    document.getElementById('onlineCount').textContent = `${n} en ligne · ${drivers.length} total`;
}

function getFilters() {
    return {
        chauffeur: document.getElementById('search_chauffeur').value.trim(),
        matricule: document.getElementById('search_matricule').value.trim(),
        couleur:   document.getElementById('search_couleur').value.trim(),
        status:    document.getElementById('search_status').value,
    };
}

function fetchDrivers(filters = {}, zoom = false) {
    const p = new URLSearchParams();
    if (filters.chauffeur) p.append('chauffeur', filters.chauffeur);
    if (filters.matricule) p.append('matricule', filters.matricule);
    if (filters.couleur)   p.append('couleur', filters.couleur);
    if (filters.status && filters.status !== 'all') p.append('status', filters.status);

    fetch(`${LIVE_URL}?${p}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            updateMarkers(data.drivers);
            if (zoom && data.drivers.length > 0) {
                showSearchResults(data.drivers);
                if (data.drivers.length === 1) {
                    const d = data.drivers[0];
                    map.setView([d.lat, d.lng], 17);
                    if (markers[d.id]) markers[d.id].openPopup();
                } else {
                    map.fitBounds(L.latLngBounds(data.drivers.map(d => [d.lat, d.lng])), { padding: [50, 50], maxZoom: 15 });
                }
            } else if (zoom) showNoResult();
        })
        .catch(e => console.error(e));
}

function searchAndZoom() { fetchDrivers(getFilters(), true); }

function resetSearch() {
    ['search_chauffeur','search_matricule','search_couleur'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('search_status').value = 'all';
    document.getElementById('searchResults').classList.add('hidden');
    fetchDrivers({}, false);
    map.setView([4.0, 15.0], 5);
}

function showSearchResults(drivers) {
    const box = document.getElementById('searchResults');
    box.innerHTML = drivers.map(d => {
        const sc = d.driver_status === 'online' ? '#22C55E' : d.driver_status === 'pause' ? '#FACC15' : '#9CA3AF';
        return `<button onclick="zoomToDriver(${d.id})"
            style="background:#fff;border:1px solid #E2E8F0;padding:5px 12px;border-radius:7px;font-size:12px;
                   display:flex;align-items:center;gap:6px;cursor:pointer;font-family:Inter,sans-serif">
            <span style="width:7px;height:7px;border-radius:50%;background:${sc};display:inline-block"></span>
            <strong>${d.first_name} ${d.last_name}</strong>
            <span style="color:#94A3B8">${d.vehicle_plate ?? ''}</span>
        </button>`;
    }).join('');
    box.classList.remove('hidden');
}

function showNoResult() {
    const box = document.getElementById('searchResults');
    box.innerHTML = `<span style="font-size:13px;color:#EF4444">Aucun chauffeur trouvé avec ces critères</span>`;
    box.classList.remove('hidden');
}

function zoomToDriver(id) {
    const d = allDrivers.find(x => x.id === id);
    if (d) { map.setView([d.lat, d.lng], 17); if (markers[d.id]) markers[d.id].openPopup(); }
}

fetchDrivers();
setInterval(() => fetchDrivers(getFilters()), REFRESH_MS);

['search_chauffeur','search_matricule','search_couleur'].forEach(id => {
    document.getElementById(id).addEventListener('keydown', e => { if (e.key === 'Enter') searchAndZoom(); });
});
</script>
@endpush
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            