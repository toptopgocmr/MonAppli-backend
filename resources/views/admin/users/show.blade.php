@extends('admin.layouts.app')

@section('content')

<div class="max-w-3xl mx-auto">

    <!-- HEADER -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.users.index') }}" class="text-gray-400 hover:text-gray-700 transition text-2xl">←</a>
            <div>
                <h1 class="text-3xl font-bold text-gray-800">👤 Profil Client</h1>
                <p class="text-gray-500 text-sm mt-1">{{ $user->first_name }} {{ $user->last_name }}</p>
            </div>
        </div>
    </div>

    <!-- CARTE PROFIL -->
    <div class="bg-white rounded-2xl shadow-md p-8 mb-6">

        <div class="flex items-center gap-6 mb-8">
            @if($user->profile_photo)
                <img src="{{ asset('storage/' . $user->profile_photo) }}"
                     class="w-20 h-20 rounded-full object-cover border-4 border-[#FFC107]">
            @else
                <div class="w-20 h-20 rounded-full bg-[#FFC107] flex items-center justify-center text-3xl font-bold text-black">
                    {{ strtoupper(substr($user->first_name, 0, 1)) }}
                </div>
            @endif
            <div>
                <h2 class="text-2xl font-bold text-gray-800">{{ $user->first_name }} {{ $user->last_name }}</h2>
                <p class="text-gray-500">{{ $user->phone }}</p>
                @if($user->status == 'active')
                    <span class="bg-green-100 text-green-700 text-xs font-semibold px-3 py-1 rounded-full mt-2 inline-block">✅ Actif</span>
                @else
                    <span class="bg-red-100 text-red-700 text-xs font-semibold px-3 py-1 rounded-full mt-2 inline-block">🚫 Bloqué</span>
                @endif
            </div>
        </div>

        <!-- DÉTAILS -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-gray-50 p-4 rounded-xl">
                <p class="text-xs text-gray-400 uppercase mb-1">Prénom</p>
                <p class="font-semibold text-gray-800">{{ $user->first_name }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-xl">
                <p class="text-xs text-gray-400 uppercase mb-1">Nom</p>
                <p class="font-semibold text-gray-800">{{ $user->last_name }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-xl">
                <p class="text-xs text-gray-400 uppercase mb-1">Téléphone</p>
                <p class="font-semibold text-gray-800">{{ $user->phone }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-xl">
                <p class="text-xs text-gray-400 uppercase mb-1">Email</p>
                <p class="font-semibold text-gray-800">{{ $user->email ?? '—' }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-xl">
                <p class="text-xs text-gray-400 uppercase mb-1">Pays</p>
                <p class="font-semibold text-gray-800">{{ $user->country }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-xl">
                <p class="text-xs text-gray-400 uppercase mb-1">Ville</p>
                <p class="font-semibold text-gray-800">{{ $user->city }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-xl">
                <p class="text-xs text-gray-400 uppercase mb-1">Inscrit le</p>
                <p class="font-semibold text-gray-800">{{ $user->created_at->format('d/m/Y à H:i') }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-xl">
                <p class="text-xs text-gray-400 uppercase mb-1">Dernière mise à jour</p>
                <p class="font-semibold text-gray-800">{{ $user->updated_at->format('d/m/Y à H:i') }}</p>
            </div>
        </div>
    </div>

    <!-- ACTIONS -->
    <div class="flex gap-4 mb-10">
        @if($user->status == 'active')
            <form method="POST" action="{{ route('admin.users.block', $user->id) }}" class="flex-1">
                @csrf
                <button type="submit" onclick="return confirm('Bloquer {{ $user->first_name }} ?')"
                        class="w-full bg-orange-100 text-orange-700 py-3 rounded-xl font-semibold hover:bg-orange-200 transition">
                    🚫 Bloquer le compte
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.users.activate', $user->id) }}" class="flex-1">
                @csrf
                <button type="submit"
                        class="w-full bg-green-100 text-green-700 py-3 rounded-xl font-semibold hover:bg-green-200 transition">
                    ✅ Activer le compte
                </button>
            </form>
        @endif

        <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" class="flex-1">
            @csrf
            @method('DELETE')
            <button type="submit" onclick="return confirm('Supprimer définitivement {{ $user->first_name }} ?')"
                    class="w-full bg-red-100 text-red-700 py-3 rounded-xl font-semibold hover:bg-red-200 transition">
                🗑 Supprimer
            </button>
        </form>

        <a href="{{ route('admin.users.index') }}" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold text-center hover:bg-gray-200 transition">
            ← Retour
        </a>
    </div>

</div>

@endsection