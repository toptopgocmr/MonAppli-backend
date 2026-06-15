@extends('company.layouts.app')

@section('title', 'Messages support clients')

@section('content')

<div class="aws-page-header">
    <div class="aws-crumb">
        <a href="{{ route('company.dashboard') }}">Dashboard</a> ›
        <a href="{{ route('company.messages.index') }}">Messages</a> ›
        Support clients
    </div>
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
        <h1 class="aws-page-title">🛠 Messages support — Clients de vos chauffeurs</h1>
        <a href="{{ route('company.messages.index') }}" class="aws-btn aws-btn-normal">
            💬 Messages trajets
        </a>
    </div>
</div>

<div style="display:grid;grid-template-columns:300px 1fr;gap:16px;align-items:start">

    <!-- LISTE CLIENTS -->
    <div class="aws-panel" style="margin-bottom:0">
        <div class="aws-panel-header">
            <span class="aws-panel-title">Clients</span>
        </div>
        <div style="padding:12px">
            <form method="GET" action="{{ route('company.messages.support') }}" style="margin-bottom:10px">
                <input type="text" name="search" class="aws-input" placeholder="Rechercher…"
                       value="{{ request('search') }}">
                @if(request('user_id'))
                    <input type="hidden" name="user_id" value="{{ request('user_id') }}">
                @endif
            </form>
        </div>
        <div style="max-height:560px;overflow-y:auto">
            @forelse($users as $user)
            @php $isSelected = request('user_id') == $user->id; @endphp
            <a href="{{ route('company.messages.support', array_filter(['user_id' => $user->id, 'search' => request('search')])) }}"
               style="
                display:block;padding:10px 16px;
                border-bottom:1px solid var(--aws-border);
                background:{{ $isSelected ? 'rgba(236,114,17,.08)' : 'transparent' }};
                border-left:3px solid {{ $isSelected ? 'var(--aws-orange)' : 'transparent' }};
                text-decoration:none;
                transition:all .12s;
               "
               onmouseover="this.style.background='rgba(236,114,17,.05)'"
               onmouseout="this.style.background='{{ $isSelected ? 'rgba(236,114,17,.08)' : 'transparent' }}'">
                <div style="display:flex;align-items:center;justify-content:space-between">
                    <div>
                        <div style="font-size:13px;font-weight:600;color:var(--aws-header)">
                            {{ $user->first_name }} {{ $user->last_name }}
                        </div>
                        <div style="font-size:11px;color:var(--aws-sub)">{{ $user->phone ?? $user->email }}</div>
                        @if($user->supportMessages->isNotEmpty())
                        <div style="font-size:11px;color:var(--aws-sub);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px">
                            {{ Str::limit($user->supportMessages->first()?->content, 36) }}
                        </div>
                        @endif
                    </div>
                    @if($user->unread_count > 0)
                    <span class="aws-badge aws-badge-red" style="font-size:11px;padding:1px 6px">
                        {{ $user->unread_count }}
                    </span>
                    @endif
                </div>
            </a>
            @empty
            <div style="text-align:center;padding:24px;color:var(--aws-sub);font-size:13px">
                Aucun client trouvé.
            </div>
            @endforelse
        </div>
        @if($users->hasPages())
        <div style="padding:10px 16px;border-top:1px solid var(--aws-border);font-size:12px">
            {{ $users->links() }}
        </div>
        @endif
    </div>

    <!-- CONVERSATION -->
    <div class="aws-panel" style="margin-bottom:0">
        @if($selectedUser)
        <div class="aws-panel-header">
            <div>
                <span class="aws-panel-title">{{ $selectedUser->first_name }} {{ $selectedUser->last_name }}</span>
                <div style="font-size:12px;color:var(--aws-sub);margin-top:2px">{{ $selectedUser->phone ?? $selectedUser->email }}</div>
            </div>
            <span style="font-size:12px;color:var(--aws-sub)">{{ $conversation->count() }} message(s) — lecture seule</span>
        </div>
        <div style="max-height:600px;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:10px"
             id="chat-body">
            @forelse($conversation as $msg)
            @php
                $sType    = (string)($msg->sender_type ?? '');
                $isClient = str_contains($sType, 'User');
            @endphp
            <div style="display:flex;flex-direction:column;align-items:{{ $isClient ? 'flex-end' : 'flex-start' }}">
                <div style="font-size:10px;color:var(--aws-sub);margin-bottom:3px;padding:0 6px">
                    {{ $isClient ? '👤 Client' : '🛠 Support' }}
                    · {{ $msg->created_at?->format('d/m H:i') }}
                </div>
                <div style="
                    max-width:72%;
                    padding:10px 14px;
                    border-radius:{{ $isClient ? '18px 18px 4px 18px' : '18px 18px 18px 4px' }};
                    background:{{ $isClient ? '#ec7211' : '#f2f3f3' }};
                    color:{{ $isClient ? '#fff' : '#16191f' }};
                    font-size:13px;
                    line-height:1.5;
                ">{{ $msg->content }}</div>
            </div>
            @empty
            <div style="text-align:center;padding:40px;color:var(--aws-sub)">
                Aucun message dans cette conversation.
            </div>
            @endforelse
        </div>
        @else
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px 20px;color:var(--aws-sub)">
            <div style="font-size:40px;margin-bottom:16px">🛠</div>
            <div style="font-size:15px;font-weight:600;color:var(--aws-header);margin-bottom:8px">Support clients</div>
            <div style="font-size:13px;text-align:center;max-width:280px">
                Sélectionnez un client dans la liste pour voir sa conversation avec le support TopTopGo.
            </div>
        </div>
        @endif
    </div>

</div>

<script>
    // Scroll auto en bas de la conversation
    const chatBody = document.getElementById('chat-body');
    if (chatBody) chatBody.scrollTop = chatBody.scrollHeight;
</script>

@endsection
