@extends('company.layouts.app')
@section('title', 'Flotte Véhicules')

@section('content')

<div class="aws-crumb"><a href="{{ route('company.dashboard') }}">Dashboard</a> › Flotte Véhicules</div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <div class="aws-page-title">Flotte Véhicules</div>
    <div style="display:flex;gap:10px">
        <a href="{{ route('company.schedule.index') }}" class="aws-btn aws-btn-normal">📅 Planning chauffeurs</a>
        <a href="{{ route('company.vehicles.create') }}" class="aws-btn aws-btn-primary">+ Ajouter un véhicule</a>
    </div>
</div>

@if(session('success'))
<div style="background:#f0f9ec;border:1px solid #b7e0a0;color:#1d8102;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:16px">{{ session('success') }}</div>
@endif

<div class="aws-panel">
    <div style="padding:12px 20px;border-bottom:1px solid var(--aws-border);display:flex;gap:10px;align-items:center;justify-content:space-between;background:#fafafa;border-radius:4px 4px 0 0">
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Plaque, marque, modèle..." class="aws-input" style="width:220px">
            <button type="submit" class="aws-btn aws-btn-primary" style="padding:7px 14px">Filtrer</button>
        </form>
        <span style="font-size:12px;color:var(--aws-sub)">{{ $vehicles->total() }} véhicule(s)</span>
    </div>

    <div style="overflow-x:auto">
        <table class="aws-table">
            <thead>
                <tr>
                    <th>Plaque</th>
                    <th>Marque / Modèle</th>
                    <th>Type</th>
                    <th>Couleur</th>
                    <th>Ville</th>
                    <th>Chauffeurs assignés</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vehicles as $vehicle)
                <tr>
                    <td><code style="background:#f2f3f3;padding:2px 8px;border-radius:3px;font-size:12px;border:1px solid var(--aws-border)">{{ $vehicle->plate }}</code></td>
                    <td>{{ $vehicle->brand ?? '—' }} {{ $vehicle->model ?? '' }}</td>
                    <td>{{ $vehicle->type ?? '—' }}</td>
                    <td style="color:var(--aws-sub)">{{ $vehicle->color ?? '—' }}</td>
                    <td style="color:var(--aws-sub)">{{ $vehicle->city ?? '—' }}</td>
                    <td>
                        @if($vehicle->drivers_count > 0)
                            <span class="aws-badge aws-badge-blue">{{ $vehicle->drivers_count }} chauffeur{{ $vehicle->drivers_count > 1 ? 's' : '' }}</span>
                        @else
                            <span style="color:var(--aws-sub);font-size:12px">Aucun</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $stMap = ['active'=>['aws-badge-green','Actif'],'maintenance'=>['aws-badge-yellow','Maintenance'],'inactive'=>['aws-badge-gray','Inactif']];
                            $sc = $stMap[$vehicle->status] ?? ['aws-badge-gray', $vehicle->status];
                        @endphp
                        <span class="aws-badge {{ $sc[0] }}">{{ $sc[1] }}</span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="{{ route('company.vehicles.show', $vehicle->id) }}" class="aws-btn aws-btn-normal" style="padding:4px 10px;font-size:12px">Gérer chauffeurs</a>
                            <a href="{{ route('company.vehicles.edit', $vehicle->id) }}" class="aws-btn aws-btn-normal" style="padding:4px 10px;font-size:12px">Modifier</a>
                            <form method="POST" action="{{ route('company.vehicles.destroy', $vehicle->id) }}" onsubmit="return confirm('Retirer ce véhicule de la flotte ?')" style="display:inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="aws-btn aws-btn-danger" style="padding:4px 10px;font-size:12px">Retirer</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:40px;color:var(--aws-sub)">
                        Aucun véhicule dans votre flotte —
                        <a href="{{ route('company.vehicles.create') }}" style="color:var(--aws-blue)">Ajouter un véhicule</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($vehicles->hasPages())
    <div style="padding:12px 20px;border-top:1px solid var(--aws-border)">{{ $vehicles->links() }}</div>
    @endif
</div>
@endsection
