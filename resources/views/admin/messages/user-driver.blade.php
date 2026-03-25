@extends('admin.layouts.app')

@section('content')
<div class="p-6">

    {{-- ===== HEADER ===== --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">💬 Conversations Users ↔ Chauffeurs</h1>
            <p class="text-sm text-gray-500 mt-1">Toutes les conversations entre utilisateurs et chauffeurs</p>
        </div>
    </div>

    {{-- ===== STATS ===== --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-sm text-gray-500 mb-1">Total Messages</div>
            <div class="text-3xl font-bold text-blue-600">{{ $totalMessages }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-sm text-gray-500 mb-1">Non lus</div>
            <div class="text-3xl font-bold text-orange-500">{{ $unreadMessages }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-sm text-gray-500 mb-1">Conversations actives</div>
            <div class="text-3xl font-bold text-green-600">{{ $totalTripsWithMessages }}</div>
        </div>
    </div>

    {{-- ===== FILTRES ===== --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
        <form method="GET" action="{{ route('admin.messages.index') }}" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    👤 Filtrer par Utilisateur
                </label>
                <select name="user_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Tous les utilisateurs --</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->first_name }} {{ $u->last_name }}
                            @if($u->phone) ({{ $u->phone }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    🚗 Filtrer par Chauffeur
                </label>
                <select name="driver_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Tous les chauffeurs --</option>
                    @foreach($drivers as $d)
                        <option value="{{ $d->id }}" {{ request('driver_id') == $d->id ? 'selected' : '' }}>
                            {{ $d->first_name }} {{ $d->last_name }}
                            @if($d->phone) ({{ $d->phone }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium transition">
                    🔍 Filtrer
                </button>
                <a href="{{ route('admin.messages.index') }}"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-lg text-sm font-medium transition">
                    ✕ Reset
                </a>
            </div>
        </form>
    </div>

    {{-- ===== CORPS PRINCIPAL ===== --}}
    <div class="flex gap-4" style="height: 65vh;">

        {{-- ---- LISTE DES CONVERSATIONS (sidebar) ---- --}}
        <div class="w-1/3 bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gray-50">
                <h2 class="font-semibold text-gray-700 text-sm">
                    📋 Conversations
                    <span class="ml-2 bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded-full">
                        {{ $trips->total() }}
                    </span>
                </h2>
            </div>

            <div class="overflow-y-auto flex-1">
                @forelse($trips as $t)
                    @php
                        $isActive = isset($trip) && $trip->id === $t->id;
                        $lastMsg  = $t->messages->first();
                        $params   = array_filter(['user_id' => request('user_id'), 'driver_id' => request('driver_id')]);
                    @endphp

                    <a href="{{ route('admin.messages.show', array_merge(['trip' => $t->id], $params)) }}"
                        class="block p-4 border-b border-gray-50 hover:bg-blue-50 transition
                               {{ $isActive ? 'bg-blue-50 border-l-4 border-l-blue-500' : '' }}">

                        {{-- Avatars --}}
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">
                                {{ strtoupper(substr($t->user->first_name ?? 'U', 0, 1)) }}
                            </div>
                            <span class="text-xs text-gray-400">↔</span>
                            <div class="w-7 h-7 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-xs font-bold">
                                {{ strtoupper(substr($t->driver->first_name ?? 'D', 0, 1)) }}
                            </div>
                        </div>

                        {{-- Noms --}}
                        <div class="text-sm font-semibold text-gray-800">
                            <span class="text-indigo-600">{{ $t->user->first_name ?? 'User supprimé' }}</span>
                            <span class="text-gray-400 mx-1">↔</span>
                            <span class="text-green-600">{{ $t->driver->first_name ?? 'Driver supprimé' }}</span>
                        </div>

                        {{-- Trip ID + aperçu --}}
                        <div class="text-xs text-gray-400 mt-0.5">Trip #{{ $t->id }}</div>
                        @if($lastMsg)
                            <div class="text-xs text-gray-500 mt-1 truncate">
                                {{ Str::limit($lastMsg->content, 45) }}
                            </div>
                            <div class="text-xs text-gray-300 mt-0.5">
                                {{ $lastMsg->created_at->diffForHumans() }}
                            </div>
                        @endif
                    </a>
                @empty
                    <div class="p-8 text-center text-gray-400">
                        <div class="text-4xl mb-2">💬</div>
                        <p class="text-sm">Aucune conversation trouvée</p>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if($trips->hasPages())
                <div class="p-3 border-t border-gray-100 text-center">
                    {{ $trips->appends(request()->query())->links('pagination::simple-tailwind') }}
                </div>
            @endif
        </div>

        {{-- ---- ZONE MESSAGES ---- --}}
        <div class="flex-1 bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col overflow-hidden">

            @if(isset($trip) && isset($messages))

                {{-- Header conversation --}}
                <div class="p-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex -space-x-2">
                            <div class="w-9 h-9 rounded-full bg-indigo-200 text-indigo-800 flex items-center justify-center text-sm font-bold ring-2 ring-white">
                                {{ strtoupper(substr($trip->user->first_name ?? 'U', 0, 1)) }}
                            </div>
                            <div class="w-9 h-9 rounded-full bg-green-200 text-green-800 flex items-center justify-center text-sm font-bold ring-2 ring-white">
                                {{ strtoupper(substr($trip->driver->first_name ?? 'D', 0, 1)) }}
                            </div>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-800">
                                <span class="text-indigo-600">{{ $trip->user->first_name ?? 'User supprimé' }} {{ $trip->user->last_name ?? '' }}</span>
                                <span class="text-gray-400 mx-1">↔</span>
                                <span class="text-green-600">{{ $trip->driver->first_name ?? 'Driver supprimé' }} {{ $trip->driver->last_name ?? '' }}</span>
                            </div>
                            <div class="text-xs text-gray-400">
                                Trip #{{ $trip->id }} •
                                {{ $messages->count() }} message(s)
                            </div>
                        </div>
                    </div>

                    {{-- Infos contacts --}}
                    <div class="flex gap-3 text-xs">
                        @if(isset($trip->user) && $trip->user->phone)
                            <span class="bg-indigo-50 text-indigo-700 px-2 py-1 rounded">
                                👤 {{ $trip->user->phone }}
                            </span>
                        @endif
                        @if(isset($trip->driver) && $trip->driver->phone)
                            <span class="bg-green-50 text-green-700 px-2 py-1 rounded">
                                🚗 {{ $trip->driver->phone }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Messages --}}
                <div class="flex-1 overflow-y-auto p-5 space-y-4 bg-gray-50" id="messagesBox">
                    @forelse($messages as $message)
                        @php
                            $isUser = str_contains($message->sender_type, 'User');
                        @endphp

                        <div class="flex {{ $isUser ? 'justify-start' : 'justify-end' }} items-end gap-2">

                            @if($isUser)
                                <div class="w-8 h-8 rounded-full bg-indigo-200 text-indigo-800 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                    {{ strtoupper(substr($trip->user->first_name ?? 'U', 0, 1)) }}
                                </div>
                            @endif

                            <div class="max-w-xs lg:max-w-md">
                                <div class="text-xs text-gray-400 mb-1 {{ $isUser ? 'text-left' : 'text-right' }}">
                                    @if($isUser)
                                        👤 {{ $trip->user->first_name ?? 'User' }}
                                    @else
                                        🚗 {{ $trip->driver->first_name ?? 'Driver' }}
                                    @endif
                                </div>

                                <div class="px-4 py-2.5 rounded-2xl text-sm leading-relaxed
                                    {{ $isUser
                                        ? 'bg-white border border-gray-200 text-gray-800 rounded-tl-none shadow-sm'
                                        : 'bg-blue-600 text-white rounded-tr-none shadow-sm' }}">
                                    {{ $message->content }}
                                </div>

                                <div class="text-xs text-gray-400 mt-1 {{ $isUser ? 'text-left' : 'text-right' }}">
                                    {{ $message->created_at->format('H:i') }}
                                    @if(!$isUser)
                                        @if($message->is_read)
                                            <span class="text-blue-400 ml-1">✓✓</span>
                                        @else
                                            <span class="text-gray-300 ml-1">✓</span>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            @if(!$isUser)
                                <div class="w-8 h-8 rounded-full bg-green-200 text-green-800 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                    {{ strtoupper(substr($trip->driver->first_name ?? 'D', 0, 1)) }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center text-gray-400 py-10">
                            <div class="text-4xl mb-2">📭</div>
                            <p>Aucun message dans cette conversation</p>
                        </div>
                    @endforelse
                </div>

                {{-- Note admin lecture seule --}}
                <div class="p-3 border-t border-gray-100 bg-gray-50 text-center">
                    <span class="text-xs text-gray-400 italic">
                        🔒 Vue en lecture seule — Interface d'administration
                    </span>
                </div>

            @else
                {{-- Aucune conversation sélectionnée --}}
                <div class="flex-1 flex items-center justify-center text-gray-400">
                    <div class="text-center">
                        <div class="text-6xl mb-4">💬</div>
                        <p class="text-lg font-medium text-gray-500">Sélectionnez une conversation</p>
                        <p class="text-sm mt-1">Cliquez sur une conversation dans la liste pour voir les messages</p>
                    </div>
                </div>
            @endif

        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    const box = document.getElementById('messagesBox');
    if (box) {
        box.scrollTop = box.scrollHeight;
    }

    @if(isset($trip))
    setInterval(function () {
        location.reload();
    }, 10000);
    @endif
</script>
@endsection