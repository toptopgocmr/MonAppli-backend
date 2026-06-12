@extends('company.layouts.app')
@section('title', 'Profil Chauffeur')
@section('page-title', 'Profil Chauffeur')

@section('content')
<div class="max-w-4xl">

    <a href="{{ route('company.drivers.index') }}" class="text-blue-600 text-sm hover:underline flex items-center gap-1 mb-6">
        ← Retour à la liste
    </a>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-6">
        <div class="flex items-center gap-6 mb-6">
            @if($driver->profile_photo)
                <img src="{{ $driver->profile_photo }}" class="w-20 h-20 rounded-full object-cover border-4 border-blue-200">
            @else
                <div class="w-20 h-20 rounded-full bg-blue-600 flex items-center justify-center text-3xl font-bold text-white">
                    {{ strtoupper(substr($driver->first_name, 0, 1)) }}
                </div>
            @endif
            <div>
                <h2 class="text-2xl font-bold text-gray-800">{{ $driver->first_name }} {{ $driver->last_name }}</h2>
                <p class="text-gray-500">{{ $driver->phone }}</p>
                <div class="flex gap-2 mt-2">
                    @php $colors = ['approved'=>'green','pending'=>'yellow','rejected'=>'red','suspended'=>'gray']; @endphp
                    <span class="text-xs px-3 py-1 rounded-full font-medium bg-{{ $colors[$driver->status] ?? 'gray' }}-100 text-{{ $colors[$driver->status] ?? 'gray' }}-700">
                        {{ ['approved'=>'Approuvé','pending'=>'En attente','rejected'=>'Rejeté','suspended'=>'Suspendu'][$driver->status] ?? $driver->status }}
                    </span>
                    <span class="text-xs px-3 py-1 rounded-full font-medium {{ $driver->driver_status === 'online' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $driver->driver_status === 'online' ? 'En ligne' : 'Hors ligne' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 p-4 rounded-xl">
                <p class="text-xs text-gray-400 uppercase mb-1">Véhicule</p>
                <p class="font-semibold text-gray-800">{{ $driver->vehicle_brand ?? '—' }} {{ $driver->vehicle_model ?? '' }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-xl">
                <p class="text-xs text-gray-400 uppercase mb-1">Plaque</p>
                <p class="font-semibold text-gray-800">{{ $driver->vehicle_plate ?? '—' }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-xl">
                <p class="text-xs text-gray-400 uppercase mb-1">Couleur</p>
                <p class="font-semibold text-gray-800">{{ $driver->vehicle_color ?? '—' }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-xl">
                <p class="text-xs text-gray-400 uppercase mb-1">Ville</p>
                <p class="font-semibold text-gray-800">{{ $driver->vehicle_city ?? '—' }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-xl">
                <p class="text-xs text-gray-400 uppercase mb-1">Inscrit le</p>
                <p class="font-semibold text-gray-800">{{ $driver->created_at->format('d/m/Y') }}</p>
            </div>
        </div>
    </div>

    <!-- Documents KYC (lecture seule) -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <h3 class="font-semibold text-gray-700 mb-4 pb-3 border-b border-gray-100">Documents KYC</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            @foreach([
                ['CNI Recto','id_card_front'],['CNI Verso','id_card_back'],
                ['Permis Recto','license_front'],['Permis Verso','license_back'],
                ['Carte grise','vehicle_registration'],['Assurance','insurance']
            ] as [$label, $field])
            <div class="border border-gray-200 rounded-xl overflow-hidden">
                <div class="bg-gray-50 px-3 py-2 border-b border-gray-200">
                    <p class="text-xs font-semibold text-gray-700">{{ $label }}</p>
                </div>
                <div class="p-2">
                    @if($driver->{$field})
                        @php $ext = strtolower(pathinfo(parse_url($driver->{$field}, PHP_URL_PATH), PATHINFO_EXTENSION)); @endphp
                        @if(in_array($ext, ['jpg','jpeg','png','webp']))
                            <a href="{{ $driver->{$field} }}" target="_blank">
                                <img src="{{ $driver->{$field} }}" class="w-full h-24 object-cover rounded-lg">
                            </a>
                        @else
                            <a href="{{ $driver->{$field} }}" target="_blank" class="text-blue-600 text-xs hover:underline">Voir le fichier</a>
                        @endif
                    @else
                        <div class="h-24 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 text-xs">Non fourni</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
