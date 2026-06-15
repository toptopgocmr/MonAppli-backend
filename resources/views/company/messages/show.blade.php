@extends('company.layouts.app')

@section('title', 'Conversation — Trajet #' . $trip->id)

@section('content')

<div class="aws-page-header">
    <div class="aws-crumb">
        <a href="{{ route('company.dashboard') }}">Dashboard</a> ›
        <a href="{{ route('company.messages.index') }}">Messages</a> ›
        Trajet #{{ $trip->id }}
    </div>
    <h1 class="aws-page-title">💬 {{ $trip->departure }} → {{ $trip->destination }}</h1>
</div>

<!-- INFOS TRAJET -->
<div class="aws-panel" style="margin-bottom:16px">
    <div class="aws-panel-header">
        <span class="aws-panel-title">Détails du trajet</span>
        <a href="{{ route('company.messages.index') }}" class="aws-btn aws-btn-normal" style="font-size:12px;padding:4px 12px">← Retour</a>
    </div>
    <div class="aws-panel-body">
        <div class="aws-detail-grid">
            <div class="aws-detail-item">
                <div class="aws-detail-label">Trajet</div>
                <div class="aws-detail-value">{{ $trip->departure }} → {{ $trip->destination }}</div>
            </div>
            <div class="aws-detail-item">
                <div class="aws-detail-label">Date</div>
                <div class="aws-detail-value">{{ $trip->departure_date?->format('d/m/Y') }}</div>
            </div>
            <div class="aws-detail-item">
                <div class="aws-detail-label">Chauffeur</div>
                <div class="aws-detail-value">
                    {{ $trip->driver?->first_name }} {{ $trip->driver?->last_name }}
                    <div style="font-size:12px;color:var(--aws-sub)">{{ $trip->driver?->phone }}</div>
                </div>
            </div>
            <div class="aws-detail-item">
                <div class="aws-detail-label">Client</div>
                <div class="aws-detail-value">
                    {{ $trip->user?->first_name }} {{ $trip->user?->last_name }}
                    <div style="font-size:12px;color:var(--aws-sub)">{{ $trip->user?->phone }}</div>
                </div>
            </div>
            <div class="aws-detail-item">
                <div class="aws-detail-label">Statut</div>
                <div class="aws-detail-value">
                    @php $statusMap = ['pending'=>['yellow','En attente'],'active'=>['green','En cours'],'completed'=>['blue','Terminé'],'cancelled'=>['red','Annulé']]; @endphp
                    @if(isset($statusMap[$trip->status]))
                        <span class="aws-badge aws-badge-{{ $statusMap[$trip->status][0] }}">{{ $statusMap[$trip->status][1] }}</span>
                    @else
                        <span class="aws-badge aws-badge-gray">{{ $trip->status }}</span>
                    @endif
                </div>
            </div>
            <div class="aws-detail-item">
                <div class="aws-detail-label">Messages</div>
                <div class="aws-detail-value">{{ $messages->count() }}</div>
            </div>
        </div>
    </div>
</div>

<!-- CONVERSATION -->
<div class="aws-panel">
    <div class="aws-panel-header">
        <span class="aws-panel-title">Conversation</span>
        <span style="font-size:12px;color:var(--aws-sub)">{{ $messages->count() }} message(s) — lecture seule</span>
    </div>
    <div class="aws-panel-body" style="padding:0">

        @if($messages->isEmpty())
        <div style="text-align:center;padding:40px;color:var(--aws-sub)">
            Aucun message dans cette conversation.
        </div>
        @else
        <div style="max-height:560px;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:10px">
            @foreach($messages as $msg)
            @php
                $sType   = (string)($msg->sender_type ?? '');
                $isDriver = str_contains($sType, 'Driver');
                $refused  = $msg->refused ?? false;
            @endphp
            <div style="display:flex;flex-direction:column;align-items:{{ $isDriver ? 'flex-end' : 'flex-start' }}">
                <div style="font-size:10px;color:var(--aws-sub);margin-bottom:3px;padding:0 6px">
                    {{ $isDriver ? '🚗 Chauffeur' : '👤 Client' }}
                    · {{ $msg->created_at?->format('d/m H:i') }}
                </div>
                <div style="
                    max-width:70%;
                    padding:10px 14px;
                    border-radius:{{ $isDriver ? '18px 18px 4px 18px' : '18px 18px 18px 4px' }};
                    background:{{ $refused ? '#fdf3f1' : ($isDriver ? '#ec7211' : '#f2f3f3') }};
                    color:{{ $refused ? '#d13212' : ($isDriver ? '#fff' : '#16191f') }};
                    border:{{ $refused ? '1px solid #f5b6a7' : 'none' }};
                    font-size:13px;
                    line-height:1.5;
                ">
                    @if($refused)
                        <em>⛔ Message bloqué par la modération</em>
                        @if($msg->refused_reason)
                            <div style="font-size:11px;margin-top:4px;opacity:.8">{{ $msg->refused_reason }}</div>
                        @endif
                    @else
                        {{ $msg->content }}
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif

    </div>
</div>

@endsection
