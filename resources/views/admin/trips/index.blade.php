@extends('admin.layouts.app')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">📋 Trajets & Courses</h1>
            <p class="text-sm text-gray-500 mt-1">
                Tous les trajets créés par les chauffeurs
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <span class="bg-blue-100 text-blue-700 text-xs font-bold px-3 py-2 rounded-lg">
                🚗 Total : {{ $stats['total'] ?? 0 }}
            </span>
            <span class="bg-yellow-100 text-yellow-700 text-xs font-bold px-3 py-2 rounded-lg">
                ⏳ En attente : {{ $stats['pending'] ?? 0 }}
            </span>
            <span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-3 py-2 rounded-lg">
                🚗 En cours : {{ $stats['in_progress'] ?? 0 }}
            </span>
            <span class="bg-green-100 text-green-700 text-xs font-bold px-3 py-2 rounded-lg">
                🏁 Terminés : {{ $stats['completed'] ?? 0 }}
            </span>
            <span class="bg-red-100 text-red-700 text-xs font-bold px-3 py-2 rounded-lg">
                ❌ Annulés : {{ $stats['cancelled'] ?? 0 }}
            </span>
        </div>
    </div>

    {{-- FILTRES --}}
    <form method="GET" action="{{ request()->url() }}"
          class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3 items-end">

        <div class="flex-1 min-w-[180px]">
            <label class="text-xs text-gray-500 font-semibold mb-1 block">Recherche</label>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Départ ou destination..."
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300">
        </div>

        <div class="min-w-[140px]">
            <label class="text-xs text-gray-500 font-semibold mb-1 block">Statut</label>
            <select name="status"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300">
                <option value="">Tous</option>
                <option value="active"      {{ request('status') === 'active'      ? 'selected' : '' }}>Actif</option>
                <option value="pending"     {{ request('status') === 'pending'     ? 'selected' : '' }}>En attente</option>
                <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>En cours</option>
                <option value="completed"   {{ request('status') === 'completed'   ? 'selected' : '' }}>Terminé</option>
                <option value="cancelled"   {{ request('status') === 'cancelled'   ? 'selected' : '' }}>Annulé</option>
            </select>
        </div>

        <div class="min-w-[140px]">
            <label class="text-xs text-gray-500 font-semibold mb-1 block">Du</label>
            <input type="date" name="from" value="{{ request('from') }}"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300">
        </div>

        <div class="min-w-[140px]">
            <label class="text-xs text-gray-500 font-semibold mb-1 block">Au</label>
            <input type="date" name="to" value="{{ request('to') }}"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300">
        </div>

        <button type="submit"
                class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold px-5 py-2 rounded-lg transition">
            🔍 Filtrer
        </button>

        @if(request()->anyFilled(['search','status','from','to']))
        <a href="{{ request()->url() }}"
           class="text-sm text-gray-400 hover:text-gray-600 px-3 py-2 rounded-lg border border-gray-200 transition">
            ✕ Reset
        </a>
        @endif
    </form>

    {{-- TABLEAU --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-700 text-sm uppercase">
                📋 Liste des trajets
                <span class="ml-2 bg-gray-100 text-gray-500 text-xs px-2 py-0.5 rounded-full">
                    {{ $trips->total() }} résultats
                </span>
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Chauffeur</th>
                        <th class="px-4 py-3 text-left">Itinéraire</th>
                        <th class="px-4 py-3 text-left">Date / Heure</th>
                        <th class="px-4 py-3 text-left">Places</th>
                        <th class="px-4 py-3 text-left">Prix</th>
                        <th class="px-4 py-3 text-left">Statut</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-50">
                    @forelse($trips as $trip)
                        @php
                            $driver = $trip->driver;
                            $statusColors = [
                                'active'      => 'bg-blue-100 text-blue-700',
                                'pending'     => 'bg-yellow-100 text-yellow-700',
                                'in_progress' => 'bg-indigo-100 text-indigo-700',
                                'completed'   => 'bg-green-100 text-green-700',
                                'cancelled'   => 'bg-red-100 text-red-700',
                            ];
                            $statusLabels = [
                                'active'      => 'Actif',
                                'pending'     => 'En attente',
                                'in_progress' => 'En cours',
                                'completed'   => 'Terminé',
                                'cancelled'   => 'Annulé',
                            ];
                            $color = $statusColors[$trip->status] ?? 'bg-gray-100 text-gray-600';
                            $label = $statusLabels[$trip->status] ?? $trip->status;
                        @endphp

                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-gray-400 font-mono text-xs">#{{ $trip->id }}</td>

                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-800 text-xs">
                                    {{ $driver?->first_name }} {{ $driver?->last_name ?? 'N/A' }}
                                </p>
                                <p class="text-gray-400 text-xs">{{ $driver?->phone ?? '-' }}</p>
                            </td>

                            <td class="px-4 py-3">
                                <p class="text-xs font-semibold text-gray-700">
                                    📍 {{ $trip->departure ?? $trip->pickup_address ?? '—' }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    🏁 {{ $trip->destination ?? $trip->dropoff_address ?? '—' }}
                                </p>
                            </td>

                            <td class="px-4 py-3 text-xs text-gray-600">
                                <p>{{ $trip->departure_date ?? '—' }}</p>
                                @if($trip->departure_time)
                                    <p class="text-gray-400">
                                        {{ \Carbon\Carbon::parse($trip->departure_time)->format('H:i') }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-xs text-gray-600 text-center">
                                {{ $trip->available_seats ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                <span class="text-orange-500 font-bold text-sm">
                                    {{ number_format($trip->price_per_seat ?? 0, 0, '.', ' ') }} FCFA
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <span class="text-xs font-bold px-2 py-1 rounded-lg {{ $color }}">
                                    {{ $label }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <button onclick="openTripModal({{ $trip->id }})"
                                        class="bg-blue-500 hover:bg-blue-600 text-white text-xs px-3 py-1.5 rounded-lg transition">
                                    Voir
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center text-gray-400">
                                <div class="flex flex-col items-center gap-3">
                                    <span class="text-4xl">🚗</span>
                                    <p class="font-semibold">Aucun trajet trouvé</p>
                                    @if(request()->anyFilled(['search','status','from','to']))
                                        <a href="{{ request()->url() }}"
                                           class="text-orange-500 text-sm hover:underline">
                                            Réinitialiser les filtres
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        <div class="px-6 py-4 border-t border-gray-100">
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
            <h2 class="font-bold text-gray-800 text-lg">📋 Détail du trajet</h2>
            <button onclick="closeTripModal()"
                    class="text-gray-400 hover:text-gray-600 text-xl font-bold transition">✕</button>
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
                        <h3 class="font-bold text-gray-700 text-xs uppercase mb-3">🗺️ Itinéraire</h3>
                        <div class="flex items-start gap-2">
                            <span class="text-blue-500 mt-0.5">📍</span>
                            <div>
                                <p class="text-xs text-gray-400">Départ</p>
                                <p class="font-semibold text-gray-800">${trip.departure ?? trip.pickup_address ?? '—'}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="text-orange-500 mt-0.5">🏁</span>
                            <div>
                                <p class="text-xs text-gray-400">Destination</p>
                                <p class="font-semibold text-gray-800">${trip.destination ?? trip.dropoff_address ?? '—'}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-xl p-4 space-y-2">
                        <h3 class="font-bold text-gray-700 text-xs uppercase mb-3">📅 Horaire & Prix</h3>
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
                        <h3 class="font-bold text-gray-700 text-xs uppercase mb-3">👤 Chauffeur</h3>
                        ${driver ? `
                        <p class="text-sm font-semibold text-gray-800">
                            ${driver.first_name ?? ''} ${driver.last_name ?? ''}
                        </p>
                        <p class="text-xs text-gray-400">${driver.phone ?? '—'}</p>
                        <p class="text-xs text-gray-400">${driver.email ?? ''}</p>
                        ` : '<p class="text-sm text-gray-400">Chauffeur non trouvé</p>'}
                    </div>

                    <div class="bg-gray-50 rounded-xl p-4">
                        <h3 class="font-bold text-gray-700 text-xs uppercase mb-3">📊 Statut</h3>
                        <span class="text-xs font-bold px-3 py-1.5 rounded-lg ${color}">
                            ${trip.status ?? '—'}
                        </span>
                        ${trip.distance_km ? `
                        <p class="text-sm mt-3"><span class="text-gray-400">Distance :</span>
                            <span class="font-semibold">${trip.distance_km} km</span></p>` : ''}
                    </div>

                </div>`;
        })
        .catch(() => {
            document.getElementById('modal-content').innerHTML =
                '<p class="text-red-500 text-center py-8">❌ Erreur lors du chargement.</p>';
        });
}

function closeTripModal() {
    document.getElementById('trip-modal').classList.add('hidden');
}

function closeTripModalOutside(e) {
    if (e.target === document.getElementById('trip-modal')) {
        closeTripModal();
    }
}
</script>
@endpush