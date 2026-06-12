@extends('admin.layouts.app')

@section('content')

<!-- MODAL ZOOM -->
<div id="zoom-modal"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.88);z-index:9999;align-items:center;justify-content:center;padding:16px"
     onclick="if(event.target===this)closeZoom()">
    <div style="position:relative;max-width:900px;width:100%">
        <button onclick="closeZoom()"
                style="position:absolute;top:-40px;right:0;background:none;border:none;color:#fff;font-size:36px;cursor:pointer;line-height:1;opacity:.8"
                onmouseover="this.style.color='#FFC107'" onmouseout="this.style.color='#fff'">&times;</button>
        <img id="zoom-img" src=""
             style="width:100%;max-height:85vh;object-fit:contain;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,.5)">
        <div style="text-align:center;margin-top:16px">
            <a id="zoom-download" href="" download
               style="display:inline-flex;align-items:center;gap:8px;background:#1DA1F2;color:#fff;padding:10px 28px;border-radius:10px;font-weight:600;text-decoration:none;font-size:14px"
               onmouseover="this.style.background='#FFC107';this.style.color='#000'"
               onmouseout="this.style.background='#1DA1F2';this.style.color='#fff'">
                ⬇ Télécharger
            </a>
        </div>
    </div>
</div>

@php
$countriesVilles = [
    'République du Congo' => ['Brazzaville','Pointe-Noire','Dolisie','Nkayi','Impfondo','Ouesso','Owando','Makoua','Sibiti','Mossendjo','Kindamba','Kinkala','Madingou','Loutété','Gamboma'],
    'Cameroun' => ['Yaoundé','Douala','Garoua','Bamenda','Bafoussam','Ngaoundéré','Bertoua','Maroua','Kumba','Nkongsamba','Edéa','Kribi','Ebolowa','Limbé','Buea'],
    'République Centrafricaine' => ['Bangui','Bimbo','Berbérati','Carnot','Bambari','Bouar','Bossangoa','Bria','Kaga-Bandoro','Mbaïki'],
    'Tchad' => ["N'Djamena",'Moundou','Sarh','Abéché','Kélo','Koumra','Pala','Am Timan','Bongor','Doba'],
    'Guinée Équatoriale' => ['Malabo','Bata','Ebebiyín','Aconibe','Añisoc','Luba','Evinayong','Mongomo','Mbini','Riaba'],
    'Gabon' => ['Libreville','Port-Gentil','Franceville','Oyem','Moanda','Mouila','Lambaréné','Tchibanga','Koulamoutou','Makokou','Bitam','Gamba','Ndjolé','Mitzic','Booué'],
    'Autre' => [],
];
@endphp

<div class="max-w-5xl mx-auto">

    <!-- HEADER -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.drivers.show', $driver->id) }}" class="btn-back">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                Retour
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Modifier le Chauffeur</h1>
                <p class="text-gray-500 text-sm mt-1">{{ $driver->first_name }} {{ $driver->last_name }}</p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.drivers.update', $driver->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- INFORMATIONS PERSONNELLES -->
        <div class="bg-white rounded-2xl shadow-md p-8 mb-6">
            <h2 class="text-lg font-bold text-gray-700 mb-6 pb-3 border-b border-gray-100">Informations Personnelles</h2>
            <div class="flex items-center gap-6 mb-6">
                @if($driver->profile_photo)
                    @php $ppUrl = str_starts_with($driver->profile_photo, 'http') ? $driver->profile_photo : asset('storage/' . $driver->profile_photo); @endphp
                    <img src="{{ $ppUrl }}"
                         id="preview_profile_photo"
                         class="w-20 h-20 rounded-full object-cover border-4 border-[#1DA1F2] cursor-pointer"
                         onclick="openZoom('{{ $ppUrl }}')">
                @else
                    <div class="w-20 h-20 rounded-full bg-[#1DA1F2] flex items-center justify-center text-3xl font-bold text-white">
                        {{ strtoupper(substr($driver->first_name, 0, 1)) }}
                    </div>
                    <img id="preview_profile_photo" class="w-20 h-20 rounded-full object-cover border-4 border-[#1DA1F2] hidden">
                @endif
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Photo de profil</label>
                    <input type="file" name="profile_photo" accept="image/*"
                           onchange="previewImage(event, 'preview_profile_photo')"
                           class="text-sm text-gray-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Prénom <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" value="{{ old('first_name', $driver->first_name) }}"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1DA1F2]" required>
                    @error('first_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Nom <span class="text-red-500">*</span></label>
                    <input type="text" name="last_name" value="{{ old('last_name', $driver->last_name) }}"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1DA1F2]" required>
                    @error('last_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Téléphone <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" value="{{ old('phone', $driver->phone) }}"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1DA1F2]" required>
                    @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Date de naissance</label>
                    <input type="date" name="birth_date" value="{{ old('birth_date', $driver->birth_date) }}"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1DA1F2]">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Lieu de naissance</label>
                    <input type="text" name="birth_place" value="{{ old('birth_place', $driver->birth_place) }}"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1DA1F2]">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Pays de naissance</label>
                    <input type="text" name="country_birth" value="{{ old('country_birth', $driver->country_birth) }}"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1DA1F2]">
                </div>
            </div>
        </div>

        <!-- PIÈCE D'IDENTITÉ -->
        <div class="bg-white rounded-2xl shadow-md p-8 mb-6">
            <h2 class="text-lg font-bold text-gray-700 mb-6 pb-3 border-b border-gray-100">Pièce d'Identité</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Type de pièce</label>
                    <select name="id_card_type" class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1DA1F2]">
                        <option value="">-- Choisir --</option>
                        @foreach(["Carte d'Identité Nationale", "Passeport", "Carte de Résidence", "Carte Consulaire"] as $type)
                            <option value="{{ $type }}" {{ old('id_card_type', $driver->id_card_type ?? '') == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Ville d'émission</label>
                    <input type="text" name="id_card_issue_city" value="{{ old('id_card_issue_city', $driver->id_card_issue_city) }}"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1DA1F2]">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Pays d'émission</label>
                    <input type="text" name="id_card_issue_country" value="{{ old('id_card_issue_country', $driver->id_card_issue_country) }}"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1DA1F2]">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Date d'émission</label>
                    <input type="date" name="id_card_issue_date" value="{{ old('id_card_issue_date', $driver->id_card_issue_date) }}"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1DA1F2]">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Date d'expiration</label>
                    <input type="date" name="id_card_expiry_date" value="{{ old('id_card_expiry_date', $driver->id_card_expiry_date) }}"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1DA1F2]">
                </div>
            </div>
        </div>

        <!-- VÉHICULE -->
        <div class="bg-white rounded-2xl shadow-md p-8 mb-6">
            <h2 class="text-lg font-bold text-gray-700 mb-6 pb-3 border-b border-gray-100">Véhicule</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Plaque d'immatriculation</label>
                    <input type="text" name="vehicle_plate" value="{{ old('vehicle_plate', $driver->vehicle_plate) }}"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1DA1F2]">
                    @error('vehicle_plate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Marque</label>
                    <input type="text" name="vehicle_brand" value="{{ old('vehicle_brand', $driver->vehicle_brand) }}"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1DA1F2]">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Modèle</label>
                    <input type="text" name="vehicle_model" value="{{ old('vehicle_model', $driver->vehicle_model) }}"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1DA1F2]">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Type</label>
                    <select name="vehicle_type" class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1DA1F2]">
                        <option value="">-- Choisir --</option>
                        @foreach(['Berline', 'SUV', 'Van', 'Moto', 'Tricycle', 'Autre'] as $type)
                            <option value="{{ $type }}" {{ old('vehicle_type', $driver->vehicle_type) == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Couleur</label>
                    <input type="text" name="vehicle_color" value="{{ old('vehicle_color', $driver->vehicle_color) }}"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1DA1F2]">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Pays</label>
                    <select name="vehicle_country" id="vehicle_country"
                            onchange="updateVilles(this.value)"
                            class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1DA1F2]">
                        <option value="">-- Choisir un pays --</option>
                        @foreach(array_keys($countriesVilles) as $pays)
                            <option value="{{ $pays }}" {{ old('vehicle_country', $driver->vehicle_country) == $pays ? 'selected' : '' }}>
                                {{ $pays }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Ville</label>
                    <select name="vehicle_city" id="vehicle_city"
                            class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1DA1F2]">
                        <option value="">-- Choisir une ville --</option>
                        @foreach($countriesVilles[old('vehicle_country', $driver->vehicle_country)] ?? [] as $ville)
                            <option value="{{ $ville }}" {{ old('vehicle_city', $driver->vehicle_city) == $ville ? 'selected' : '' }}>
                                {{ $ville }}
                            </option>
                        @endforeach
                    </select>
                    <input type="text" name="vehicle_city_autre" id="vehicle_city_autre"
                           placeholder="Saisir la ville"
                           value="{{ old('vehicle_country', $driver->vehicle_country) == 'Autre' ? old('vehicle_city', $driver->vehicle_city) : '' }}"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2 mt-2 focus:outline-none focus:ring-2 focus:ring-[#1DA1F2] {{ old('vehicle_country', $driver->vehicle_country) == 'Autre' ? '' : 'hidden' }}">
                </div>
            </div>
        </div>

        <!-- DOCUMENTS KYC -->
        <div class="bg-white rounded-2xl shadow-md p-8 mb-6">
            <h2 class="text-lg font-bold text-gray-700 mb-6 pb-3 border-b border-gray-100">Documents KYC</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @php
                $docs = [
                    ['label' => 'CNI Recto',    'name' => 'id_card_front'],
                    ['label' => 'CNI Verso',     'name' => 'id_card_back'],
                    ['label' => 'Permis Recto',  'name' => 'license_front'],
                    ['label' => 'Permis Verso',  'name' => 'license_back'],
                    ['label' => 'Carte grise',   'name' => 'vehicle_registration'],
                    ['label' => 'Assurance',     'name' => 'insurance'],
                ];
                @endphp

                @foreach($docs as $doc)
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <p class="text-sm font-semibold text-gray-700">{{ $doc['label'] }}</p>
                    </div>
                    <div class="p-3">
                        @if($driver->{$doc['name']})
                            @php
                                $docVal = $driver->{$doc['name']};
                                $docUrl = str_starts_with($docVal, 'http') ? $docVal : asset('storage/' . $docVal);
                                $ext    = strtolower(pathinfo($docVal, PATHINFO_EXTENSION));
                            @endphp
                            @if(in_array($ext, ['jpg','jpeg','png','webp']))
                                <div class="relative group">
                                    <img src="{{ $docUrl }}"
                                         id="preview_{{ $doc['name'] }}"
                                         class="w-full h-28 object-cover rounded-lg mb-2 cursor-zoom-in group-hover:opacity-90 transition"
                                         style="cursor:pointer;width:100%;height:112px;object-fit:cover;border-radius:8px;margin-bottom:8px"
                                         onclick="openZoom('{{ $docUrl }}')">
                                    <div class="absolute top-1 right-1">
                                        <button type="button" onclick="removePreview('{{ $doc['name'] }}')"
                                                class="bg-red-500 text-white rounded-full w-6 h-6 text-xs flex items-center justify-center">×</button>
                                    </div>
                                </div>
                            @else
                                <a href="{{ $docUrl }}" target="_blank"
                                   class="text-sm text-[#1DA1F2] font-semibold hover:underline">{{ $doc['label'] }}</a>
                            @endif
                        @endif
                        <input type="file" name="{{ $doc['name'] }}" accept="image/*"
                               onchange="previewImage(event, 'preview_{{ $doc['name'] }}')"
                               class="text-sm text-gray-500 mt-1 w-full">
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end gap-4 mt-4">
            <a href="{{ route('admin.drivers.index') }}"
               class="px-6 py-2 border rounded-xl font-semibold text-gray-700 hover:bg-gray-100 transition">Annuler</a>
            <button type="submit"
                    class="px-6 py-2 bg-[#1DA1F2] text-white rounded-xl font-semibold hover:bg-[#0d8ce0] transition">Enregistrer</button>
        </div>
    </form>
</div>

<!-- SCRIPTS -->
<script>
function previewImage(event, id){
    const reader = new FileReader();
    reader.onload = function(){
        const output = document.getElementById(id);
        output.src = reader.result;
        output.classList.remove('hidden');
    }
    reader.readAsDataURL(event.target.files[0]);
}

function openZoom(src){
    document.getElementById('zoom-img').src = src;
    document.getElementById('zoom-download').href = src;
    document.getElementById('zoom-modal').style.display = 'flex';
}

function closeZoom(){
    document.getElementById('zoom-modal').style.display = 'none';
}

document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeZoom();
});

function removePreview(id){
    const input = document.querySelector(`input[name=${id}]`);
    input.value = '';
    const img = document.getElementById('preview_' + id);
    if(img) img.src = '';
}
</script>

@endsection