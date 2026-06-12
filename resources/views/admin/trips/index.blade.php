@extends('admin.layouts.app')
@section('title','Trajets & Courses')

@section('content')
<div style="display:flex;flex-direction:column;gap:16px">

    <div class="page-header">
        <h1 class="page-title">Trajets & Courses</h1>
        <p class="page-sub">Tous les trajets créés par les chauffeurs</p>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px">
        <div class="stat-card">
            <div class="stat-icon" style="background:#EFF8FF">
                <svg width="16" height="16" fill="none" stroke="#1DA1F2" stroke-width="2" viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            </div>
            <div class="stat-val">{{ $stats['total'] ?? 0 }}</div>
            <div class="stat-lbl">Total</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#FFFBEB">
                <svg width="16" height="16" fill="none" stroke="#F59E0B" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="stat-val">{{ $stats['pending'] ?? 0 }}</div>
            <div class="stat-lbl">En attente</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#EEF2FF">
                <svg width="16" height="16" fill="none" stroke="#6366F1" stroke-width="2" viewBox="0 0 24 24"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
            </div>
            <div class="stat-val">{{ $stats['in_progress'] ?? 0 }}</div>
            <div class="stat-lbl">En cours</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#ECFDF5">
                <svg width="16" height="16" fill="none" stroke="#10B981" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="stat-val">{{ $stats['completed'] ?? 0 }}</div>
            <div class="stat-lbl">Terminés</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#FEF2F2">
                <svg width="16" height="16" fill="none" stroke="#EF4444" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </div>
            <div class="stat-val">{{ $stats['cancelled'] ?? 0 }}</div>
            <div class="stat-lbl">Annulés</div>
        </div>
    </div>

    <form method="GET" action="{{ request()->url() }}" class="filter-bar" style="flex-wrap:wrap;gap:10px">
        <div style="display:flex;flex-direction:column;gap:4px;flex:1;min-width:180px">
            <label class="ttg-label">Recherche</label>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Départ ou destination..." class="ttg-input">
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;min-width:140px">
            <label class="ttg-label">Statut</label>
            <select name="status" class="ttg-select">
                <option value="">Tous</option>
                <option value="active"      {{ request('status') === 'active'      ? 'selected' : '' }}>Actif</option>
                <option value="pending"     {{ request('status') === 'pending'     ? 'selected' : '' }}>En attente</option>
                <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>En cours</option>
                <option value="completed"   {{ request('status') === 'completed'   ? 'selected' : '' }}>Terminé</option>
                <option value="cancelled"   {{ request('status') === 'cancelled'   ? 'selected' : '' }}>Annulé</option>
            </select>
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;min-width:140px">
            <label class="ttg-label">Du</label>
            <input type="date" name="from" value="{{ request('from') }}" class="ttg-input">
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;min-width:140px">
            <label class="ttg-label">Au</label>
            <input type="date" name="to" value="{{ request('to') }}" class="ttg-input">
        </div>
        <button type="submit" class="btn btn-primary" style="align-self:flex-end">Filtrer</button>
        @if(request()->anyFilled(['search','status','from','to']))
        <a href="{{ request()->url() }}" class="btn btn-secondary" style="align-self:flex-end">Reset</a>
        @endif
    </form>

    <div class="panel">
        <div class="panel-header">
            Liste des trajets
            <span style="margin-left:8px;background:#f1f5f9;color:#64748b;font-size:11px;padding:2px 8px;border-radius:20px">
                {{ $trips->total() }} résultats
            </span>
        </div>
        <div style="overflow-x:auto">
            <table class="ttg-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Chauffeur</th>
                        <th>Itinéraire</th>
                        <th>Date / Heure</th>
                        <th>Places</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($trips as $trip)
                        @php
                            $driver = $trip->driver;
                            $statusBadge = [
                                'active'      => 'badge-info',
                                'pending'     => 'badge-warning',
                                'in_progress' => 'badge badge-primary',
                                'completed'   => 'badge-success',
                                'cancelled'   => 'badge-danger',
                            ][$trip->status] ?? 'badge';
                            $statusLabel = [
                                'active'      => 'Actif',
                                'pending'     => 'En attente',
                                'in_progress' => 'En cours',
                                'completed'   => 'Terminé',
                                'cancelled'   => 'Annulé',
                            ][$trip->status] ?? $trip->status;
                        @endphp
                        <tr>
                            <td style="font-family:monospace;color:#94a3b8;font-size:11px">#{{ $trip->id }}</td>
                            <td>
                                <div style="font-weight:600;font-size:13px">{{ $driver?->first_name }} {{ $driver?->last_name ?? 'N/A' }}</div>
                                <div style="color:#94a3b8;font-size:11px">{{ $driver?->phone ?? '—' }}</div>
                            </td>
                            <td>
                                <div style="font-weight:600;font-size:12px">{{ $trip->departure ?? $trip->pickup_address ?? '—' }}</div>
                                <div style="color:#64748b;font-size:12px">{{ $trip->destination ?? $trip->dropoff_address ?? '—' }}</div>
                            </td>
                            <td style="font-size:12px">
                                {{ $trip->departure_date ?? '—' }}
                                @if($trip->departure_time && strlen(trim($trip->departure_time)) > 2)
                                    @php
                                        try { $t = \Carbon\Carbon::parse($trip->departure_time)->format('H:i'); }
                                        catch(\Exception $e) { $t = substr($trip->departure_time, 0, 5); }
                                    @endphp
                                    <div style="color:#94a3b8">{{ $t }}</div>
                                @endif
                            </td>
                            <td style="text-align:center">{{ $trip->available_seats ?? '—' }}</td>
                            <td>
                                <span style="color:#f97316;font-weight:700">{{ number_format($trip->price_per_seat ?? 0, 0, '.', ' ') }} FCFA</span>
                            </td>
                            <td><span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span></td>
                            <td>
                                <button onclick="openTripModal({{ $trip->id }})" class="btn btn-primary" style="font-size:12px;padding:4px 12px">
                                    Voir
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;padding:48px;color:#94a3b8">
                                <div style="font-size:40px;margin-bottom:8px"></div>
                                <div style="font-weight:600">Aucun trajet trouvé</div>
                                @if(request()->anyFilled(['search','status','from','to']))
                                    <a href="{{ request()->url() }}" style="color:#1DA1F2;font-size:13px">Réinitialiser les filtres</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:16px 20px;border-top:1px solid #f1f5f9">
            {{ $trips->withQueryString()->links() }}
        </div>
    </div>
</div>


{{-- MODAL DÉTAIL --}}
<div id="trip-modal"
     style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center"
     onclick="closeTripModalOutside(event)">

    <div style="background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.3);width:100%;max-width:680px;margin:16px;padding:24px;max-height:90vh;overflow-y:auto">

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <h2 style="font-size:16px;font-weight:700;color:#1e293b;margin:0">Détail du trajet</h2>
            <button onclick="closeTripModal()"
                    style="background:none;border:none;font-size:22px;color:#94a3b8;cursor:pointer;line-height:1">&times;</button>
        </div>

        <div id="modal-content" style="color:#64748b;font-size:14px">
            <div style="display:flex;justify-content:center;padding:32px">
                <div style="width:32px;height:32px;border:3px solid #f97316;border-top-color:transparent;border-radius:50%"></div>
            </div>
        </div>

    </div>
</div>

@endsection


@push('scripts')
<script>
function openTripModal(id) {
    document.getElementById('trip-modal').style.display = 'flex';
    document.getElementById('modal-content').innerHTML =
        '<div style="display:flex;justify-content:center;padding:32px">' +
        '<div style="width:32px;height:32px;border:3px solid #f97316;border-top-color:transparent;border-radius:50%;animation:spin 0.8s linear infinite"></div>' +
        '</div>';

    fetch('/admin/trips/' + id + '/detail')
        .then(function(res) { return res.json(); })
        .then(function(data) {
            var trip   = data.data ?? data;
            var driver = trip.driver;

            var statusMap = {
                active:      { label: 'Actif',      color: '#1DA1F2', bg: '#EFF8FF' },
                pending:     { label: 'En attente', color: '#F59E0B', bg: '#FFFBEB' },
                in_progress: { label: 'En cours',   color: '#6366F1', bg: '#EEF2FF' },
                completed:   { label: 'Terminé',    color: '#10B981', bg: '#ECFDF5' },
                cancelled:   { label: 'Annulé',     color: '#EF4444', bg: '#FEF2F2' },
            };
            var s = statusMap[trip.status] ?? { label: trip.status, color: '#64748b', bg: '#f1f5f9' };

            var driverHtml = driver
                ? '<div style="font-weight:600;font-size:14px">' + (driver.first_name ?? '') + ' ' + (driver.last_name ?? '') + '</div>' +
                  '<div style="color:#64748b;font-size:12px">' + (driver.phone ?? '—') + '</div>' +
                  '<div style="color:#94a3b8;font-size:12px">' + (driver.email ?? '') + '</div>'
                : '<span style="color:#94a3b8">—</span>';

            var bookings = trip.bookings ?? [];
            var bookingsHtml = '';
            if (bookings.length > 0) {
                bookings.forEach(function(b) {
                    var u = b.user ?? {};
                    bookingsHtml +=
                        '<div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f1f5f9">' +
                        '<div>' +
                        '<div style="font-size:13px;font-weight:600">' + (u.first_name ?? '') + ' ' + (u.last_name ?? '') + '</div>' +
                        '<div style="font-size:11px;color:#94a3b8">' + (u.phone ?? '—') + '</div>' +
                        '</div>' +
                        '<span style="font-size:12px;padding:2px 8px;border-radius:12px;background:#ECFDF5;color:#10B981">' + (b.status ?? '') + '</span>' +
                        '</div>';
                });
            } else {
                bookingsHtml = '<div style="color:#94a3b8;font-size:13px">Aucune réservation</div>';
            }

            document.getElementById('modal-content').innerHTML =
                '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">' +

                /* Itinéraire */
                '<div style="background:#f8fafc;border-radius:12px;padding:16px">' +
                '<div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:12px">Itinéraire</div>' +
                '<div style="margin-bottom:8px"><div style="font-size:11px;color:#94a3b8">Départ</div>' +
                '<div style="font-weight:600;font-size:13px">' + (trip.departure ?? trip.pickup_address ?? '—') + '</div></div>' +
                '<div><div style="font-size:11px;color:#94a3b8">Destination</div>' +
                '<div style="font-weight:600;font-size:13px">' + (trip.destination ?? trip.dropoff_address ?? '—') + '</div></div>' +
                '</div>' +

                /* Horaire & Prix */
                '<div style="background:#f8fafc;border-radius:12px;padding:16px">' +
                '<div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:12px">Horaire & Prix</div>' +
                '<div style="font-size:13px;margin-bottom:6px"><span style="color:#94a3b8">Date : </span><strong>' + (trip.departure_date ?? '—') + '</strong></div>' +
                '<div style="font-size:13px;margin-bottom:6px"><span style="color:#94a3b8">Heure : </span><strong>' + (trip.departure_time ?? '—') + '</strong></div>' +
                '<div style="font-size:13px;margin-bottom:6px"><span style="color:#94a3b8">Prix/place : </span><strong style="color:#f97316">' + Number(trip.price_per_seat ?? 0).toLocaleString() + ' FCFA</strong></div>' +
                '<div style="font-size:13px;margin-bottom:6px"><span style="color:#94a3b8">Places dispo : </span><strong>' + (trip.available_seats ?? '—') + '</strong></div>' +
                '<div style="margin-top:8px"><span style="padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:' + s.bg + ';color:' + s.color + '">' + s.label + '</span></div>' +
                '</div>' +

                /* Chauffeur */
                '<div style="background:#f8fafc;border-radius:12px;padding:16px">' +
                '<div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:12px">Chauffeur</div>' +
                driverHtml +
                '</div>' +

                /* Réservations */
                '<div style="background:#f8fafc;border-radius:12px;padding:16px">' +
                '<div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:12px">Réservations (' + bookings.length + ')</div>' +
                bookingsHtml +
                '</div>' +

                '</div>';
        })
        .catch(function(err) {
            document.getElementById('modal-content').innerHTML =
                '<div style="text-align:center;padding:32px;color:#EF4444">Erreur lors du chargement</div>';
        });
}

function closeTripModal() {
    document.getElementById('trip-modal').style.display = 'none';
}

function closeTripModalOutside(event) {
    if (event.target === document.getElementById('trip-modal')) {
        closeTripModal();
    }
}
</script>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>
@endpush
