@extends('admin.layouts.app')
@section('title','Chauffeurs')

@section('content')

<div style="display:flex;flex-direction:column;gap:20px">

{{-- Header --}}
<div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px">
    <div>
        <h1 class="page-title">Gestion des Chauffeurs</h1>
        <p class="page-sub">Liste et gestion de tous les chauffeurs</p>
    </div>
    <a href="{{ route('admin.drivers.create') }}" class="btn btn-primary">Nouveau Chauffeur</a>
</div>

{{-- Stats --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px">
    <div class="stat-card" style="border-left:4px solid #1DA1F2">
        <div class="lbl">Total</div>
        <div class="val" style="color:#1DA1F2">{{ $drivers->total() }}</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #22c55e">
        <div class="lbl">Approuvés</div>
        <div class="val" style="color:#16a34a">{{ \App\Models\Driver\Driver::where('status','approved')->count() }}</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #f59e0b">
        <div class="lbl">En attente</div>
        <div class="val" style="color:#d97706">{{ \App\Models\Driver\Driver::where('status','pending')->count() }}</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #ef4444">
        <div class="lbl">Suspendus</div>
        <div class="val" style="color:#dc2626">{{ \App\Models\Driver\Driver::where('status','suspended')->count() }}</div>
    </div>
</div>

{{-- Filtres --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('admin.drivers.index') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;width:100%">
        <div style="flex:1;min-width:180px">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Nom, téléphone..." class="ttg-input">
        </div>
        <select name="status" class="ttg-select" style="min-width:160px">
            <option value="">Tous les statuts</option>
            <option value="pending"   {{ request('status')=='pending'   ?'selected':'' }}>⏳ En attente</option>
            <option value="approved"  {{ request('status')=='approved'  ?'selected':'' }}>Approuvés</option>
            <option value="rejected"  {{ request('status')=='rejected'  ?'selected':'' }}>Rejetés</option>
            <option value="suspended" {{ request('status')=='suspended' ?'selected':'' }}>Suspendus</option>
        </select>
        <button type="submit" class="btn btn-primary">Filtrer</button>
        <a href="{{ route('admin.drivers.index') }}" class="btn btn-gray">Reset</a>
    </form>
</div>

{{-- Tableau --}}
<div class="panel">
    <div style="overflow-x:auto">
        <table class="ttg-table">
            <thead>
                <tr>
                    <th>Chauffeur</th>
                    <th>Téléphone</th>
                    <th>Véhicule</th>
                    <th>Type</th>
                    <th>Statut KYC</th>
                    <th>En ligne</th>
                    <th>Inscrit le</th>
                    <th style="text-align:center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($drivers as $driver)
                <tr id="driver-row-{{ $driver->id }}">
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            @if($driver->profile_photo)
                                <img src="{{ $driver->profile_photo }}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid #e2e8f0"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                <div class="avatar" style="background:#1DA1F2;color:#fff;display:none">{{ strtoupper(substr($driver->first_name,0,1)) }}</div>
                            @else
                                <div class="avatar" style="background:#1DA1F2;color:#fff">{{ strtoupper(substr($driver->first_name,0,1)) }}</div>
                            @endif
                            <div>
                                <p style="font-weight:600;color:#0f172a;font-size:13px">{{ $driver->first_name }} {{ $driver->last_name }}</p>
                                <p style="font-size:11px;color:#94a3b8">{{ $driver->vehicle_city ?? '—' }}</p>
                            </div>
                        </div>
                    </td>
                    <td style="color:#475569">{{ $driver->phone }}</td>
                    <td>
                        <p style="font-size:13px;color:#374151">{{ $driver->vehicle_brand ?? '—' }} {{ $driver->vehicle_model ?? '' }}</p>
                        <p style="font-size:11px;color:#94a3b8">{{ $driver->vehicle_plate ?? '—' }}</p>
                    </td>
                    <td><span class="badge badge-gray">{{ $driver->vehicle_type ?? '—' }}</span></td>
                    <td>
                        @if($driver->status=='approved')
                            <span class="badge badge-green">Approuvé</span>
                        @elseif($driver->status=='pending')
                            <span class="badge badge-yellow">⏳ En attente</span>
                        @elseif($driver->status=='rejected')
                            <span class="badge badge-red">Rejeté</span>
                        @else
                            <span class="badge badge-gray">Suspendu</span>
                        @endif
                    </td>
                    <td>
                        <span id="driver-status-{{ $driver->id }}">
                            @if($driver->driver_status=='online')
                                <span style="display:flex;align-items:center;gap:5px;color:#16a34a;font-size:12px;font-weight:600">
                                    <span style="width:7px;height:7px;border-radius:50%;background:#22c55e;display:inline-block;animation:pulse 1.5s infinite"></span> En ligne
                                </span>
                            @elseif($driver->driver_status=='pause')
                                <span style="display:flex;align-items:center;gap:5px;color:#d97706;font-size:12px;font-weight:600">
                                    <span style="width:7px;height:7px;border-radius:50%;background:#facc15;display:inline-block"></span> Pause
                                </span>
                            @else
                                <span style="display:flex;align-items:center;gap:5px;color:#9ca3af;font-size:12px;font-weight:600">
                                    <span style="width:7px;height:7px;border-radius:50%;background:#9ca3af;display:inline-block"></span> Hors ligne
                                </span>
                            @endif
                        </span>
                    </td>
                    <td style="font-size:12px;color:#64748b">{{ $driver->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div style="display:flex;justify-content:center;gap:6px;flex-wrap:wrap">
                            <a href="{{ route('admin.drivers.show',$driver->id) }}" class="btn btn-gray btn-sm">Voir</a>
                            <a href="{{ route('admin.drivers.edit',$driver->id) }}" class="btn btn-primary btn-sm">Modifier ️</a>
                            @if($driver->status=='pending')
                                <form method="POST" action="{{ route('admin.drivers.approve',$driver->id) }}">@csrf
                                    <button class="btn btn-success btn-sm">✓ Approuver</button>
                                </form>
                                <form method="POST" action="{{ route('admin.drivers.reject',$driver->id) }}">@csrf
                                    <button class="btn btn-danger btn-sm" onclick="return confirm('Rejeter ?')">✕ Rejeter</button>
                                </form>
                            @elseif($driver->status=='approved')
                                <form method="POST" action="{{ route('admin.drivers.suspend',$driver->id) }}">@csrf
                                    <button class="btn btn-sm" style="background:#fff7ed;color:#c2410c;border:1px solid #fed7aa" onclick="return confirm('Suspendre ?')">⏸ Suspendre</button>
                                </form>
                            @elseif($driver->status=='suspended')
                                <form method="POST" action="{{ route('admin.drivers.activate',$driver->id) }}">@csrf
                                    <button class="btn btn-success btn-sm">▶ Activer</button>
                                </form>
                            @elseif($driver->status=='rejected')
                                <form method="POST" action="{{ route('admin.drivers.approve',$driver->id) }}">@csrf
                                    <button class="btn btn-success btn-sm">✓ Approuver</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.drivers.destroy',$driver->id) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm" onclick="return confirm('Supprimer définitivement ?')">Supprimer </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" style="padding:40px;text-align:center;color:#94a3b8">Aucun chauffeur trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($drivers->hasPages())
    <div style="padding:16px 20px;border-top:1px solid #f1f5f9">
        {{ $drivers->appends(request()->query())->links() }}
    </div>
    @endif
</div>

</div>
@endsection

@push('scripts')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
const pusher = new Pusher('{{ env("PUSHER_APP_KEY") }}', { cluster: '{{ env("PUSHER_APP_CLUSTER") }}', forceTLS: true });
const channel = pusher.subscribe('drivers.status');
channel.bind('status.updated', function(data) {
    const el = document.getElementById('driver-status-' + data.driver_id);
    if (!el) return;
    const map = {
        online:  `<span style="display:flex;align-items:center;gap:5px;color:#16a34a;font-size:12px;font-weight:600"><span style="width:7px;height:7px;border-radius:50%;background:#22c55e;display:inline-block;animation:pulse 1.5s infinite"></span>En ligne</span>`,
        pause:   `<span style="display:flex;align-items:center;gap:5px;color:#d97706;font-size:12px;font-weight:600"><span style="width:7px;height:7px;border-radius:50%;background:#facc15;display:inline-block"></span>Pause</span>`,
        offline: `<span style="display:flex;align-items:center;gap:5px;color:#9ca3af;font-size:12px;font-weight:600"><span style="width:7px;height:7px;border-radius:50%;background:#9ca3af;display:inline-block"></span>Hors ligne</span>`,
    };
    el.innerHTML = map[data.status] || map.offline;
    const row = document.getElementById('driver-row-' + data.driver_id);
    if (row) { row.style.background='#f0fdf4'; setTimeout(()=>row.style.background='',1500); }
});
</script>
@endpush
