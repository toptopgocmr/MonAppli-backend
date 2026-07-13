@extends('company.layouts.app')
@section('title', 'Réservations')

@section('content')

<div class="aws-crumb"><a href="{{ route('company.dashboard') }}">Dashboard</a> › Réservations</div>
<div class="aws-page-title" style="margin-bottom:16px">Réservations & Courses</div>

@if(session('success'))
<div class="aws-alert aws-alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif

@if($pendingAssignment->isNotEmpty())
<div class="aws-panel" style="margin-bottom:20px;border-color:#f0ad4e">
    <div class="aws-panel-header" style="background:#fff8e6">
        <span class="aws-panel-title">⏳ Trajets à assigner à un chauffeur</span>
        <span style="font-size:12px;color:var(--aws-sub)">{{ $pendingAssignment->count() }} trajet(s) — client(s) déjà payé(s)</span>
    </div>
    <div style="overflow-x:auto">
        <table class="aws-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Départ → Arrivée</th>
                    <th>Date / Heure</th>
                    <th>Places réservées (payées)</th>
                    <th>Chauffeur</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingAssignment as $trip)
                @php
                    $paidSeats = $trip->bookings->sum(fn($b) => $b->seats ?? $b->passengers ?? 1);
                @endphp
                <tr>
                    <td style="font-family:monospace;color:var(--aws-sub)">#{{ $trip->id }}</td>
                    <td>
                        <span style="font-size:13px;color:var(--aws-header)">
                            {{ \Illuminate\Support\Str::limit($trip->departure ?? '—', 22) }}
                            <span style="color:var(--aws-sub);margin:0 3px">→</span>
                            {{ \Illuminate\Support\Str::limit($trip->destination ?? '—', 22) }}
                        </span>
                    </td>
                    <td style="font-size:12px;color:var(--aws-sub)">
                        {{ $trip->departure_date ? \Carbon\Carbon::parse($trip->departure_date)->format('d/m/Y') : '—' }}
                        {{ $trip->departure_time ? substr($trip->departure_time, 0, 5) : '' }}
                    </td>
                    <td style="font-weight:700;color:#0073bb">{{ $paidSeats }} / {{ $trip->total_seats ?? $trip->available_seats }}</td>
                    <td>
                        <form method="POST" action="{{ route('company.reservations.assign-driver', $trip->id) }}" style="display:flex;gap:6px;align-items:center">
                            @csrf
                            <select name="driver_id" class="aws-input" style="width:200px" required>
                                <option value="">— Choisir un chauffeur —</option>
                                @foreach($companyDrivers as $d)
                                    <option value="{{ $d->id }}">{{ $d->first_name }} {{ $d->last_name }}{{ $d->vehicle_type ? ' — '.$d->vehicle_type : '' }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="aws-btn aws-btn-primary" style="padding:6px 12px;font-size:12px">Assigner</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="aws-panel">
    <div style="padding:12px 20px;border-bottom:1px solid var(--aws-border);display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;background:#fafafa;border-radius:4px 4px 0 0">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Chauffeur, adresse..." class="aws-input" style="width:220px">
            <select name="status" class="aws-input" style="width:160px">
                <option value="">Tous les statuts</option>
                @foreach(['pending'=>'En attente','accepted'=>'Acceptée','in_progress'=>'En cours','completed'=>'Terminée','cancelled'=>'Annulée'] as $val => $label)
                    <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="aws-btn aws-btn-primary" style="padding:7px 14px">Filtrer</button>
        </form>
        <span style="font-size:12px;color:var(--aws-sub)">{{ $trips->total() }} course(s)</span>
    </div>

    <div style="overflow-x:auto">
        <table class="aws-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Chauffeur</th>
                    <th>Départ → Arrivée</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($trips as $trip)
                @php
                    $statusMap = ['pending'=>['aws-badge-yellow','En attente'],'accepted'=>['aws-badge-blue','Acceptée'],'in_progress'=>['aws-badge-blue','En cours'],'completed'=>['aws-badge-green','Terminée'],'cancelled'=>['aws-badge-red','Annulée']];
                    $sc = $statusMap[$trip->status] ?? ['aws-badge-gray', $trip->status];
                @endphp
                <tr>
                    <td style="font-family:monospace;color:var(--aws-sub)">#{{ $trip->id }}</td>
                    <td>
                        @if($trip->driver)
                        <div style="display:flex;align-items:center;gap:8px">
                            <div style="width:26px;height:26px;border-radius:50%;background:#0073bb;color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                {{ strtoupper(substr($trip->driver->first_name, 0, 1)) }}
                            </div>
                            <span style="font-size:13px;font-weight:600">{{ $trip->driver->first_name }} {{ $trip->driver->last_name }}</span>
                        </div>
                        @else
                            <span style="color:var(--aws-sub)">—</span>
                        @endif
                    </td>
                    <td>
                        <span style="font-size:13px;color:var(--aws-header)">
                            {{ \Illuminate\Support\Str::limit($trip->departure ?? '—', 22) }}
                            <span style="color:var(--aws-sub);margin:0 3px">→</span>
                            {{ \Illuminate\Support\Str::limit($trip->destination ?? '—', 22) }}
                        </span>
                    </td>
                    <td style="font-weight:700;color:#0073bb">{{ number_format($trip->amount ?? 0, 0, ',', ' ') }} FCFA</td>
                    <td><span class="aws-badge {{ $sc[0] }}">{{ $sc[1] }}</span></td>
                    <td style="color:var(--aws-sub);font-size:12px">{{ $trip->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <a href="{{ route('company.reservations.show', $trip->id) }}" class="aws-btn aws-btn-normal" style="padding:4px 10px;font-size:12px">Voir / Reçu</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:40px;color:var(--aws-sub)">Aucune course trouvée</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($trips->hasPages())
    <div style="padding:12px 20px;border-top:1px solid var(--aws-border)">{{ $trips->links() }}</div>
    @endif
</div>
@endsection
