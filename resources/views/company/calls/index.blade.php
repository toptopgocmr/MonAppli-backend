@extends('company.layouts.app')
@section('title', 'Journal des appels')

@section('content')

<div class="aws-page-header">
    <div class="aws-crumb"><a href="{{ route('company.dashboard') }}">Dashboard</a> › Journal des appels</div>
    <h1 class="aws-page-title">Journal des appels</h1>
</div>

<div class="aws-panel">
    <div class="aws-panel-header">
        <span class="aws-panel-title">Historique (support ↔ société, client ↔ société)</span>
    </div>
    <div class="aws-panel-body" style="padding:0">
        <table class="aws-table">
            <thead>
                <tr>
                    <th>Sens</th>
                    <th>Interlocuteur</th>
                    <th>Statut</th>
                    <th>Durée</th>
                    <th>Débuté le</th>
                    <th>Terminé le</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($calls as $call)
                <tr>
                    <td>{{ $call->direction }}</td>
                    <td style="font-weight:600">{{ $call->other_name }}</td>
                    <td>
                        @if($call->status=='initiated')
                            <span class="aws-badge aws-badge-yellow">⏳ En attente</span>
                        @elseif($call->status=='answered')
                            <span class="aws-badge aws-badge-blue">🎙️ En cours</span>
                        @elseif($call->status=='ended')
                            <span class="aws-badge aws-badge-green">Terminé</span>
                        @else
                            <span class="aws-badge aws-badge-red">Manqué</span>
                        @endif
                    </td>
                    <td>{{ $call->duration_formatted }}</td>
                    <td style="font-size:12px;color:var(--aws-sub)">{{ $call->created_at?->format('d/m/Y H:i:s') }}</td>
                    <td style="font-size:12px;color:var(--aws-sub)">{{ $call->ended_at?->format('d/m/Y H:i:s') ?? '—' }}</td>
                    <td>
                        {{-- La société ne peut appeler que le support (un seul
                             destinataire possible) — même action que le bouton
                             "Appeler le support" du topbar, dupliquée ici pour
                             éviter de remonter en haut de page. --}}
                        <button type="button" onclick="TTCall.callSupport()"
                                class="aws-btn aws-btn-primary" style="padding:6px 12px;font-size:12px;white-space:nowrap">
                            📞 Rappeler
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="padding:40px;text-align:center;color:var(--aws-sub)">Aucun appel enregistré.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($calls->hasPages())
    <div style="padding:16px 20px;border-top:1px solid var(--aws-border)">
        {{ $calls->links() }}
    </div>
    @endif
</div>

@endsection
