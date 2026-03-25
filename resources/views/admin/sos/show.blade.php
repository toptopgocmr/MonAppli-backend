@extends('admin.layouts.app')

@section('content')
<div class="p-6 max-w-4xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.sos.index') }}"
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm transition">
            ← Retour
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">🆘 Détail Alerte SOS #{{ $alert->id }}</h1>
            <p class="text-sm text-gray-400 mt-0.5">{{ $alert->created_at->format('d/m/Y à H:i:s') }}</p>
        </div>
        <span class="ml-auto text-sm px-3 py-1.5 rounded-full font-medium
            {{ $alert->status === 'active' ? 'bg-red-500 text-white animate-pulse' : 'bg-green-100 text-green-700' }}">
            {{ $alert->status === 'active' ? '🆘 ACTIVE' : '✅ Traitée' }}
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- Infos expéditeur --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-700 mb-4">
                {{ str_contains($alert->sender_type, 'Driver') ? '🚗 Chauffeur' : '👤 Utilisateur' }}
            </h3>
            @if($alert->sender)
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Nom</span>
                        <span class="font-medium">{{ $alert->sender->first_name }} {{ $alert->sender->last_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Téléphone</span>
                        <span class="font-medium">{{ $alert->sender->phone ?? '—' }}</span>
                    </div>
                    @if(str_contains($alert->sender_type, 'Driver'))
                        <div class="flex justify-between">
                            <span class="text-gray-500">Véhicule</span>
                            <span class="font-medium">
                                {{ $alert->sender->vehicle_brand }} {{ $alert->sender->vehicle_model }}
                                — {{ $alert->sender->vehicle_plate }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Statut</span>
                            <span class="font-medium">{{ $alert->sender->driver_status }}</span>
                        </div>
                    @endif
                </div>
            @else
                <p class="text-gray-400 text-sm">Expéditeur introuvable</p>
            @endif
        </div>

        {{-- Infos alerte --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-700 mb-4">📋 Détails de l'alerte</h3>
            <div class="space-y-2 text-sm">
                @if($alert->message)
                    <div>
                        <span class="text-gray-500 block mb-1">Message :</span>
                        <p class="bg-red-50 text-red-800 px-3 py-2 rounded-lg">{{ $alert->message }}</p>
                    </div>
                @endif
                @if($alert->lat && $alert->lng)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Position GPS</span>
                        <span class="font-medium font-mono text-xs">{{ $alert->lat }}, {{ $alert->lng }}</span>
                    </div>
                @endif
                @if($alert->trip)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Course liée</span>
                        <span class="font-medium">#{{ $alert->trip_id }} — {{ $alert->trip->status }}</span>
                    </div>
                @endif
                @if($alert->status === 'treated')
                    <div class="flex justify-between">
                        <span class="text-gray-500">Traité par</span>
                        <span class="font-medium">{{ $alert->treatedBy->name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Traité le</span>
                        <span class="font-medium">{{ $alert->treated_at?->format('d/m/Y H:i') }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Carte position --}}
        @if($alert->lat && $alert->lng)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden md:col-span-2">
            <div class="p-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-700">📍 Position de l'alerte</h3>
            </div>
            <div id="alertMap" style="height:300px; z-index:1;"></div>
        </div>
        @endif

        {{-- Actions --}}
        <div class="md:col-span-2 flex gap-3 justify-end">
            @if($alert->status === 'active')
                <form method="POST" action="{{ route('admin.sos.treat', $alert->id) }}">
                    @csrf
                    <button class="bg-green-600 hover:bg-green-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium transition">
                        ✓ Marquer comme traitée
                    </button>
                </form>
            @endif
            <form method="POST" action="{{ route('admin.sos.destroy', $alert->id) }}"
                  onsubmit="return confirm('Supprimer cette alerte définitivement ?')">
                @csrf @method('DELETE')
                <button class="bg-red-100 hover:bg-red-200 text-red-700 px-6 py-2.5 rounded-lg text-sm font-medium transition">
                    🗑 Supprimer
                </button>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
@if($alert->lat && $alert->lng)
<script>
const alertMap = L.map('alertMap').setView([{{ $alert->lat }}, {{ $alert->lng }}], 15);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap', maxZoom: 19
}).addTo(alertMap);

const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="40" height="50" viewBox="0 0 40 50">
    <path d="M20 0 C9 0 0 9 0 20 C0 33 20 50 20 50 C20 50 40 33 40 20 C40 9 31 0 20 0Z"
          fill="#ef4444" stroke="white" stroke-width="2"/>
    <text x="20" y="26" text-anchor="middle" font-size="18" fill="white">🆘</text>
</svg>`;

const icon = L.divIcon({ html: svg, iconSize:[40,50], iconAnchor:[20,50], popupAnchor:[0,-50], className:'' });
L.marker([{{ $alert->lat }}, {{ $alert->lng }}], { icon })
 .addTo(alertMap)
 .bindPopup('<b>Position de l\'alerte SOS</b>')
 .openPopup();
</script>
@endif
@endpush