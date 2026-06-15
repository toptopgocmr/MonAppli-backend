@extends('company.layouts.app')

@section('title', 'Messages — Trajets')

@section('content')

<div class="aws-page-header">
    <div class="aws-crumb">
        <a href="{{ route('company.dashboard') }}">Dashboard</a> › Messages
    </div>
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
        <h1 class="aws-page-title">💬 Messages des trajets</h1>
        <a href="{{ route('company.messages.support') }}" class="aws-btn aws-btn-normal">
            🛠 Messages support clients
        </a>
    </div>
</div>

<!-- STATS -->
<div class="aws-stat-grid">
    <div class="aws-stat-card">
        <div class="aws-stat-label">Trajets avec échanges</div>
        <div class="aws-stat-value">{{ $totalTripsWithMessages }}</div>
    </div>
    <div class="aws-stat-card">
        <div class="aws-stat-label">Messages total</div>
        <div class="aws-stat-value">{{ $totalMessages }}</div>
    </div>
</div>

<!-- SEARCH -->
<form method="GET" action="{{ route('company.messages.index') }}" style="margin-bottom:16px">
    <div style="display:flex;gap:8px">
        <input type="text" name="search" class="aws-input" style="max-width:320px"
               placeholder="Rechercher par ville, destination…"
               value="{{ request('search') }}">
        <button type="submit" class="aws-btn aws-btn-primary">Rechercher</button>
        @if(request('search'))
            <a href="{{ route('company.messages.index') }}" class="aws-btn aws-btn-normal">Effacer</a>
        @endif
    </div>
</form>

<!-- TABLE -->
<div class="aws-panel">
    <div class="aws-panel-header">
        <span class="aws-panel-title">Trajets avec conversations</span>
        <span style="font-size:13px;color:var(--aws-sub)">{{ $trips->total() }} résultat(s)</span>
    </div>
    <div style="overflow-x:auto">
        <table class="aws-table">
            <thead>
                <tr>
                    <th>Trajet</th>
                    <th>Chauffeur</th>
                    <th>Client</th>
                    <th>Messages</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($trips as $trip)
                <tr>
                    <td>
                        <div style="font-weight:600;font-size:13px">{{ $trip->departure }}</div>
                        <div style="color:var(--aws-sub);font-size:12px">→ {{ $trip->destination }}</div>
                    </td>
                    <td>
                        @if($trip->driver)
                            <div style="font-size:13px">{{ $trip->driver->first_name }} {{ $trip->driver->last_name }}</div>
                        @else
                            <span style="color:var(--aws-sub)">—</span>
                        @endif
                    </td>
                    <td>
                        @if($trip->user)
                            <div style="font-size:13px">{{ $trip->user->first_name }} {{ $trip->user->last_name }}</div>
                            <div style="color:var(--aws-sub);font-size:12px">{{ $trip->user->phone }}</div>
                        @else
                            <span style="color:var(--aws-sub)">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="aws-badge aws-badge-blue">{{ $trip->messages_count }} msg</span>
                    </td>
                    <td style="font-size:12px;color:var(--aws-sub)">
                        {{ $trip->departure_date?->format('d/m/Y') }}
                    </td>
                    <td>
                        @php $statusMap = ['pending'=>['yellow','En attente'],'active'=>['green','En cours'],'completed'=>['blue','Terminé'],'cancelled'=>['red','Annulé']]; @endphp
                        @if(isset($statusMap[$trip->status]))
                            <span class="aws-badge aws-badge-{{ $statusMap[$trip->status][0] }}">
                                {{ $statusMap[$trip->status][1] }}
                            </span>
                        @else
                            <span class="aws-badge aws-badge-gray">{{ $trip->status }}</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('company.messages.show', $trip->id) }}" class="aws-btn aws-btn-normal" style="font-size:12px;padding:4px 12px">
                            Voir →
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:40px;color:var(--aws-sub)">
                        Aucun trajet avec des messages trouvé.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($trips->hasPages())
    <div style="padding:16px 20px;border-top:1px solid var(--aws-border)">
        {{ $trips->links() }}
    </div>
    @endif
</div>

@endsection
