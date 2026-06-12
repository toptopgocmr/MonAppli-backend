@extends('company.layouts.app')
@section('title', 'Détail Course #' . $trip->id)
@section('page-title', 'Détail de la Course')

@section('content')
<div class="max-w-3xl">

    <a href="{{ route('company.reservations.index') }}" class="text-blue-600 text-sm hover:underline flex items-center gap-1 mb-6">
        ← Retour aux réservations
    </a>

    @php
        $statusColors = [
            'pending'   => ['bg-yellow-100','text-yellow-700','En attente'],
            'accepted' => ['bg-blue-100','text-blue-700','Acceptée'],
            'in_progress' => ['bg-indigo-100','text-indigo-700','En cours'],
            'completed' => ['bg-green-100','text-green-700','Terminée'],
            'cancelled' => ['bg-red-100','text-red-700','Annulée'],
        ];
        $sc = $statusColors[$trip->status] ?? ['bg-gray-100','text-gray-700', $trip->status];
    @endphp

    <!-- Header card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Course #{{ $trip->id }}</h2>
                <p class="text-xs text-gray-400 mt-0.5">{{ $trip->created_at->format('d/m/Y à H:i') }}</p>
            </div>
            <span class="text-sm px-4 py-2 rounded-full font-semibold {{ $sc[0] }} {{ $sc[1] }}">{{ $sc[2] }}</span>
        </div>

        <!-- Route -->
        <div class="bg-gray-50 rounded-xl p-4 space-y-3">
            <div class="flex items-start gap-3">
                <div class="w-3 h-3 bg-green-500 rounded-full mt-1 flex-shrink-0"></div>
                <div>
                    <p class="text-xs text-gray-400 uppercase">Départ</p>
                    <p class="text-sm font-medium text-gray-800">{{ $trip->departure ?? '—' }}</p>
                </div>
            </div>
            <div class="ml-1.5 w-0.5 h-4 bg-gray-300"></div>
            <div class="flex items-start gap-3">
                <div class="w-3 h-3 bg-red-500 rounded-full mt-1 flex-shrink-0"></div>
                <div>
                    <p class="text-xs text-gray-400 uppercase">Arrivée</p>
                    <p class="text-sm font-medium text-gray-800">{{ $trip->destination ?? '—' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-6">

        <!-- Chauffeur -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-700 mb-4 text-sm uppercase tracking-wide">Chauffeur</h3>
            @if($trip->driver)
            <div class="flex items-center gap-3">
                @if($trip->driver->profile_photo)
                    <img src="{{ $trip->driver->profile_photo }}" class="w-12 h-12 rounded-full object-cover">
                @else
                    <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                        {{ strtoupper(substr($trip->driver->first_name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <p class="font-semibold text-gray-800">{{ $trip->driver->first_name }} {{ $trip->driver->last_name }}</p>
                    <p class="text-xs text-gray-400">{{ $trip->driver->phone }}</p>
                    @if($trip->driver->vehicle_plate)
                        <span class="text-xs bg-gray-100 px-2 py-0.5 rounded font-mono mt-1 inline-block">{{ $trip->driver->vehicle_plate }}</span>
                    @endif
                </div>
            </div>
            @else
                <p class="text-gray-400 text-sm">Chauffeur non renseigné</p>
            @endif
        </div>

        <!-- Financier -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-700 mb-4 text-sm uppercase tracking-wide">Financier</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-500">Montant course</p>
                    <p class="text-sm font-semibold text-gray-800">{{ number_format($trip->amount ?? 0, 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-500">Commission ({{ auth('company')->user()->commission_rate }}%)</p>
                    <p class="text-sm font-semibold text-red-500">
                        - {{ number_format(($trip->amount ?? 0) * auth('company')->user()->commission_rate / 100, 0, ',', ' ') }} FCFA
                    </p>
                </div>
                <div class="border-t border-gray-100 pt-3 flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-700">Net société</p>
                    <p class="text-base font-bold text-blue-700">
                        {{ number_format(($trip->amount ?? 0) * (1 - auth('company')->user()->commission_rate / 100), 0, ',', ' ') }} FCFA
                    </p>
                </div>
            </div>
        </div>

    </div>

    @if($trip->distance_km || $trip->duration)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mt-6">
        <h3 class="font-semibold text-gray-700 mb-4 text-sm uppercase tracking-wide">Détails du trajet</h3>
        <div class="flex gap-8">
            @if($trip->distance_km)
            <div>
                <p class="text-xs text-gray-400 uppercase">Distance</p>
                <p class="font-semibold text-gray-800">{{ $trip->distance_km }} km</p>
            </div>
            @endif
            @if($trip->duration)
            <div>
                <p class="text-xs text-gray-400 uppercase">Durée</p>
                <p class="font-semibold text-gray-800">{{ $trip->duration }} min</p>
            </div>
            @endif
        </div>
    </div>
    @endif

</div>
@endsection
