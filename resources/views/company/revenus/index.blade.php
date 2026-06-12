@extends('company.layouts.app')
@section('title', 'Revenus')
@section('page-title', 'Revenus & Analyses')

@section('content')

<!-- KPI Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

    <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
        <p class="text-sm text-gray-500 font-medium mb-2">Revenus ce mois</p>
        <p class="text-3xl font-bold text-gray-800">{{ number_format($revenueThisMonth, 0, ',', ' ') }} <span class="text-base font-normal text-gray-400">FCFA</span></p>
        <p class="text-xs text-gray-400 mt-1">{{ $tripsThisMonth }} course(s)</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
        <p class="text-sm text-gray-500 font-medium mb-2">Revenus total</p>
        <p class="text-3xl font-bold text-gray-800">{{ number_format($revenueTotal, 0, ',', ' ') }} <span class="text-base font-normal text-gray-400">FCFA</span></p>
        <p class="text-xs text-gray-400 mt-1">{{ $tripsTotal }} course(s) au total</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
        <p class="text-sm text-gray-500 font-medium mb-2">Commission plateforme</p>
        <p class="text-3xl font-bold text-red-500">{{ $company->commission_rate }}%</p>
        <p class="text-xs text-gray-400 mt-1">déduite sur chaque course</p>
    </div>

</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Évolution mensuelle -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-800 mb-5">Évolution mensuelle (12 derniers mois)</h2>
        <div class="space-y-3">
            @php
                $maxMonth = $monthlyRevenue->max('total') ?: 1;
            @endphp
            @foreach($monthlyRevenue as $month)
            <div class="flex items-center gap-3">
                <p class="text-xs text-gray-500 w-20 flex-shrink-0">{{ \Carbon\Carbon::createFromFormat('Y-m', $month->month)->locale('fr')->translatedFormat('M Y') }}</p>
                <div class="flex-1 bg-gray-100 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ ($month->total / $maxMonth) * 100 }}%"></div>
                </div>
                <p class="text-xs font-semibold text-gray-700 w-28 text-right">{{ number_format($month->total, 0, ',', ' ') }} FCFA</p>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Top chauffeurs -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-800 mb-5">Top Chauffeurs (ce mois)</h2>
        <div class="space-y-4">
            @forelse($topDrivers as $index => $item)
            @php $driver = $item['driver']; $total = $item['total']; $count = $item['count']; @endphp
            <div class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex items-center justify-center flex-shrink-0">
                    {{ $index + 1 }}
                </span>
                @if($driver->profile_photo)
                    <img src="{{ $driver->profile_photo }}" class="w-9 h-9 rounded-full object-cover flex-shrink-0">
                @else
                    <div class="w-9 h-9 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                        {{ strtoupper(substr($driver->first_name, 0, 1)) }}
                    </div>
                @endif
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-800">{{ $driver->first_name }} {{ $driver->last_name }}</p>
                    <p class="text-xs text-gray-400">{{ $count }} course(s)</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-blue-700">{{ number_format($total, 0, ',', ' ') }}</p>
                    <p class="text-xs text-gray-400">FCFA</p>
                </div>
            </div>
            @empty
            <p class="text-gray-400 text-sm text-center py-4">Aucune course ce mois</p>
            @endforelse
        </div>
    </div>

</div>

@endsection
