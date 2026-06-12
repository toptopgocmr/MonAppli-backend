@extends('admin.layouts.app')
@section('title','Support Chauffeurs')

@section('content')
<div style="display:flex;flex-direction:column;gap:16px">

    <div>
        <h1 class="page-title">🛡️ Support Admin ↔ Chauffeurs</h1>
        <p class="page-sub">Écrivez à n'importe quel chauffeur depuis cette interface</p>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px">
        <div class="stat-card" style="border-left:4px solid #1DA1F2">
            <div class="lbl">Conversations actives</div>
            <div class="val" style="color:#1DA1F2">{{ $totalConversations }}</div>
        </div>
        <div class="stat-card" style="border-left:4px solid #22c55e">
            <div class="lbl">Total Messages envoyés</div>
            <div class="val" style="color:#16a34a">{{ $totalMessages }}</div>
        </div>
        <div class="stat-card" style="border-left:4px solid #f59e0b">
            <div class="lbl">Non lus</div>
            <div class="val unread-badge" style="color:#d97706">{{ $unreadMessages }}</div>
        </div>
    </div>

    <div class="filter-bar">
        <form method="GET" action="{{ route('admin.support.drivers.index') }}" style="display:flex;gap:10px;align-items:center;width:100%">
            <div style="flex:1">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Rechercher par nom ou téléphone..." class="ttg-input">
            </div>
            <button type="submit" class="btn btn-primary">Rechercher</button>
            <a href="{{ route('admin.support.drivers.index') }}" class="btn btn-gray">✕ Reset</a>
        </form>
    </div>

    {{-- ===== CORPS PRINCIPAL ===== --}}
    <div class="flex gap-4" style="height: 65vh;">

        {{-- ---- SIDEBAR : TOUS LES CHAUFFEURS ---- --}}
        <div class="w-1/3 bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gray-50">
                <h2 class="font-semibold text-gray-700 text-sm">
                    🚗 Tous les chauffeurs
                    <span class="ml-2 bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded-full">
                        {{ $drivers->total() }}
                    </span>
                </h2>
                <p class="text-xs text-gray-400 mt-0.5">Cliquez sur un chauffeur pour lui écrire</p>
            </div>

            <div class="overflow-y-auto flex-1" id="driversList">
                @forelse($drivers as $d)
                    @php
                        $isActive = isset($driver) && $driver->id === $d->id;
                        $hasMsg   = $d->supportMessages->isNotEmpty();
                        $lastMsg  = $d->supportMessages->first();
                        $params   = array_filter(['search' => request('search')]);
                    @endphp

                    <a href="{{ route('admin.support.drivers.show', array_merge(['driver' => $d->id], $params)) }}"
                        class="driver-item block p-4 border-b border-gray-50 hover:bg-blue-50 transition
                               {{ $isActive ? 'bg-blue-50 border-l-4 border-l-blue-500' : '' }}"
                        data-driver-id="{{ $d->id }}">

                        <div class="flex items-center gap-3">
                            <div class="relative flex-shrink-0">
                                @if($d->profile_photo)
                                    <img src="{{ asset('storage/' . $d->profile_photo) }}"
                                         class="w-10 h-10 rounded-full object-cover">
                                @else
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold
                                        {{ $hasMsg ? 'bg-green-200 text-green-800' : 'bg-gray-100 text-gray-500' }}">
                                        {{ strtoupper(substr($d->first_name ?? 'D', 0, 1)) }}
                                    </div>
                                @endif
                                @if($hasMsg)
                                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-400 border-2 border-white rounded-full"></span>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-gray-800 truncate">
                                        {{ $d->first_name }} {{ $d->last_name }}
                                    </span>
                                    @if($d->unread_count > 0)
                                        <span class="driver-unread-badge bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full ml-2 flex-shrink-0"
                                              data-driver-id="{{ $d->id }}">
                                            {{ $d->unread_count }}
                                        </span>
                                    @endif
                                </div>

                                <div class="text-xs text-gray-400 truncate">
                                    {{ $d->phone ?? '—' }}
                                    @if($d->vehicle_brand)
                                        • {{ $d->vehicle_brand }} {{ $d->vehicle_model }}
                                    @endif
                                </div>

                                @if($lastMsg)
                                    <div class="text-xs text-gray-500 mt-0.5 truncate driver-last-msg" data-driver-id="{{ $d->id }}">
                                        {{ \Illuminate\Support\Str::limit($lastMsg->content, 38) }}
                                    </div>
                                    <div class="text-xs text-gray-300">
                                        {{ $lastMsg->created_at->diffForHumans() }}
                                    </div>
                                @else
                                    <div class="text-xs text-gray-300 mt-0.5 italic driver-last-msg" data-driver-id="{{ $d->id }}">
                                        Aucun message — cliquez pour écrire
                                    </div>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-8 text-center text-gray-400">
                        <div class="text-4xl mb-2">🚗</div>
                        <p class="text-sm">Aucun chauffeur trouvé</p>
                    </div>
                @endforelse
            </div>

            @if($drivers->hasPages())
                <div class="p-3 border-t border-gray-100 text-center">
                    {{ $drivers->appends(request()->query())->links('pagination::simple-tailwind') }}
                </div>
            @endif
        </div>

        {{-- ---- ZONE MESSAGES ---- --}}
        <div class="flex-1 bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col overflow-hidden">

            @if(isset($driver) && isset($messages))

                {{-- Header --}}
                <div class="p-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        @if($driver->profile_photo)
                            <img src="{{ asset('storage/' . $driver->profile_photo) }}"
                                 class="w-10 h-10 rounded-full object-cover">
                        @else
                            <div class="w-10 h-10 rounded-full bg-green-200 text-green-800 flex items-center justify-center text-sm font-bold">
                                {{ strtoupper(substr($driver->first_name ?? 'D', 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <div class="font-semibold text-gray-800">
                                {{ $driver->first_name }} {{ $driver->last_name }}
                            </div>
                            <div class="text-xs text-gray-400">
                                {{ $driver->phone ?? '—' }}
                                @if($driver->vehicle_brand)
                                    • {{ $driver->vehicle_brand }} {{ $driver->vehicle_model }}
                                @endif
                                • <span id="msgCount">{{ $messages->count() }}</span> message(s)
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2 text-xs">
                        @if($driver->phone)
                            <span class="bg-green-50 text-green-700 px-2 py-1 rounded">
                                📱 {{ $driver->phone }}
                            </span>
                        @endif
                        @if($driver->vehicle_plate)
                            <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded">
                                🚗 {{ $driver->vehicle_plate }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Liste messages --}}
                <div class="flex-1 overflow-y-auto p-5 space-y-4 bg-gray-50" id="messagesBox">
                    @forelse($messages as $message)
                        @php
                            $isFromAdmin = $message->sender_type === \App\Models\Admin\AdminUser::class
                                       || $message->sender_type === 'App\Models\Admin\AdminUser';
                        @endphp

                        @if($isFromAdmin)
                            <div class="flex justify-end items-end gap-2">
                                <div class="max-w-xs lg:max-w-md">
                                    <div class="text-xs text-gray-400 mb-1 text-right">
                                        🛡 {{ session('admin_name', 'Admin') }}
                                    </div>
                                    <div class="px-4 py-2.5 rounded-2xl rounded-tr-none text-sm leading-relaxed bg-blue-600 text-white shadow-sm">
                                        {{ $message->content }}
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1 text-right">
                                        {{ $message->created_at->format('d/m H:i') }}
                                        @if($message->is_read)
                                            <span class="text-blue-400 ml-1">✓✓ Lu</span>
                                        @else
                                            <span class="text-gray-300 ml-1">✓ Envoyé</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="w-8 h-8 rounded-full bg-yellow-400 text-black flex items-center justify-center text-xs font-bold flex-shrink-0">
                                    {{ strtoupper(substr(session('admin_name', 'A'), 0, 1)) }}
                                </div>
                            </div>
                        @else
                            <div class="flex justify-start items-end gap-2">
                                <div class="w-8 h-8 rounded-full bg-green-200 text-green-800 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                    {{ strtoupper(substr($driver->first_name ?? 'D', 0, 1)) }}
                                </div>
                                <div class="max-w-xs lg:max-w-md">
                                    <div class="text-xs text-gray-400 mb-1 text-left">
                                        🚗 {{ $driver->first_name }} {{ $driver->last_name }}
                                    </div>
                                    <div class="px-4 py-2.5 rounded-2xl rounded-tl-none text-sm leading-relaxed bg-white text-gray-800 shadow-sm border border-gray-200">
                                        {{ $message->content }}
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1 text-left">
                                        {{ $message->created_at->format('d/m H:i') }}
                                        @if($message->is_read)
                                            <span class="text-green-400 ml-1">✓✓ Lu</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="text-center text-gray-400 py-10">
                            <div class="text-4xl mb-3">✉️</div>
                            <p class="font-medium text-gray-500">Démarrez la conversation</p>
                            <p class="text-sm mt-1">
                                Écrivez votre premier message à
                                <span class="font-semibold text-green-600">{{ $driver->first_name }}</span>
                                ci-dessous
                            </p>
                        </div>
                    @endforelse
                </div>

                {{-- Formulaire envoi --}}
                <div class="p-4 border-t border-gray-100 bg-white">
                    <form method="POST" action="{{ route('admin.support.drivers.send', $driver->id) }}"
                          class="flex gap-3 items-end">
                        @csrf
                        @if(request('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                        <div class="flex-1">
                            <textarea name="content" rows="2"
                                placeholder="Écrire un message à {{ $driver->first_name }}..."
                                class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm resize-none
                                       focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required></textarea>
                        </div>
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-medium transition flex-shrink-0">
                            Envoyer ➤
                        </button>
                    </form>
                </div>

            @else
                <div class="flex-1 flex items-center justify-center text-gray-400">
                    <div class="text-center">
                        <div class="text-6xl mb-4">🚗</div>
                        <p class="text-lg font-medium text-gray-500">Sélectionnez un chauffeur</p>
                        <p class="text-sm mt-1">Cliquez sur n'importe quel chauffeur dans la liste pour lui écrire</p>
                    </div>
                </div>
            @endif

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
// Scroll automatique en bas
const box = document.getElementById('messagesBox');
if (box) box.scrollTop = box.scrollHeight;

// Demander permission notifications navigateur
if (Notification.permission === 'default') {
    Notification.requestPermission();
}

// Son de notification (bip simple sans fichier externe)
function playSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const o = ctx.createOscillator();
        const g = ctx.createGain();
        o.connect(g);
        g.connect(ctx.destination);
        o.type = 'sine';
        o.frequency.setValueAtTime(880, ctx.currentTime);
        g.gain.setValueAtTime(0.3, ctx.currentTime);
        g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
        o.start(ctx.currentTime);
        o.stop(ctx.currentTime + 0.5);
    } catch(e) {}
}

@if(isset($driver))
// Connexion Pusher
const pusher = new Pusher('{{ env("PUSHER_APP_KEY") }}', {
    cluster: '{{ env("PUSHER_APP_CLUSTER", "eu") }}',
    forceTLS: true
});

const channel = pusher.subscribe('admin-support');

channel.bind('message.received', function(data) {
    // On vérifie que le message vient bien du chauffeur actuellement affiché
    if (parseInt(data.sender_id) !== {{ $driver->id }}) {
        // Message d'un autre chauffeur : mettre à jour son badge dans la sidebar
        const badge = document.querySelector('.driver-unread-badge[data-driver-id="' + data.sender_id + '"]');
        if (badge) {
            badge.textContent = parseInt(badge.textContent || 0) + 1;
        } else {
            // Créer le badge s'il n'existe pas
            const driverItem = document.querySelector('.driver-item[data-driver-id="' + data.sender_id + '"]');
            if (driverItem) {
                const nameSpan = driverItem.querySelector('.text-sm.font-semibold');
                if (nameSpan) {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'driver-unread-badge bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full ml-2 flex-shrink-0';
                    newBadge.setAttribute('data-driver-id', data.sender_id);
                    newBadge.textContent = '1';
                    nameSpan.parentNode.appendChild(newBadge);
                }
            }
        }
        // Mettre à jour l'aperçu du dernier message dans la sidebar
        const lastMsgEl = document.querySelector('.driver-last-msg[data-driver-id="' + data.sender_id + '"]');
        if (lastMsgEl) {
            lastMsgEl.textContent = data.content.substring(0, 38);
        }
        // Jouer le son même si c'est un autre chauffeur
        playSound();
        return;
    }

    // Message du chauffeur affiché : ajouter dans la zone de chat
    const messagesBox = document.getElementById('messagesBox');
    if (messagesBox) {
        const driverInitial = '{{ strtoupper(substr($driver->first_name ?? "D", 0, 1)) }}';
        const driverName = '{{ $driver->first_name }} {{ $driver->last_name }}';

        const div = document.createElement('div');
        div.className = 'flex justify-start items-end gap-2 new-msg';
        div.innerHTML = `
            <div class="w-8 h-8 rounded-full bg-green-200 text-green-800 flex items-center justify-center text-xs font-bold flex-shrink-0">
                ${driverInitial}
            </div>
            <div class="max-w-xs lg:max-w-md">
                <div class="text-xs text-gray-400 mb-1 text-left">🚗 ${driverName}</div>
                <div class="px-4 py-2.5 rounded-2xl rounded-tl-none text-sm leading-relaxed bg-white text-gray-800 shadow-sm border border-gray-200">
                    ${data.content}
                </div>
                <div class="text-xs text-gray-400 mt-1 text-left">${data.created_at}</div>
            </div>`;

        messagesBox.appendChild(div);
        messagesBox.scrollTop = messagesBox.scrollHeight;

        // Mettre à jour le compteur de messages
        const msgCount = document.getElementById('msgCount');
        if (msgCount) msgCount.textContent = parseInt(msgCount.textContent) + 1;
    }

    // Mettre à jour le badge "Non lus" global
    const globalBadge = document.querySelector('.unread-badge');
    if (globalBadge) {
        globalBadge.textContent = parseInt(globalBadge.textContent || 0) + 1;
    }

    // Son
    playSound();

    // Notification navigateur
    if (Notification.permission === 'granted') {
        new Notification('🚖 Nouveau message de {{ $driver->first_name }}', {
            body: data.content,
            icon: '/favicon.ico'
        });
    }
});
@endif
</script>

<style>
.new-msg {
    animation: fadeUp 0.3s ease;
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
@endpush