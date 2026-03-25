@extends('admin.layouts.app')

@section('content')

<div class="max-w-2xl mx-auto">

    <!-- HEADER -->
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.profiles.index') }}"
           class="text-gray-400 hover:text-gray-700 transition text-2xl">←</a>
        <div>
            <h1 class="text-3xl font-bold text-gray-800">➕ Nouvel Administrateur</h1>
            <p class="text-gray-500 text-sm mt-1">Créer un nouveau compte administrateur</p>
        </div>
    </div>

    <!-- FORMULAIRE -->
    <div class="bg-white rounded-2xl shadow-md p-8">

        @if ($errors->any())
            <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-xl mb-6 text-sm">
                @foreach ($errors->all() as $error)
                    <p>• {{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.profiles.store') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Prénom -->
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Prénom *</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="Jean">
                </div>

                <!-- Nom -->
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Nom *</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="Dupont">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="jean@toptopgo.com">
                </div>

                <!-- Téléphone -->
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Téléphone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="+237 6XX XXX XXX">
                </div>

                <!-- Rôle -->
                <div class="md:col-span-2">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Rôle *</label>
                    <select name="role_id" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition bg-white">
                        <option value="">-- Sélectionner un rôle --</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Mot de passe -->
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Mot de passe *</label>
                    <input type="password" name="password" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="Minimum 8 caractères">
                </div>

                <!-- Confirmation mot de passe -->
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Confirmer le mot de passe *</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none transition"
                           placeholder="Répéter le mot de passe">
                </div>

            </div>

            <!-- BOUTONS -->
            <div class="flex gap-4 mt-8">
                <button type="submit"
                        class="flex-1 bg-[#1DA1F2] text-white py-3 rounded-xl font-semibold
                               hover:bg-[#FFC107] hover:text-black transition-all duration-300">
                    ✅ Enregistrer
                </button>
                <a href="{{ route('admin.profiles.index') }}"
                   class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold text-center
                          hover:bg-gray-200 transition">
                    Annuler
                </a>
            </div>

        </form>
    </div>
</div>

@endsection