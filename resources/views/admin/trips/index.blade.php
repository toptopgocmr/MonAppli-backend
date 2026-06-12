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
                                @if($trip->departure_time)
                                    <div style="color:#94a3b8">{{ \Carbon\Carbon::parse($trip->departure_time)->format('H:i') }}</div>
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
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 hidden"
     onclick="closeTripModalOutside(event)">

    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl mx-4 p-6 max-h-[90vh] overflow-y-auto">

        <div class="flex justify-between items-center mb-6">
            <h2 class="font-bold text-gray-800 text-lg">Détail du trajet</h2>
            <button onclick="closeTripModal()"
                    class="text-gray-400 hover:text-gray-600 text-xl font-bold transition">× </button>
        </div>

        <div id="modal-content" class="text-gray-500 text-sm">
            <div class="flex justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-500"></div>
            </div>
        </div>

    </div>
</div>

@endsection


@push('scripts')
<script>
function openTripModal(id) {
    document.getElementById('trip-modal').classList.remove('hidden');
    document.getElementById('modal-content').innerHTML = `
        <div class="flex justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-500"></div>
        </div>`;

    fetch(`/admin/trips/${id}/detail`)
        .then(res => res.json())
        .then(data => {
            const trip   = data.data ?? data;
            const driver = trip.driver;

            const statusColors = {
                active:      'bg-blue-100 text-blue-700',
                pending:     'bg-yellow-100 text-yellow-700',
                in_progress: 'bg-indigo-100 text-indigo-700',
                completed:   'bg-green-100 text-green-700',
                cancelled:   'bg-red-100 text-red-700',
            };
            const color = statusColors[trip.status] ?? 'bg-gray-100 text-gray-600';

            document.getElementById('modal-content').innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div class="bg-gray-50 rounded-xl p-4 space-y-2">
                        <h3 class="font-bold text-gray-700 text-xs uppercase mb-3">Itinéraire</h3>
                        <div class="flex items-start gap-2">
                            <span class="text-blue-500 mt-0.5"></span>
                            <div>
                                <p class="text-xs text-gray-400">Départ</p>
                                <p class="font-semibold text-gray-800">${trip.departure ?? trip.pickup_address ?? '—'}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="text-orange-500 mt-0.5"></span>
                            <div>
                                <p class="text-xs text-gray-400">Destination</p>
                                <p class="font-semibold text-gray-800">${trip.destination ?? trip.dropoff_address ?? '—'}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-xl p-4 space-y-2">
                        <h3 class="font-bold text-gray-700 text-xs uppercase mb-3">Horaire & Prix</h3>
                        <p class="text-sm"><span class="text-gray-400">Date :</span>
                            <span class="font-semibold">${trip.departure_date ?? '—'}</span></p>
                        <p class="text-sm"><span class="text-gray-400">Heure :</span>
                            <span class="font-semibold">${trip.departure_time ?? '—'}</span></p>
                        <p class="text-sm"><span class="text-gray-400">Prix/place :</span>
                            <span class="font-bold text-orange-500">${Number(trip.price_per_seat ?? 0).toLocaleString()} FCFA</span></p>
                        <p class="text-sm"><span class="text-gray-400">Places dispo :</span>
                            <span class="font-semibold">${trip.available_seats ?? '—'}</span></p>
                    </div>

                    <div class="bg-gray-50 rounded-xl p-4 space-y-2">
                        <h3 class="font-bold text-gray-700 text-xs uppercase mb-3">Chauffeur</h3>
                        ${driver ? `
                        <p class="text-sm font-semibold text-gray-800">
                            ${driver.first_name ?? ''} ${driver.last_name ?? ''}
                        </p>
                        <p class="text-xs text-gray-400">${driver.pho
</script>
@endpush
