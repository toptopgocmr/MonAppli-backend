@extends('admin.layouts.app')

@section('content')

<!-- HEADER -->
<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">
            👤 Gestion des <span class="text-[#1DA1F2]">Clients</span>
        </h1>
        <p class="text-gray-500 text-sm mt-1">Liste de tous les utilisateurs inscrits</p>
    </div>
</div>

<!-- STATS -->
<div class="grid grid-cols-2 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-5 rounded-2xl shadow-md border-l-4 border-blue-500">
        <p class="text-gray-500 text-sm">Total Clients</p>
        <h2 class="text-3xl font-bold text-blue-500 mt-1">{{ $users->total() }}</h2>
    </div>
    <div class="bg-white p-5 rounded-2xl shadow-md border-l-4 border-green-500">
        <p class="text-gray-500 text-sm">Actifs</p>
        <h2 class="text-3xl font-bold text-green-500 mt-1">{{ \App\Models\User\User::where('status','active')->count() }}</h2>
    </div>
    <div class="bg-white p-5 rounded-2xl shadow-md border-l-4 border-red-500">
        <p class="text-gray-500 text-sm">Bloqués</p>
        <h2 class="text-3xl font-bold text-red-500 mt-1">{{ \App\Models\User\User::where('status','inactive')->count() }}</h2>
    </div>
</div>

<!-- FILTRES -->
<div class="bg-white p-6 rounded-2xl shadow-md mb-6">
    <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap gap-4">

        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Nom, téléphone, email..."
               class="px-4 py-2 border rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none flex-1 min-w-48">

        <select name="status" class="px-4 py-2 border rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none bg-white">
            <option value="">Tous les statuts</option>
            <option value="active"   {{ request('status') == 'active'   ? 'selected' : '' }}>✅ Actifs</option>
            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>🚫 Bloqués</option>
        </select>

        <select name="country" class="px-4 py-2 border rounded-xl focus:ring-2 focus:ring-[#1DA1F2] outline-none bg-white">
            <option value="">Tous les pays</option>
            @foreach($countries as $country)
                <option value="{{ $country }}" {{ request('country') == $country ? 'selected' : '' }}>
                    {{ $country }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="bg-[#1DA1F2] text-white px-6 py-2 rounded-xl hover:bg-[#FFC107] hover:text-black transition">
            Filtrer
        </button>
        <a href="{{ route('admin.users.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-xl hover:bg-gray-300 transition">
            Reset
        </a>
    </form>
</div>

<!-- TABLEAU -->
<div class="bg-white rounded-2xl shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-6 py-4 text-left">Client</th>
                    <th class="px-6 py-4 text-left">Téléphone</th>
                    <th class="px-6 py-4 text-left">Email</th>
                    <th class="px-6 py-4 text-left">Pays / Ville</th>
                    <th class="px-6 py-4 text-left">Statut</th>
                    <th class="px-6 py-4 text-left">Inscrit le</th>
                    <th class="px-6 py-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $user)
                <tr class="hover:bg-gray-50 transition">

                    <!-- Nom -->
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            @if($user->profile_photo)
                                <img src="{{ asset('storage/' . $user->profile_photo) }}"
                                     class="w-9 h-9 rounded-full object-cover border">
                            @else
                                <div class="w-9 h-9 rounded-full bg-[#FFC107] flex items-center justify-center text-black font-bold text-sm">
                                    {{ strtoupper(substr($user->first_name, 0, 1)) }}
                                </div>
                            @endif
                            <p class="font-semibold text-gray-800">{{ $user->first_name }} {{ $user->last_name }}</p>
                        </div>
                    </td>

                    <!-- Téléphone -->
                    <td class="px-6 py-4 text-gray-600">{{ $user->phone }}</td>

                    <!-- Email -->
                    <td class="px-6 py-4 text-gray-600">{{ $user->email ?? '—' }}</td>

                    <!-- Pays / Ville -->
                    <td class="px-6 py-4 text-gray-600">
                        {{ $user->country }}<br>
                        <span class="text-xs text-gray-400">{{ $user->city }}</span>
                    </td>

                    <!-- Statut -->
                    <td class="px-6 py-4">
                        @if($user->status == 'active')
                            <span class="bg-green-100 text-green-700 text-xs font-semibold px-3 py-1 rounded-full">✅ Actif</span>
                        @else
                            <span class="bg-red-100 text-red-700 text-xs font-semibold px-3 py-1 rounded-full">🚫 Bloqué</span>
                        @endif
                    </td>

                    <!-- Date -->
                    <td class="px-6 py-4 text-gray-500 text-xs">
                        {{ $user->created_at->format('d/m/Y') }}
                    </td>

                    <!-- Actions -->
                    <td class="px-6 py-4">
                        <div class="flex justify-center items-center gap-2">

                            <a href="{{ route('admin.users.show', $user->id) }}"
                               class="bg-gray-100 text-gray-700 px-3 py-1 rounded-lg text-xs font-semibold hover:bg-gray-200 transition">
                                👁 Voir
                            </a>

                            @if($user->status == 'active')
                                <form method="POST" action="{{ route('admin.users.block', $user->id) }}">
                                    @csrf
                                    <button type="submit" onclick="return confirm('Bloquer {{ $user->first_name }} ?')"
                                            class="bg-orange-100 text-orange-700 px-3 py-1 rounded-lg text-xs font-semibold hover:bg-orange-200 transition">
                                        🚫 Bloquer
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.users.activate', $user->id) }}">
                                    @csrf
                                    <button type="submit"
                                            class="bg-green-100 text-green-700 px-3 py-1 rounded-lg text-xs font-semibold hover:bg-green-200 transition">
                                        ✅ Activer
                                    </button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Supprimer définitivement {{ $user->first_name }} ?')"
                                        class="bg-red-100 text-red-700 px-3 py-1 rounded-lg text-xs font-semibold hover:bg-red-200 transition">
                                    🗑
                                </button>
                            </form>

                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-10 text-center text-gray-400">
                        Aucun client trouvé.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    @if($users->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $users->appends(request()->query())->links() }}
    </div>
    @endif
</div>

@endsection