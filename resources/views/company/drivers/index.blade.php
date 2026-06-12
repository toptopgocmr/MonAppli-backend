@extends('company.layouts.app')
@section('title', 'Chauffeurs')
@section('page-title', 'Mes Chauffeurs')

@section('content')

<div class="bg-white rounded-2xl shadow-sm border border-gray-100">

    <!-- Filters -->
    <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap gap-3 items-center justify-between">
        <form method="GET" class="flex gap-3 flex-wrap">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher un chauffeur..."
                   class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-64">
            <select name="status" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Tous les statuts</option>
                <option value="approved"  {{ request('status') === 'approved'  ? 'selected' : '' }}>Approuvés</option>
                <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>En attente</option>
                <option value="rejected"  {{ request('status') === 'rejected'  ? 'selected' : '' }}>Rejetés</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspendus</option>
            </select>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-blue-700 transition">
                Filtrer
            </button>
        </form>
        <p class="text-sm text-gray-400">{{ $drivers->total() }} chauffeur(s)</p>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-6 py-3 text-left">Chauffeur</th>
                    <th class="px-6 py-3 text-left">Téléphone</th>
                    <th class="px-6 py-3 text-left">Véhicule</th>
                    <th class="px-6 py-3 text-left">Statut KYC</th>
                    <th class="px-6 py-3 text-left">Présence</th>
                    <th class="px-6 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($drivers as $driver)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            @if($driver->profile_photo)
                                <img src="{{ $driver->profile_photo }}" class="w-9 h-9 rounded-full object-cover">
                            @else
                                <div class="w-9 h-9 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
                                    {{ strtoupper(substr($driver->first_name, 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $driver->first_name }} {{ $driver->last_name }}</p>
                                <p class="text-xs text-gray-400">{{ $driver->vehicle_city ?? '—' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $driver->phone }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $driver->vehicle_brand ?? '—' }} {{ $driver->vehicle_model ?? '' }}
                        @if($driver->vehicle_plate)
                            <span class="text-xs bg-gray-100 px-2 py-0.5 rounded ml-1">{{ $driver->vehicle_plate }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $colors = ['approved'=>'green','pending'=>'yellow','rejected'=>'red','suspended'=>'gray'];
                            $labels = ['approved'=>'Approuvé','pending'=>'En attente','rejected'=>'Rejeté','suspended'=>'Suspendu'];
                            $c = $colors[$driver->status] ?? 'gray';
                            $l = $labels[$driver->status] ?? $driver->status;
                        @endphp
                        <span class="text-xs px-2 py-1 rounded-full font-medium bg-{{ $c }}-100 text-{{ $c }}-700">{{ $l }}</span>
                    </td>
                    <td class="px-6 py-4">
                        @if($driver->driver_status === 'online')
                            <span class="flex items-center gap-1 text-xs text-green-600 font-medium">
                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span> En ligne
                            </span>
                        @else
                            <span class="text-xs text-gray-400">Hors ligne</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex gap-2">
                            <a href="{{ route('company.drivers.show', $driver->id) }}"
                               class="text-xs bg-blue-50 text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-100 transition font-medium">
                                Voir
                            </a>
                            <form method="POST" action="{{ route('company.drivers.remove', $driver->id) }}"
                                  onsubmit="return confirm('Retirer ce chauffeur de votre société ?')">
                                @csrf
                                <button type="submit"
                                        class="text-xs bg-red-50 text-red-600 px-3 py-1.5 rounded-lg hover:bg-red-100 transition font-medium">
                                    Retirer
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">Aucun chauffeur dans votre société</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($drivers->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $drivers->links() }}
    </div>
    @endif

</div>
@endsection
