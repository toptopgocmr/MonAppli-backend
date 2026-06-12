@extends('company.layouts.app')
@section('title', 'Flotte Véhicules')

@section('content')

<div class="aws-crumb"><a href="{{ route('company.dashboard') }}">Dashboard</a> › Flotte Véhicules</div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <div class="aws-page-title">Flotte Véhicules</div>
    <a href="{{ route('company.vehicles.create') }}" class="aws-btn aws-btn-primary">+ Ajouter un véhicule</a>
</div>

<div class="aws-panel">
    <div style="padding:12px 20px;border-bottom:1px solid var(--aws-border);display:flex;gap:10px;align-items:center;justify-content:space-between;background:#fafafa;border-radius:4px 4px 0 0">
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher..." class="aws-input" style="width:220px">
            <button type="submit" class="aws-btn aws-btn-primary" style="padding:7px 14px">Filtrer</button>
        </form>
        <span style="font-size:12px;color:var(--aws-sub)">{{ $drivers->total() }} véhicule(s)</span>
    </div>

    <div style="overflow-x:auto">
        <table class="aws-table">
            <thead>
                <tr>
                    <th>Chauffeur</th>
                    <th>Marque / Modèle</th>
                    <th>Plaque</th>
                    <th>Couleur</th>
                    <th>Type</th>
                    <th>Ville</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($drivers as $driver)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            @if($driver->profile_photo)
                                <img src="{{ $driver->profile_photo }}" style="width:30px;height:30px;border-radius:50%;object-fit:cover;border:1px solid var(--aws-border)">
                            @else
                                <div style="width:30px;height:30px;border-radius:50%;background:#0073bb;color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center">
                                    {{ strtoupper(substr($driver->first_name, 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <div style="font-weight:600;font-size:13px">{{ $driver->first_name }} {{ $driver->last_name }}</div>
                                <div style="font-size:12px;color:var(--aws-sub)">{{ $driver->phone }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ $driver->vehicle_brand ?? '—' }} {{ $driver->vehicle_model ?? '' }}</td>
                    <td>
                        @if($driver->vehicle_plate)
                            <code style="background:#f2f3f3;padding:2px 8px;border-radius:3px;font-size:12px;border:1px solid var(--aws-border)">{{ $driver->vehicle_plate }}</code>
                        @else —
                        @endif
                    </td>
                    <td style="color:var(--aws-sub)">{{ $driver->vehicle_color ?? '—' }}</td>
                    <td>{{ $driver->vehicle_type ?? '—' }}</td>
                    <td style="color:var(--aws-sub)">{{ $driver->vehicle_city ?? '—' }}</td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="{{ route('company.vehicles.edit', $driver->id) }}" class="aws-btn aws-btn-normal" style="padding:4px 10px;font-size:12px">Modifier</a>
                            <form method="POST" action="{{ route('company.vehicles.destroy', $driver->id) }}" onsubmit="return confirm('Retirer ce véhicule ?')" style="display:inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="aws-btn aws-btn-danger" style="padding:4px 10px;font-size:12px">Retirer</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:40px;color:var(--aws-sub)">
                        Aucun véhicule dans votre flotte —
                        <a href="{{ route('company.vehicles.create') }}" style="color:var(--aws-blue)">Ajouter un véhicule</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($drivers->hasPages())
    <div style="padding:12px 20px;border-top:1px solid var(--aws-border)">{{ $drivers->links() }}</div>
    @endif
</div>
@endsection
