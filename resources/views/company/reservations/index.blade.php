@extends('company.layouts.app')
@section('title', 'Réservations')
@section('page-title', 'Réservations & Courses')

@section('content')

<div class="bg-white rounded-2xl shadow-sm border border-gray-100">

    <!-- Filters -->
    <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap gap-3 items-center justify-between">
        <form method="GET" class="flex flex-wrap gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Chauffeur, adresse..."
                   class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-56">
            <select name="status" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Tous les statuts</option>
                @foreach(['pending'=>'En attente','confirmed'=>'Confirmée','ongoing'=>'En cours','completed'=>'Terminée','cancelled'=>'Annulée'] as $val => $label)
                    <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-blue-700 transition">
                Filtrer
            </button>
        </form>
        <p class="text-sm text-gray-400">{{ $trips->total() }} course(s)</p>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-6 py-3 text-left">#</th>
                    <th class="px-6 py-3 text-left">Chauffeur</th>
                    <th class="px-6 py-3 text-left">Départ → Arrivée</th>
                    <th class="px-6 py-3 text-left">Montant</th>
                    <th class="px-6 py-3 text-left">Statut</th>
                    <th class="px-6 py-3 text-left">Date</th>
                    <th class="px-6 py-3 text-left">Détails</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($trips as $trip)
                @php
                    $statusColors = [
                        'pending'   => ['bg-yellow-100','text-yellow-700','En attente'],
                        'confirmed' => ['bg-blue-100','text-blue-700','Confirmée'],
                        'ongoing'   => ['bg-indigo-100','text-indigo-700','En cours'],
                        'completed' => ['bg-green-100','text-green-700','Terminée'],
                        'cancelled' => ['bg-red-100','text-red-700','Annulée'],
                    ];
                    $sc = $statusColors[$trip->status] ?? ['bg-gray-100','text-gray-700',$trip->status];
                @endphp
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-sm font-mono text-gray-400">#{{ $trip->id }}</td>
                    <td class="px-6 py-4">
                        @if($trip->driver)
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 bg-blue-600 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                {{ strtoupper(substr($trip->driver->first_name, 0, 1)) }}
                            </div>
                            <p class="text-sm font-medium text-gray-800">{{ $trip->driver->first_name }} {{ $trip->driver->last_name }}</p>
                        </div>
                        @else
                            <span class="text-gray-400 text-sm">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-sm text-gray-700 truncate max-w-xs">
                            {{ Str::limit($trip->departure_address ?? '—', 25) }}
                            <span class="text-gray-400 mx-1">→</span>
                            {{ Str::limit($trip->arrival_address ?? '—', 25) }}
                        </p>
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-sm font-semibold text-blue-700">{{ number_format($trip->price ?? 0, 0, ',', ' ') }} FCFA</p>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-xs px-2 py-1 rounded-full font-medium {{ $sc[0] }} {{ $sc[1] }}">{{ $sc[2] }}</span>
                    </td>
                    <td class="px-6 py-4 text-xs text-gray-400">{{ $trip->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-4">
                        <a href="{{ route('company.reservations.show', $trip->id) }}"
                           class="text-xs bg-blue-50 text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-100 transition font-medium">
                            Voir
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">Aucune course trouvée</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($trips->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $trips->links() }}
    </div>
    @endif

</div>
@endsection
