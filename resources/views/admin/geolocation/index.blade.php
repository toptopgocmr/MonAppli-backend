@extends('admin.layouts.app')
@section('title','Géolocalisation')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px">

    <div class="page-header">
        <h1 class="page-title">Géolocalisation en temps réel</h1>
        <p class="page-sub">Position des chauffeurs actifs — actualisé toutes les 30 secondes</p>
    </div>

    <div class="panel" style="overflow:hidden">
        <div class="panel-header">
            <span>Carte en temps réel</span>
            <span class="badge badge-success">En direct</span>
        </div>
        <div id="map" style="height:500px;width:100%;z-index:1"></div>
    </div>

    <div class="panel" style="overflow:hidden">
        <div class="panel-header">
            <span>Chauffeurs en ligne</span>
            <span class="badge badge-gray" id="driver-count">—</span>
        </div>
        <div style="overflow-x:auto">
            <table class="ttg-table">
                <thead>
                    <tr>
                        <th>Chauffeur</th>
                        <th>Téléphone</th>
                        <th>Statut</th>
                        <th>Dernière position</th>
                        <th>Mise à jour</th>
                    </tr>
                </thead>
                <tbody id="drivers-table">
                    <tr>
                        <td colspan="5" style="text-align:center;padding:40px;color:#94A3B8">
                            Chargement des chauffeurs...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const map = L.map('map').setView([-4.2634, 15.2429], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

const markers = {};

const driverIcon = L.divIcon({
    className: '',
    html: '<div style="background:#1DA1F2;border:3px solid #fff;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,.25);font-size:14px;">&#128663;</div>',
    iconSize: [36, 36],
    iconAnchor: [18, 18],
});

async function loadDrivers() {
    try {
        const res = await fetch('/api/admin/drivers?status=active', {
            headers: {
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + (localStorage.getItem('admin_token') || '')
            }
        });
        const data = await res.json();
        const drivers = data.data ?? data.drivers ?? [];

        document.getElementById('driver-count').textContent = drivers.length + ' en ligne';
        const tbody = document.getElementById('drivers-table');
        tbody.innerHTML = '';

        if (!drivers.length) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;color:#94A3B8">Aucun chauffeur actif</td></tr>';
            return;
        }

        drivers.forEach(function(driver) {
            const name  = (driver.first_name || '') + ' ' + (driver.last_name || '');
            const phone = driver.phone || '—';
            const lat   = driver.latitude  || driver.last_lat;
            const lng   = driver.longitude || driver.last_lng;
            const upd   = driver.location_updated_at || driver.updated_at || '';

            if (lat && lng) {
                if (markers[driver.id]) {
                    markers[driver.id].setLatLng([lat, lng]);
                } else {
                    markers[driver.id] = L.marker([lat, lng], { icon: driverIcon })
                        .addTo(map)
                        .bindPopup('<strong>' + name + '</strong><br>' + phone);
                }
            }

            const badge = driver.status === 'active'
                ? '<span class="badge badge-success">En ligne</span>'
                : '<span class="badge badge-gray">Hors ligne</span>';

            const init = name.charAt(0).toUpperCase();
            const lat4 = lat ? parseFloat(lat).toFixed(4) : null;
            const lng4 = lng ? parseFloat(lng).toFixed(4) : null;
            const pos  = lat4 && lng4 ? lat4 + ', ' + lng4 : '—';
            const dt   = upd ? new Date(upd).toLocaleString('fr-FR') : '—';

            tbody.innerHTML += '<tr>'
                + '<td><div style="display:flex;align-items:center;gap:10px"><div class="avatar">' + init + '</div>'
                + '<span style="font-weight:600;color:#0F172A">' + name + '</span></div></td>'
                + '<td style="color:#475569">' + phone + '</td>'
                + '<td>' + badge + '</td>'
                + '<td style="font-size:12px;color:#64748B">' + pos + '</td>'
                + '<td style="font-size:12px;color:#94A3B8">' + dt + '</td>'
                + '</tr>';
        });

    } catch(e) {
        console.error('Erreur:', e);
    }
}

loadDrivers();
setInterval(loadDrivers, 30000);
</script>
@endpush
