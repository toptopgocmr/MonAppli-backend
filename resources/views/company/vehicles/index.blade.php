@extends('company.layouts.app')
@section('title', 'Flotte Véhicules')
@section('page-title', 'Flotte Véhicules')

@section('content')

<div class="bg-white rounded-2xl shadow-sm border border-gray-100">

    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <p class="text-sm text-gray-400">{{ $drivers->total() }} véhicule(s) dans votre flotte</p>
        <form method="GET" class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher..."
                   class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-56">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-blue-700 transition">
                Filtrer
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-6 py-3 text-left">Chauffeur</th>
                    <th class="px-6 py-3 text-left">Marque / Modèle</th>
                    <th class="px-6 py-3 text-left">Plaque</th>
                    <th class="px-6 py-3 text-left">Couleur</th>
                    <th class="px-6 py-3 text-left">Type</th>
                    <th class="px-6 py-3 text-left">Ville</th>
                    <th class="px-6 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($drivers as $driver)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            @if($driver->profile_photo)
                                <img src="{{ $driver->profile_photo }}" class="w-8 h-8 rounded-full object-cover">
                            @else
                                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
                                    {{ strtoupper(substr($driver->first_name, 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $driver->first_name }} {{ $driver->last_name }}</p>
                                <p class="text-xs text-gray-400">{{ $driver->phone }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">
                        {{ $driver->vehicle_brand ?? '—' }} {{ $driver->vehicle_model ?? '' }}
                    </td>
                    <td class="px-6 py-4">
                        @if($driver->vehicle_plate)
                            <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-lg text-sm font-mono font-medium">{{ $driver->vehicle_plate }}</span>
                        @else
                            <span class="text-gray-400 text-sm">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $driver->vehicle_color ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $driver->vehicle_type ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $driver->vehicle_city ?? '—' }}</td>
                    <td class="px-6 py-4">
                        <a href="{{ route('company.vehicles.edit', $driver->id) }}"
                           class="text-xs bg-blue-50 text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-100 transition font-medium">
                            Modifier
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">Aucun véhicule dans votre flotte</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($drivers->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $drivers->links() }}
    </div>
    @endif

</div>
@endsection
