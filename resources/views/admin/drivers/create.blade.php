@extends('admin.layouts.app')

@section('content')

<div class="max-w-5xl mx-auto">

    <!-- HEADER -->
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.drivers.index') }}"
           class="text-gray-400 hover:text-gray-700 transition text-2xl">←</a>
        <div>
            <h1 class="text-3xl font-bold text-gray-800">🚗 Nouveau Chauffeur</h1>
            <p class="text-gray-500 text-sm mt-1">Ajouter un chauffeur manuellement</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-xl mb-6 text-sm">
            @foreach ($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.drivers.store') }}" enctype="multipart/form-data">
        @csrf

        {{-- ===== 1. INFORMATIONS PERSONNELLES ===== --}}
        <div class="bg-white rounded-2xl shadow-md p-8 mb-6">
            <h2 class="text-lg font-bold text-gray-700 mb-6 pb-3 border-b border-gray-100">
                👤 Informations personnelles
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Prénom *</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="Jean">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Nom *</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="Dupont">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Date de naissance *</label>
                    <input type="date" name="birth_date" value="{{ old('birth_date') }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Lieu de naissance *</label>
                    <input type="text" name="birth_place" value="{{ old('birth_place') }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="Douala">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Pays de naissance *</label>
                    <input type="text" name="country_birth" value="{{ old('country_birth') }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="Cameroun">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Téléphone *</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="+237 6XX XXX XXX">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Mot de passe *</label>
                    <input type="password" name="password" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="Minimum 8 caractères">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Confirmer le mot de passe *</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="Répéter le mot de passe">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Photo de profil</label>
                    <input type="file" name="profile_photo" accept="image/*"
                           onchange="previewImage(event, 'preview_photo')"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition">
                    <img id="preview_photo" class="hidden mt-3 w-24 h-24 rounded-full object-cover border-2 border-[#1DA1F2]">
                </div>

            </div>
        </div>

        {{-- ===== 2. CARTE D'IDENTITÉ ===== --}}
        <div class="bg-white rounded-2xl shadow-md p-8 mb-6">
            <h2 class="text-lg font-bold text-gray-700 mb-6 pb-3 border-b border-gray-100">
                🪪 Carte d'identité
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Date de délivrance</label>
                    <input type="date" name="id_card_issue_date" value="{{ old('id_card_issue_date') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Date d'expiration</label>
                    <input type="date" name="id_card_expiry_date" value="{{ old('id_card_expiry_date') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Ville de délivrance</label>
                    <input type="text" name="id_card_issue_city" value="{{ old('id_card_issue_city') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="Douala">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Pays de délivrance</label>
                    <input type="text" name="id_card_issue_country" value="{{ old('id_card_issue_country') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="Cameroun">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">CNI Recto</label>
                    <input type="file" name="id_card_front" accept="image/*,.pdf"
                           onchange="previewImage(event, 'preview_id_front')"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl outline-none transition">
                    <img id="preview_id_front" class="hidden mt-2 h-20 rounded-lg object-cover border">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">CNI Verso</label>
                    <input type="file" name="id_card_back" accept="image/*,.pdf"
                           onchange="previewImage(event, 'preview_id_back')"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl outline-none transition">
                    <img id="preview_id_back" class="hidden mt-2 h-20 rounded-lg object-cover border">
                </div>

            </div>
        </div>

        {{-- ===== 3. PERMIS DE CONDUIRE ===== --}}
        <div class="bg-white rounded-2xl shadow-md p-8 mb-6">
            <h2 class="text-lg font-bold text-gray-700 mb-6 pb-3 border-b border-gray-100">
                📋 Permis de conduire
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Date de délivrance</label>
                    <input type="date" name="license_issue_date" value="{{ old('license_issue_date') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Date d'expiration</label>
                    <input type="date" name="license_expiry_date" value="{{ old('license_expiry_date') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Ville de délivrance</label>
                    <input type="text" name="license_issue_city" value="{{ old('license_issue_city') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="Yaoundé">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Pays de délivrance</label>
                    <input type="text" name="license_issue_country" value="{{ old('license_issue_country') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="Cameroun">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Permis Recto</label>
                    <input type="file" name="license_front" accept="image/*,.pdf"
                           onchange="previewImage(event, 'preview_license_front')"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl outline-none transition">
                    <img id="preview_license_front" class="hidden mt-2 h-20 rounded-lg object-cover border">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Permis Verso</label>
                    <input type="file" name="license_back" accept="image/*,.pdf"
                           onchange="previewImage(event, 'preview_license_back')"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl outline-none transition">
                    <img id="preview_license_back" class="hidden mt-2 h-20 rounded-lg object-cover border">
                </div>

            </div>
        </div>

        {{-- ===== 4. VÉHICULE ===== --}}
        <div class="bg-white rounded-2xl shadow-md p-8 mb-6">
            <h2 class="text-lg font-bold text-gray-700 mb-6 pb-3 border-b border-gray-100">
                🚗 Informations du véhicule
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Plaque d'immatriculation</label>
                    <input type="text" name="vehicle_plate" value="{{ old('vehicle_plate') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="LT 1234 A">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Marque</label>
                    <input type="text" name="vehicle_brand" value="{{ old('vehicle_brand') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="Toyota">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Modèle</label>
                    <input type="text" name="vehicle_model" value="{{ old('vehicle_model') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="Corolla">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Type de véhicule</label>
                    <select name="vehicle_type"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition bg-white">
                        <option value="">-- Sélectionner --</option>
                        <option value="Standard"  {{ old('vehicle_type') == 'Standard'  ? 'selected' : '' }}>Standard</option>
                        <option value="Confort"   {{ old('vehicle_type') == 'Confort'   ? 'selected' : '' }}>Confort</option>
                        <option value="Van"       {{ old('vehicle_type') == 'Van'       ? 'selected' : '' }}>Van</option>
                        <option value="PMR"       {{ old('vehicle_type') == 'PMR'       ? 'selected' : '' }}>PMR</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Couleur</label>
                    <input type="text" name="vehicle_color" value="{{ old('vehicle_color') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="Blanc">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Pays d'immatriculation</label>
                    <input type="text" name="vehicle_country" value="{{ old('vehicle_country') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="Cameroun">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Ville</label>
                    <input type="text" name="vehicle_city" value="{{ old('vehicle_city') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="Douala">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Carte grise</label>
                    <input type="file" name="vehicle_registration" accept="image/*,.pdf"
                           onchange="previewImage(event, 'preview_registration')"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl outline-none transition">
                    <img id="preview_registration" class="hidden mt-2 h-20 rounded-lg object-cover border">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Assurance</label>
                    <input type="file" name="insurance" accept="image/*,.pdf"
                           onchange="previewImage(event, 'preview_insurance')"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl outline-none transition">
                    <img id="preview_insurance" class="hidden mt-2 h-20 rounded-lg object-cover border">
                </div>

            </div>
        </div>

        {{-- ===== 5. STATUT ===== --}}
        <div class="bg-white rounded-2xl shadow-md p-8 mb-6">
            <h2 class="text-lg font-bold text-gray-700 mb-6 pb-3 border-b border-gray-100">
                ⚙️ Statut du compte
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Statut KYC</label>
                    <select name="status"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition bg-white">
                        <option value="pending"   {{ old('status', 'pending') == 'pending'   ? 'selected' : '' }}>⏳ En attente</option>
                        <option value="approved"  {{ old('status') == 'approved'  ? 'selected' : '' }}>✅ Approuvé</option>
                        <option value="rejected"  {{ old('status') == 'rejected'  ? 'selected' : '' }}>❌ Rejeté</option>
                        <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>🚫 Suspendu</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Statut de conduite</label>
                    <select name="driver_status"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition bg-white">
                        <option value="offline" {{ old('driver_status', 'offline') == 'offline' ? 'selected' : '' }}>⚫ Hors ligne</option>
                        <option value="online"  {{ old('driver_status') == 'online'  ? 'selected' : '' }}>🟢 En ligne</option>
                        <option value="pause"   {{ old('driver_status') == 'pause'   ? 'selected' : '' }}>🟡 En pause</option>
                    </select>
                </div>

            </div>
        </div>

        {{-- ===== BOUTONS ===== --}}
        <div class="flex gap-4 mb-10">
            <button type="submit"
                    class="flex-1 bg-[#1DA1F2] text-white py-4 rounded-xl font-semibold text-lg
                           hover:bg-[#FFC107] hover:text-black transition-all duration-300">
                🚗 Créer le chauffeur
            </button>
            <a href="{{ route('admin.drivers.index') }}"
               class="flex-1 bg-gray-100 text-gray-700 py-4 rounded-xl font-semibold text-lg text-center
                      hover:bg-gray-200 transition">
                Annuler
            </a>
        </div>

    </form>
</div>

@endsection