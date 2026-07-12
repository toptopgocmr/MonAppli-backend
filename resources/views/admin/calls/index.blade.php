@extends('admin.layouts.app')
@section('title','Journal des appels')

@section('content')

<div style="display:flex;flex-direction:column;gap:20px">

<div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px">
    <div>
        <h1 class="page-title">Journal des appels support</h1>
        <p class="page-sub">Historique des appels entrants (client, chauffeur, société → support) et sortants (support → ...), même si la sonnerie temps réel a échoué.</p>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" action="{{ route('admin.calls.index') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;width:100%">
        <select name="queue_type" class="ttg-select" style="min-width:200px">
            <option value="">Toutes les catégories</option>
            <option value="client"    {{ request('queue_type')=='client'    ? 'selected' : '' }}>📞 Client</option>
            <option value="chauffeur" {{ request('queue_type')=='chauffeur' ? 'selected' : '' }}>📞 Chauffeur</option>
            <option value="societe"   {{ request('queue_type')=='societe'   ? 'selected' : '' }}>📞 Société</option>
        </select>
        <button type="submit" class="btn btn-primary">Filtrer</button>
        <a href="{{ route('admin.calls.index') }}" class="btn btn-gray">Reset</a>
    </form>
</div>

<div class="panel">
    <div style="overflow-x:auto">
        <table class="ttg-table">
            <thead>
                <tr>
                    <th>Sens</th>
                    <th>Catégorie</th>
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
                    <td style="font-size:12px;color:#475569">{{ $call->direction }}</td>
                    <td>
                        @php $qLabels = ['client'=>'📞 Client','chauffeur'=>'📞 Chauffeur','societe'=>'📞 Société']; @endphp
                        <span class="badge badge-gray">{{ $qLabels[$call->queue_type] ?? '—' }}</span>
                    </td>
                    <td style="font-weight:600;color:#0f172a">{{ $call->other_name }}</td>
                    <td>
                        @if($call->status=='initiated')
                            <span class="badge badge-yellow">⏳ En attente</span>
                        @elseif($call->status=='answered')
                            <span class="badge badge-blue">🎙️ En cours</span>
                        @elseif($call->status=='ended')
                            <span class="badge badge-green">Terminé</span>
                        @else
                            <span class="badge badge-red">Manqué</span>
                        @endif
                    </td>
                    <td style="color:#475569">{{ $call->duration_formatted }}</td>
                    <td style="font-size:12px;color:#64748b">{{ $call->created_at?->format('d/m/Y H:i:s') }}</td>
                    <td style="font-size:12px;color:#64748b">{{ $call->ended_at?->format('d/m/Y H:i:s') ?? '—' }}</td>
                    <td style="white-space:nowrap">
                        @if($call->target_type)
                        <button type="button"
                                onclick="TTCall.startCall('{{ $call->target_type }}', {{ $call->target_id }})"
                                class="btn btn-sm btn-primary" style="white-space:nowrap">
                            📞 Rappeler
                        </button>
                        @else
                        <span style="color:#cbd5e1;font-size:12px">—</span>
                        @endif
                        @foreach($call->recordings as $rec)
                        <a href="{{ route('admin.calls.recording.play', [$call->id, $rec->id]) }}" target="_blank"
                           class="btn btn-sm btn-gray" style="white-space:nowrap;margin-left:4px" title="Écouter l'enregistrement">
                            🎧
                        </a>
                        @endforeach
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" style="padding:40px;text-align:center;color:#94a3b8">Aucun appel enregistré.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($calls->hasPages())
    <div style="padding:16px 20px;border-top:1px solid #f1f5f9">
        {{ $calls->appends(request()->query())->links() }}
    </div>
    @endif
</div>

</div>
@endsection
