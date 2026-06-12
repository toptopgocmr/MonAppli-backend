@extends('admin.layouts.app')
@section('title', 'Géolocalisation')

@section('content')
<div style="display:flex;flex-direction:column;gap:16px">

    <div>
        <h1 class="page-title">🗺️ Géolocalisation</h1>
        <p class="page-sub">Position en temps réel des chauffeurs actifs — actualisé toutes les 30 secondes</p>
    </div>

    {{-- Carte --}}
    <div class="panel" style="padding:0;overflow:hidden">
        <div class="panel-header">🗺️ Carte en temps réel</div>
        <div id="map" style="height:520px;width:100%;z-index:1"></div>
    </div>

    {{-- Table chauffeurs --}}
    <div class="panel" style="padding:0;overflow:hidden">
        <div class="panel-header">🚗 Chauffeurs en ligne</div>
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
                        <td colspan="5" style="text-align:center;padding:32px;color:#94a3b8">
                            ⏳ Chargement des chauffeurs...
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
    html: `<div style="background:#1DA1F2;border:3px solid white;border-radius:50%;
      width:36px;height:36px;display:flex;align-items:center;justify-content:center;
      box-shadow:0 2px 8px rgba(0,0,0,0.3);font-size:16px;">🚗</div>`,
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

      const tbody = document.getElementById('drivers-table');
      tbody.innerHTML = '';

      if (drivers.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:32px;color:#94a3b8">Aucun chauffeur actif pour le moment</td></tr>`;
        return;
      }

      drivers.forEach(driver => {
        const name  = (driver.first_name ?? '') + ' ' + (driver.last_name ?? '');
        const phone = driver.phone ?? '—';
        const lat   = driver.latitude  ?? driver.last_lat;
        const lng   = driver.longitude ?? driver.last_lng;
        const updatedAt = driver.location_updated_at ?? driver.updated_at ?? '';

        if (lat && lng) {
          if (markers[driver.id]) {
            markers[driver.id].setLatLng([lat, lng]);
          } else {
            markers[driver.id] = L.marker([lat, lng], { icon: driverIcon })
              .addTo(map)
              .bindPopup(`<strong>${name}</strong><br>${phone}`);
          }
        }

        const badge = driver.status === 'active'
          ? `<span class="badge badge-success">🟢 En ligne</span>`
          : `<span class="badge badge-secondary">⚪ Hors ligne</span>`;

        tbody.innerHTML += `
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <div class="avatar">${name.charAt(0).toUpperCase()}</div>
                <span style="font-weight:600">${name}</span>
              </div>
            </td>
            <td>${phone}</td>
            <td>${badge}</td>
            <td style="font-size:12px;color:#64748b">${lat && lng ? parseFloat(lat).toFixed(4)+', '+parseFloat(lng).toFixed(4) : '—'}</td>
            <td style="font-size:12px;color:#94a3b8">${updatedAt ? new Date(updatedAt).toLocaleString('fr-FR') : '—'}</td>
          </tr>`;
      });

    } catch (e) {
      console.error('Erreur chargement chauffeurs:', e);
    }
  }

  loadDrivers();
  setInterval(loadDrivers, 30000);
</script>
@endpush