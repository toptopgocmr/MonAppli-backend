@extends('company.layouts.app')
@section('title', 'Itinéraires')

@section('content')

<div class="aws-crumb"><a href="{{ route('company.dashboard') }}">Dashboard</a> › Itinéraires</div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <div class="aws-page-title">Itinéraires</div>
    <a href="{{ route('company.itineraries.create') }}" class="aws-btn aws-btn-primary">+ Nouvel itinéraire</a>
</div>

<div class="aws-panel">
    <div style="padding:12px 20px;border-bottom:1px solid var(--aws-border);display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;background:#fafafa;border-radius:4px 4px 0 0">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Départ, destination..." class="aws-input" style="width:220px">
            <select name="status" class="aws-input" style="width:140px">
                <option value="">Tous</option>
                <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Actifs</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactifs</option>
            </select>
            <button type="submit" class="aws-btn aws-btn-primary" style="padding:7px 14px">Filtrer</button>
        </form>
        <span style="font-size:12px;color:var(--aws-sub)">{{ $itineraries->total() }} itinéraire(s)</span>
    </div>

    <div style="overflow-x:auto">
        <table class="aws-table">
            <thead>
                <tr>
                    <th>Départ → Destination</th>
                    <th>Tarif</th>
                    <th>Distance</th>
                    <th>Durée</th>
                    <th>Véhicule</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($itineraries as $it)
                <tr>
                    <td>
                        <div style="font-weight:600;font-size:13px;color:var(--aws-header)">
                            {{ $it->departure }}
                            <span style="color:var(--aws-sub);margin:0 4px">→</span>
                            {{ $it->destination }}
                        </div>
                        @if($it->notes)
                            <div style="font-size:11px;color:var(--aws-sub);margin-top:2px">{{ Str::limit($it->notes, 60) }}</div>
                        @endif
                    </td>
                    <td>
                        @if($it->price)
                            <span style="font-weight:700;color:var(--aws-header)">{{ number_format($it->price, 0, ',', ' ') }}</span>
                            <span style="font-size:11px;color:var(--aws-sub)"> FCFA</span>
                        @else
                            <span style="color:var(--aws-sub)">—</span>
                        @endif
                    </td>
                    <td style="color:var(--aws-sub)">{{ $it->distance_km ? $it->distance_km . ' km' : '—' }}</td>
                    <td style="color:var(--aws-sub)">{{ $it->duration_min ? $it->duration_min . ' min' : '—' }}</td>
                    <td style="color:var(--aws-sub)">{{ $it->vehicle_type ?? '—' }}</td>
                    <td>
                        @if($it->is_active)
                            <span class="aws-badge aws-badge-green">Actif</span>
                        @else
                            <span class="aws-badge aws-badge-gray">Inactif</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            <a href="{{ route('company.itineraries.edit', $it->id) }}" class="aws-btn aws-btn-normal" style="padding:4px 10px;font-size:12px">Modifier</a>
                            <form method="POST" action="{{ route('company.itineraries.toggle', $it->id) }}" style="display:inline">
                                @csrf
                                <button type="submit" class="aws-btn aws-btn-normal" style="padding:4px 10px;font-size:12px">
                                    {{ $it->is_active ? 'Désactiver' : 'Activer' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('company.itineraries.destroy', $it->id) }}" style="display:inline" onsubmit="return confirm('Supprimer cet itinéraire ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="aws-btn aws-btn-danger" style="padding:4px 10px;font-size:12px">✕</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:40px;color:var(--aws-sub)">
                        Aucun itinéraire défini —
                        <a href="{{ route('company.itineraries.create') }}" style="color:var(--aws-blue)">Créer le premier</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($itineraries->hasPages())
    <div style="padding:12px 20px;border-top:1px solid var(--aws-border)">{{ $itineraries->links() }}</div>
    @endif
</div>
@endsection
