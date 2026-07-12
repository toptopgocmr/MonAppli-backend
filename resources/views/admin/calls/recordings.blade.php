@extends('admin.layouts.app')
@section('title','Enregistrements des appels')

@section('content')

<div style="display:flex;flex-direction:column;gap:20px">

<div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px">
    <div>
        <h1 class="page-title">🎧 Enregistrements des appels</h1>
        <p class="page-sub">Appels support (client, chauffeur, société ↔ admin, enregistrés côté navigateur) et appels trajet (client ↔ chauffeur, enregistrés via Agora Cloud Recording).</p>
    </div>
    <a href="{{ route('admin.calls.index') }}" class="btn btn-gray">← Journal des appels</a>
</div>

<div class="filter-bar">
    <form method="GET" action="{{ route('admin.calls.recordings.index') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;width:100%">
        <select name="queue_type" class="ttg-select" style="min-width:200px">
            <option value="">Toutes les catégories</option>
            <option value="client"    {{ request('queue_type')=='client'    ? 'selected' : '' }}>📞 Client</option>
            <option value="chauffeur" {{ request('queue_type')=='chauffeur' ? 'selected' : '' }}>📞 Chauffeur</option>
            <option value="societe"   {{ request('queue_type')=='societe'   ? 'selected' : '' }}>📞 Société</option>
            <option value="trajet"    {{ request('queue_type')=='trajet'    ? 'selected' : '' }}>🚗 Trajet (client ↔ chauffeur)</option>
        </select>
        <button type="submit" class="btn btn-primary">Filtrer</button>
        <a href="{{ route('admin.calls.recordings.index') }}" class="btn btn-gray">Reset</a>
    </form>
</div>

<div class="panel">
    <div style="overflow-x:auto">
        <table class="ttg-table">
            <thead>
                <tr>
                    <th>Catégorie</th>
                    <th>Interlocuteur</th>
                    <th>Sens</th>
                    <th>Enregistré par</th>
                    <th>Taille</th>
                    <th>Date</th>
                    <th>Lecture</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recordings as $rec)
                <tr>
                    <td>
                        @php $qLabels = ['client'=>'📞 Client','chauffeur'=>'📞 Chauffeur','societe'=>'📞 Société','trajet'=>'🚗 Trajet']; @endphp
                        <span class="badge badge-gray">{{ $qLabels[$rec->queue_type] ?? '—' }}</span>
                    </td>
                    <td style="font-weight:600;color:#0f172a">{{ $rec->other_name }}</td>
                    <td style="font-size:12px;color:#475569">{{ $rec->direction }}</td>
                    <td style="font-size:12px;color:#475569">{{ $rec->recorded_by_name }}</td>
                    <td style="font-size:12px;color:#64748b">{{ $rec->size_bytes ? round($rec->size_bytes / 1024, 0) . ' Ko' : '—' }}</td>
                    <td style="font-size:12px;color:#64748b">{{ $rec->created_at?->format('d/m/Y H:i:s') }}</td>
                    <td style="min-width:260px">
                        <audio controls preload="none" style="height:32px;width:240px">
                            <source src="{{ route('admin.calls.recording.play', [$rec->call_id, $rec->id]) }}" type="{{ $rec->source === 'cloud' ? 'audio/mp4' : 'audio/webm' }}">
                        </audio>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="padding:40px;text-align:center;color:#94a3b8">Aucun enregistrement pour le moment.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($recordings->hasPages())
    <div style="padding:16px 20px;border-top:1px solid #f1f5f9">
        {{ $recordings->appends(request()->query())->links() }}
    </div>
    @endif
</div>

</div>
@endsection
