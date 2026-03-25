@extends('admin.layouts.app')

@section('title', 'Vérification KYC')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Vérification KYC</h1>
    <p class="text-gray-600">Vérifier les documents des chauffeurs</p>
</div>

<!-- Status Tabs -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="border-b border-gray-200">
        <nav class="flex -mb-px">
            <a href="{{ route('admin.kyc', ['status' => 'pending']) }}"
               class="px-6 py-4 border-b-2 font-medium text-sm {{ $status === 'pending' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                En attente
            </a>
            <a href="{{ route('admin.kyc', ['status' => 'approved']) }}"
               class="px-6 py-4 border-b-2 font-medium text-sm {{ $status === 'approved' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Approuvés
            </a>
            <a href="{{ route('admin.kyc', ['status' => 'rejected']) }}"
               class="px-6 py-4 border-b-2 font-medium text-sm {{ $status === 'rejected' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Rejetés
            </a>
        </nav>
    </div>
</div>

<!-- Drivers List -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($drivers as $driver)
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                    <span class="text-blue-600 font-bold text-lg">{{ substr($driver->user->first_name ?? 'D', 0, 1) }}</span>
                </div>
                <div class="ml-4">
                    <h3 class="font-semibold text-gray-800">
                        {{ $driver->user->first_name ?? 'N/A' }} {{ $driver->user->last_name ?? '' }}
                    </h3>
                    <p class="text-sm text-gray-500">{{ $driver->user->phone ?? 'N/A' }}</p>
                </div>
            </div>

            <div class="space-y-2 mb-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Véhicule</span>
                    <span class="text-gray-800">{{ $driver->vehicle_brand ?? 'N/A' }} {{ $driver->vehicle_model ?? '' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Plaque</span>
                    <span class="text-gray-800">{{ $driver->vehicle_plate ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">N° Permis</span>
                    <span class="text-gray-800">{{ $driver->license_number ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Inscrit le</span>
                    <span class="text-gray-800">{{ $driver->created_at->format('d/m/Y') }}</span>
                </div>
            </div>

            @if($status === 'pending')
            <a href="{{ route('admin.kyc.review', $driver) }}"
               class="block w-full text-center bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                Vérifier les documents
            </a>
            @elseif($status === 'rejected')
            <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-3">
                <p class="text-sm text-red-700">
                    <strong>Raison:</strong> {{ $driver->kyc_rejection_reason ?? 'Non spécifiée' }}
                </p>
            </div>
            <a href="{{ route('admin.kyc.review', $driver) }}"
               class="block w-full text-center bg-gray-600 text-white py-2 rounded-lg hover:bg-gray-700">
                Revoir
            </a>
            @else
            <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                <p class="text-sm text-green-700">
                    Approuvé le {{ $driver->kyc_reviewed_at ? $driver->kyc_reviewed_at->format('d/m/Y') : 'N/A' }}
                </p>
            </div>
            @endif
        </div>
    </div>
    @empty
    <div class="col-span-full">
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
            </svg>
            <p class="text-gray-500">Aucune vérification {{ $status === 'pending' ? 'en attente' : ($status === 'approved' ? 'approuvée' : 'rejetée') }}</p>
        </div>
    </div>
    @endforelse
</div>

<!-- Pagination -->
<div class="mt-6">
    {{ $drivers->links() }}
</div>
@endsection
