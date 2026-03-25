@extends('admin.layouts.app')

@section('content')

<div class="max-w-7xl mx-auto">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">📊 Gestion des Commissions</h2>
            <p class="text-gray-500 text-sm mt-1">Définissez les taux par pays, type de véhicule ou chauffeur</p>
        </div>
        <a href="{{ route('admin.commission-rates.export') }}"
           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
            📥 Exporter CSV
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- ===== COLONNE GAUCHE : Règles actives ===== --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b flex items-center gap-2">
                <span class="font-bold text-gray-700">⚙️ Règles actives</span>
            </div>

            {{-- Priorité --}}
            <div class="px-5 py-2 bg-gray-50 border-b text-xs text-gray-500 flex flex-wrap gap-1 items-center">
                <span class="font-semibold">Priorité :</span>
                <span class="bg-blue-600 text-white px-2 py-0.5 rounded-full">Chauffeur</span>
                <span>›</span>
                <span class="bg-cyan-500 text-white px-2 py-0.5 rounded-full">Type véhicule</span>
                <span>›</span>
                <span class="bg-green-600 text-white px-2 py-0.5 rounded-full">Pays</span>
                <span>›</span>
                <span class="bg-gray-500 text-white px-2 py-0.5 rounded-full">Global</span>
            </div>

            {{-- Liste --}}
            <div class="divide-y">
                @forelse($allRates as $rate)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div class="flex flex-wrap items-center gap-2">
                        @if($rate->type === 'global')
                            <span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full">🌍 Global</span>
                        @elseif($rate->type === 'country')
                            <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full">🚩 {{ $rate->country }}</span>
                        @elseif($rate->type === 'vehicle_type')
                            <span class="bg-cyan-100 text-cyan-700 text-xs px-2 py-1 rounded-full">🚗 {{ $rate->vehicle_type }}</span>
                        @elseif($rate->type === 'driver')
                            <span class="bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded-full">
                                👤 {{ optional($rate->driver)->first_name }} {{ optional($rate->driver)->last_name }}
                            </span>
                        @endif

                        <span class="font-bold text-gray-800">{{ $rate->rate }}%</span>

                        @if($rate->description)
                            <span class="text-gray-400 text-xs">— {{ $rate->description }}</span>
                        @endif

                        @if($rate->is_active)
                            <span class="bg-green-100 text-green-600 text-xs px-2 py-0.5 rounded-full">✓ Actif</span>
                        @else
                            <span class="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full">✗ Inactif</span>
                        @endif
                    </div>

                    <div class="flex gap-1 ml-2 shrink-0">
                        <button class="btn-edit-rule hover:bg-blue-50 p-1.5 rounded-lg transition text-sm"
                            data-id="{{ $rate->id }}"
                            data-type="{{ $rate->type }}"
                            data-rate="{{ $rate->rate }}"
                            data-description="{{ $rate->description }}"
                            data-country="{{ $rate->country }}"
                            data-vehicle="{{ $rate->vehicle_type }}"
                            data-driver="{{ $rate->driver_id }}"
                            title="Modifier">✏️</button>

                        @if($rate->type !== 'global')
                        <form action="{{ route('admin.commission-rates.destroy', $rate->id) }}" method="POST" class="inline">
                            @csrf @method('DELETE')
                            <button onclick="return confirm('Supprimer cette règle ?')"
                                class="hover:bg-red-50 p-1.5 rounded-lg transition text-sm" title="Supprimer">🗑️</button>
                        </form>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-center text-gray-400 py-10">
                    <p class="text-3xl mb-2">📭</p>
                    <p>Aucune règle définie</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- ===== COLONNE DROITE : Formulaire ===== --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b">
                <span class="font-bold text-gray-700" id="form-title">➕ Ajouter / Modifier une règle</span>
            </div>
            <div class="p-5">
                <form action="{{ route('admin.commission-rates.store') }}" method="POST" id="commission-form">
                    @csrf
                    <input type="hidden" name="_method" id="form-method" value="POST">

                    {{-- Type de règle --}}
                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Type de règle <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">

                            <label class="rule-card flex items-start gap-2 p-3 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition" for="type_global">
                                <input type="radio" name="type" id="type_global" value="global" class="mt-0.5 accent-blue-600" checked onchange="switchType('global')">
                                <div>
                                    <p class="font-semibold text-sm">🌍 Global</p>
                                    <p class="text-xs text-gray-400">S'applique à tous</p>
                                </div>
                            </label>

                            <label class="rule-card flex items-start gap-2 p-3 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition" for="type_country">
                                <input type="radio" name="type" id="type_country" value="country" class="mt-0.5 accent-blue-600" onchange="switchType('country')">
                                <div>
                                    <p class="font-semibold text-sm">🚩 Par pays</p>
                                    <p class="text-xs text-gray-400">Selon le pays</p>
                                </div>
                            </label>

                            <label class="rule-card flex items-start gap-2 p-3 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition" for="type_vehicle">
                                <input type="radio" name="type" id="type_vehicle" value="vehicle_type" class="mt-0.5 accent-blue-600" onchange="switchType('vehicle_type')">
                                <div>
                                    <p class="font-semibold text-sm">🚗 Par véhicule</p>
                                    <p class="text-xs text-gray-400">Selon le type</p>
                                </div>
                            </label>

                            <label class="rule-card flex items-start gap-2 p-3 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition" for="type_driver">
                                <input type="radio" name="type" id="type_driver" value="driver" class="mt-0.5 accent-blue-600" onchange="switchType('driver')">
                                <div>
                                    <p class="font-semibold text-sm">👤 Par chauffeur</p>
                                    <p class="text-xs text-gray-400">Contrat individuel</p>
                                </div>
                            </label>

                        </div>
                    </div>

                    {{-- Dropdown Pays --}}
                    <div class="mb-4 hidden" id="field-country">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            🚩 Pays <span class="text-red-500">*</span>
                        </label>
                        <select name="country" id="select-country"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Sélectionner un pays --</option>
                            @foreach($countries as $country)
                                <option value="{{ $country }}">{{ $country }}</option>
                            @endforeach
                        </select>
                        @error('country')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- Dropdown Véhicule --}}
                    <div class="mb-4 hidden" id="field-vehicle">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            🚗 Type de véhicule <span class="text-red-500">*</span>
                        </label>
                        <select name="vehicle_type" id="select-vehicle"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Sélectionner un type --</option>
                            @foreach(['Standard', 'Confort', 'Van', 'PMR'] as $vType)
                                <option value="{{ $vType }}">{{ $vType }}</option>
                            @endforeach
                        </select>
                        @error('vehicle_type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- Dropdown Chauffeur --}}
                    <div class="mb-4 hidden" id="field-driver">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            👤 Chauffeur <span class="text-red-500">*</span>
                        </label>
                        <select name="driver_id" id="select-driver"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Rechercher un chauffeur --</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}">
                                    {{ $driver->first_name }} {{ $driver->last_name }}
                                    @if($driver->phone) — {{ $driver->phone }} @endif
                                    @if(isset($driver->vehicle_country)) ({{ $driver->vehicle_country }}) @endif
                                </option>
                            @endforeach
                        </select>
                        @error('driver_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- Taux + Description --}}
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">
                                Taux (%) <span class="text-red-500">*</span>
                            </label>
                            <div class="flex">
                                <input type="number" name="rate" id="input-rate"
                                    class="flex-1 border border-gray-300 rounded-l-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Ex: 15" min="0" max="100" step="0.01" required>
                                <span class="bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg px-3 py-2 text-sm text-gray-500">%</span>
                            </div>
                            @error('rate')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Statut</label>
                            <label class="flex items-center gap-2 mt-2 cursor-pointer">
                                <input type="checkbox" name="is_active" id="is_active" value="1" checked
                                    class="w-4 h-4 accent-blue-600">
                                <span class="text-sm text-gray-600">Actif</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                        <input type="text" name="description" id="input-description"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Ex: Congo Brazzaville, SUV premium...">
                    </div>

                    {{-- Boutons --}}
                    <div class="flex gap-3">
                        <button type="submit"
                            class="flex-1 bg-[#1DA1F2] hover:bg-blue-700 text-white py-2 rounded-lg font-semibold text-sm transition">
                            💾 <span id="btn-label">Enregistrer la règle</span>
                        </button>
                        <button type="button" onclick="resetForm()"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm transition">
                            ✕ Reset
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>{{-- /grid --}}

    {{-- ===== Filtres ===== --}}
    <div class="bg-white rounded-xl shadow mt-6 p-5">
        <form method="GET" action="{{ route('admin.commission-rates.index') }}">
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach(['day' => "Aujourd'hui", 'week' => 'Cette semaine', 'month' => 'Ce mois', 'year' => 'Cette année'] as $key => $label)
                <a href="?period={{ $key }}"
                   class="px-3 py-1.5 rounded-lg text-sm font-medium transition
                   {{ $period === $key ? 'bg-[#1DA1F2] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    {{ $label }}
                </a>
                @endforeach
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Chauffeur</label>
                    <select name="driver_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Tous</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>
                                {{ $driver->first_name }} {{ $driver->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Pays</label>
                    <select name="country" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Tous</option>
                        @foreach($countries as $country)
                            <option value="{{ $country }}" {{ request('country') === $country ? 'selected' : '' }}>{{ $country }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Ville</label>
                    <select name="city" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Toutes</option>
                        @foreach($cities as $city)
                            <option value="{{ $city }}" {{ request('city') === $city ? 'selected' : '' }}>{{ $city }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Type véhicule</label>
                    <select name="vehicle_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Tous</option>
                        @foreach(['Standard', 'Confort', 'Van', 'PMR'] as $vType)
                            <option value="{{ $vType }}" {{ request('vehicle_type') === $vType ? 'selected' : '' }}>{{ $vType }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex gap-2 mt-3">
                <button type="submit"
                    class="bg-[#1DA1F2] hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition">
                    🔍 Filtrer
                </button>
                <a href="{{ route('admin.commission-rates.index') }}"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm transition">
                    ✕ Reset
                </a>
            </div>
        </form>
    </div>

    {{-- ===== Tableau des trips ===== --}}
    <div class="bg-white rounded-xl shadow mt-4 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">#Trip</th>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Chauffeur</th>
                        <th class="px-4 py-3 text-left">Client</th>
                        <th class="px-4 py-3 text-right">Montant</th>
                        <th class="px-4 py-3 text-right">Commission</th>
                        <th class="px-4 py-3 text-right">Net chauffeur</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($trips as $trip)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 font-mono text-gray-500">#{{ $trip->id }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ \Carbon\Carbon::parse($trip->completed_at)->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3 font-medium">
                            {{ optional($trip->driver)->first_name }} {{ optional($trip->driver)->last_name }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            @if($trip->user)
                                {{ $trip->user->name ?? ($trip->user->first_name . ' ' . $trip->user->last_name) }}
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-medium">
                            {{ number_format($trip->amount, 0, ',', ' ') }} FCFA
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-red-500">
                            {{ number_format($trip->commission, 0, ',', ' ') }} FCFA
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-green-600">
                            {{ number_format($trip->driver_net, 0, ',', ' ') }} FCFA
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-gray-400 py-10">
                            Aucun trajet pour cette période
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($trips->hasPages())
        <div class="px-4 py-3 border-t">
            {{ $trips->links() }}
        </div>
        @endif
    </div>

</div>

<style>
.rule-card:has(input:checked) {
    border-color: #1DA1F2;
    background-color: #eff8ff;
}
</style>

@push('scripts')
<script>
function switchType(type) {
    const fields = {
        country:      document.getElementById('field-country'),
        vehicle_type: document.getElementById('field-vehicle'),
        driver:       document.getElementById('field-driver'),
    };

    Object.entries(fields).forEach(([key, el]) => {
        el.classList.add('hidden');
        const sel = el.querySelector('select');
        if (sel) sel.removeAttribute('required');
    });

    if (type !== 'global' && fields[type]) {
        fields[type].classList.remove('hidden');
        const sel = fields[type].querySelector('select');
        if (sel) sel.setAttribute('required', 'required');
    }
}

document.querySelectorAll('.btn-edit-rule').forEach(btn => {
    btn.addEventListener('click', function () {
        const id      = this.dataset.id;
        const type    = this.dataset.type;
        const rate    = this.dataset.rate;
        const desc    = this.dataset.description;
        const country = this.dataset.country;
        const vehicle = this.dataset.vehicle;
        const driver  = this.dataset.driver;

        const form = document.getElementById('commission-form');
        form.action = `/admin/commission-rates/${id}`;
        document.getElementById('form-method').value = 'PUT';
        document.getElementById('form-title').textContent = '✏️ Modifier la règle';
        document.getElementById('btn-label').textContent  = 'Mettre à jour';

        const radioMap = {
            global:       'type_global',
            country:      'type_country',
            vehicle_type: 'type_vehicle',
            driver:       'type_driver',
        };
        if (radioMap[type]) document.getElementById(radioMap[type]).checked = true;
        switchType(type);

        document.getElementById('input-rate').value        = rate;
        document.getElementById('input-description').value = desc ?? '';

        if (type === 'country')      document.getElementById('select-country').value = country;
        if (type === 'vehicle_type') document.getElementById('select-vehicle').value = vehicle;
        if (type === 'driver')       document.getElementById('select-driver').value  = driver;

        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});

function resetForm() {
    const form = document.getElementById('commission-form');
    form.reset();
    form.action = "{{ route('admin.commission-rates.store') }}";
    document.getElementById('form-method').value  = 'POST';
    document.getElementById('form-title').textContent = '➕ Ajouter / Modifier une règle';
    document.getElementById('btn-label').textContent  = 'Enregistrer la règle';
    switchType('global');
}

switchType('global');
</script>
@endpush
@endsection
