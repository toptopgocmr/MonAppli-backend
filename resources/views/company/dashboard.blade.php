@extends('company.layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

<!-- KPI Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

    <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-3">
            <p class="text-sm text-gray-500 font-medium">Chauffeurs total</p>
            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-gray-800">{{ $totalDrivers }}</p>
        <p class="text-xs text-green-600 mt-1">{{ $approvedDrivers }} approuvés</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-3">
            <p class="text-sm text-gray-500 font-medium">En ligne maintenant</p>
            <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
            </div>
        </div>
        <p class="text-3xl font-bold text-gray-800">{{ $activeDrivers }}</p>
        <p class="text-xs text-gray-400 mt-1">chauffeurs actifs</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-3">
            <p class="text-sm text-gray-500 font-medium">Courses ce mois</p>
            <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-gray-800">{{ $tripsThisMonth }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $totalTrips }} au total</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-3">
            <p class="text-sm text-gray-500 font-medium">Revenus ce mois</p>
            <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-gray-800">{{ number_format($revenueThisMonth, 0, ',', ' ') }} FCFA</p>
        <p class="text-xs text-gray-400 mt-1">commission {{ $company->commission_rate }}% déduite</p>
    </div>

</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Derniers chauffeurs -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">Chauffeurs récents</h2>
            <a href="{{ route('company.drivers.index') }}" class="text-blue-600 text-sm hover:underline">Voir tout</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentDrivers as $driver)
            <div class="px-6 py-4 flex items-center gap-3">
                @if($driver->profile_photo)
                    <img src="{{ $driver->profile_photo }}" class="w-9 h-9 rounded-full object-cover">
                @else
                    <div class="w-9 h-9 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
                        {{ strtoupper(substr($driver->first_name, 0, 1)) }}
                    </div>
                @endif
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-800">{{ $driver->first_name }} {{ $driver->last_name }}</p>
                    <p class="text-xs text-gray-400">{{ $driver->phone }}</p>
                </div>
                <span class="text-xs px-2 py-1 rounded-full font-medium
                    {{ $driver->status === 'approved' ? 'bg-green-100 text-green-700' :
                       ($driver->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                    {{ $driver->status === 'approved' ? 'Approuvé' : ($driver->status === 'pending' ? 'En attente' : 'Rejeté') }}
                </span>
            </div>
            @empty
            <div class="px-6 py-8 text-center text-gray-400 text-sm">Aucun chauffeur pour le moment</div>
            @endforelse
        </div>
    </div>

    <!-- Dernières courses -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">Courses récentes</h2>
            <a href="{{ route('company.reservations.index') }}" class="text-blue-600 text-sm hover:underline">Voir tout</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentTrips as $trip)
            <div class="px-6 py-4">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-sm font-medium text-gray-800 truncate max-w-xs">
                        {{ $trip->driver->first_name ?? '—' }} {{ $trip->driver->last_name ?? '' }}
                    </p>
                    <span class="text-sm font-semibold text-blue-700">{{ number_format($trip->amount ?? 0, 0, ',', ' ') }} FCFA</span>
                </div>
                <p class="text-xs text-gray-400 truncate">{{ $trip->departure ?? '—' }} → {{ $trip->destination ?? '—' }}</p>
            </div>
            @empty
            <div class="px-6 py-8 text-center text-gray-400 text-sm">Aucune course pour le moment</div>
            @endforelse
        </div>
    </div>

</div>

@endsection
