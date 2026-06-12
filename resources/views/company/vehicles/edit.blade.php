@extends('company.layouts.app')
@section('title', 'Modifier Véhicule')
@section('page-title', 'Modifier les informations du véhicule')

@section('content')
<div class="max-w-2xl">

    <a href="{{ route('company.vehicles.index') }}" class="text-blue-600 text-sm hover:underline flex items-center gap-1 mb-6">
        ← Retour à la flotte
    </a>

    <!-- Chauffeur info -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6 flex items-center gap-4">
        @if($driver->profile_photo)
            <img src="{{ $driver->profile_photo }}" class="w-14 h-14 rounded-full object-cover border-2 border-blue-200">
        @else
            <div class="w-14 h-14 bg-blue-600 rounded-full flex items-center justify-center text-white text-xl font-bold">
                {{ strtoupper(substr($driver->first_name, 0, 1)) }}
            </div>
        @endif
        <div>
            <h2 class="font-semibold text-gray-800">{{ $driver->first_name }} {{ $driver->last_name }}</h2>
            <p class="text-sm text-gray-400">{{ $driver->phone }}</p>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <h3 class="font-semibold text-gray-700 mb-6 pb-3 border-b border-gray-100">Informations du véhicule</h3>

        <form method="POST" action="{{ route('company.vehicles.update', $driver->id) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Marque</label>
                    <input type="text" name="vehicle_brand" value="{{ old('vehicle_brand', $driver->vehicle_brand) }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Toyota, Renault...">
                    @error('vehicle_brand')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Modèle</label>
                    <input type="text" name="vehicle_model" value="{{ old('vehicle_model', $driver->vehicle_model) }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Corolla, Clio...">
                    @error('vehicle_model')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Plaque d'immatriculation</label>
                    <input type="text" name="vehicle_plate" value="{{ old('vehicle_plate', $driver->vehicle_plate) }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="AB-1234-CD">
                    @error('vehicle_plate')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Couleur</label>
                    <input type="text" name="vehicle_color" value="{{ old('vehicle_color', $driver->vehicle_color) }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Blanc, Noir...">
                    @error('vehicle_color')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type de véhicule</label>
                    <select name="vehicle_type" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Sélectionner —</option>
                        @foreach(['Berline','SUV','Monospace','Pickup','Minibus','Moto'] as $type)
                            <option value="{{ $type }}" {{ old('vehicle_type', $driver->vehicle_type) === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                    @error('vehicle_type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ville opération</label>
                    <input type="text" name="vehicle_city" value="{{ old('vehicle_city', $driver->vehicle_city) }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Yaoundé, Douala...">
                    @error('vehicle_city')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2.5 rounded-xl transition shadow">
                    Enregistrer les modifications
                </button>
                <a href="{{ route('company.vehicles.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">
                    Annuler
                </a>
            </div>
        </form>
    </div>

</div>
@endsection
