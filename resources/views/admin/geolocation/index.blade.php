{{-- resources/views/admin/geolocation/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Géolocalisation')

@section('content')
<div class="container-fluid py-4">

  <div class="row mb-4">
    <div class="col">
      <h4 class="fw-bold mb-0">
        <i class="fas fa-map-marked-alt text-primary me-2"></i>
        Géolocalisation des chauffeurs
      </h4>
      <p class="text-muted small mt-1">Suivez la position en temps réel des chauffeurs actifs</p>
    </div>
  </div>

  {{-- Carte --}}
  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
      <div id="map" style="height: 600px; width: 100%; border-radius: 1rem;"></div>
    </div>
  </div>

  {{-- Liste chauffeurs actifs --}}
  <div class="row mt-4">
    <div class="col-12">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 py-3">
          <h6 class="fw-bold mb-0">
            <i class="fas fa-car text-success me-2"></i>
            Chauffeurs en ligne
          </h6>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="ps-4">Chauffeur</th>
                  <th>Téléphone</th>
                  <th>Statut</th>
                  <th>Dernière position</th>
                  <th>Mise à jour</th>
                </tr>
              </thead>
              <tbody id="drivers-table">
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">
                    <i class="fas fa-spinner fa-spin me-2"></i>
                    Chargement...
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
{{-- Leaflet.js (carte open source, pas besoin de clé API) --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
  // Initialiser la carte centrée sur Brazzaville
  const map = L.map('map').setView([-4.2634, 15.2429], 12);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(map);

  const markers = {};

  // Icône chauffeur personnalisée
  const driverIcon = L.divIcon({
    className: '',
    html: `<div style="
      background: #1A73E8;
      border: 3px solid white;
      border-radius: 50%;
      width: 36px; height: 36px;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
      font-size: 16px;
    ">🚗</div>`,
    iconSize: [36, 36],
    iconAnchor: [18, 18],
  });

  // Charger les chauffeurs actifs
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
        tbody.innerHTML = `
          <tr>
            <td colspan="5" class="text-center text-muted py-4">
              Aucun chauffeur actif pour le moment
            </td>
          </tr>`;
        return;
      }

      drivers.forEach(driver => {
        const name  = (driver.first_name ?? '') + ' ' + (driver.last_name ?? '');
        const phone = driver.phone ?? '—';
        const lat   = driver.latitude  ?? driver.last_lat;
        const lng   = driver.longitude ?? driver.last_lng;
        const updatedAt = driver.location_updated_at ?? driver.updated_at ?? '';

        // Ajouter marqueur sur la carte si position disponible
        if (lat && lng) {
          if (markers[driver.id]) {
            markers[driver.id].setLatLng([lat, lng]);
          } else {
            markers[driver.id] = L.marker([lat, lng], { icon: driverIcon })
              .addTo(map)
              .bindPopup(`<strong>${name}</strong><br>${phone}`);
          }
        }

        // Ajouter ligne dans le tableau
        const statusBadge = driver.status === 'active'
          ? '<span class="badge bg-success">En ligne</span>'
          : '<span class="badge bg-secondary">Hors ligne</span>';

        tbody.innerHTML += `
          <tr>
            <td class="ps-4">
              <div class="d-flex align-items-center gap-2">
                <div class="avatar rounded-circle bg-primary bg-opacity-10 d-flex align-items-center
                  justify-content-center" style="width:36px;height:36px;font-weight:700;color:#1A73E8">
                  ${name.charAt(0).toUpperCase()}
                </div>
                <span class="fw-semibold">${name}</span>
              </div>
            </td>
            <td>${phone}</td>
            <td>${statusBadge}</td>
            <td>${lat && lng ? `${parseFloat(lat).toFixed(4)}, ${parseFloat(lng).toFixed(4)}` : '—'}</td>
            <td class="text-muted small">${updatedAt ? new Date(updatedAt).toLocaleString('fr-FR') : '—'}</td>
          </tr>`;
      });

    } catch (e) {
      console.error('Erreur chargement chauffeurs:', e);
    }
  }

  loadDrivers();
  // Rafraîchir toutes les 30 secondes
  setInterval(loadDrivers, 30000);
</script>
@endpush