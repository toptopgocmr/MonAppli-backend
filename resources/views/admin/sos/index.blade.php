@extends('admin.layouts.app')
@section('title','Alertes SOS')

@section('content')
<div style="display:flex;flex-direction:column;gap:16px">

    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px">
        <div class="page-header" style="margin-bottom:0">
            <h1 class="page-title">Alertes SOS</h1>
            <p class="page-sub">Surveillance en temps réel des alertes d'urgence</p>
        </div>
        @if($totalActive > 0)
        <form method="POST" action="{{ route('admin.sos.treat-all') }}"
              onsubmit="return confirm('Marquer toutes les alertes actives comme traitées ?')">
            @csrf
            <button class="btn btn-success">Tout marquer traité ({{ $totalActive }})</button>
        </form>
        @endif
    </div>

    {{-- STAT CARDS --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px">
        <div class="stat-card" style="border-left:3px solid var(--danger)">
            <div class="stat-icon" style="background:#FDF3F0">
                <svg width="16" height="16" fill="none" stroke="var(--danger)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div class="stat-val" style="color:var(--danger);display:flex;align-items:center;gap:8px">
                {{ $totalActive }}
                @if($totalActive > 0)
                    <span style="width:8px;height:8px;border-radius:50%;background:var(--danger);display:inline-block;animation:ping 1s infinite"></span>
                @endif
            </div>
            <div class="stat-lbl">Actives</div>
        </div>
        <div class="stat-card" style="border-left:3px solid var(--success)">
            <div class="stat-icon" style="background:#F2FCF6">
                <svg width="16" height="16" fill="none" stroke="var(--success)" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="stat-val" style="color:var(--success)">{{ $totalTreated }}</div>
            <div class="stat-lbl">Traitées</div>
        </div>
        <div class="stat-card" style="border-left:3px solid var(--brand)">
            <div class="stat-icon" style="background:#EBF5FB">
                <svg width="16" height="16" fill="none" stroke="var(--brand)" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div class="stat-val" style="color:var(--brand)">{{ $totalToday }}</div>
            <div class="stat-lbl">Aujourd'hui</div>
        </div>
        <div class="stat-card" style="border-left:3px solid var(--text-3)">
            <div class="stat-icon" style="background:#F7F8F8">
                <svg width="16" height="16" fill="none" stroke="var(--text-3)" stroke-width="2" viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            </div>
            <div class="stat-val">{{ $totalAll }}</div>
            <div class="stat-lbl">Total</div>
        </div>
    </div>

    {{-- CARTE SOS --}}
    <div class="panel" style="overflow:hidden">
        <div class="panel-header">
            <div style="display:flex;align-items:center;gap:10px">
                <span>Carte des alertes actives</span>
                @if($totalActive > 0)
                    <span class="badge badge-danger" style="animation:pulse 1.5s infinite">{{ $totalActive }} active{{ $totalActive > 1 ? 's' : '' }}</span>
                @endif
            </div>
            <div style="display:flex;align-items:center;gap:12px;font-size:12px;color:var(--text-3)">
                <span style="display:flex;align-items:center;gap:6px">
                    <span style="width:10px;height:10px;border-radius:50%;background:var(--danger);display:inline-block"></span>Chauffeur
                </span>
                <span style="display:flex;align-items:center;gap:6px">
                    <span style="width:10px;height:10px;border-radius:50%;background:#f97316;display:inline-block"></span>Utilisateur
                </span>
                <span style="color:var(--text-3);font-size:11px">Actualisé toutes les 10s</span>
            </div>
        </div>
        <div id="sosMap" style="height:350px;z-index:1"></div>
    </div>

    {{-- FILTRES --}}
    <form method="GET" action="{{ route('admin.sos.index') }}" class="filter-bar">
        <div style="display:flex;flex-direction:column;gap:4px">
            <label class="ttg-label">Statut</label>
            <select name="status" class="ttg-select" style="width:140px">
                <option value="all"     {{ request('status','all') === 'all'     ? 'selected' : '' }}>Tous</option>
                <option value="active"  {{ request('status') === 'active'        ? 'selected' : '' }}>Actives</option>
                <option value="treated" {{ request('status') === 'treated'       ? 'selected' : '' }}>Traitées</option>
            </select>
        </div>
        <div style="display:flex;flex-direction:column;gap:4px">
            <label class="ttg-label">Type</label>
            <select name="sender_type" class="ttg-select" style="width:140px">
                <option value="">Tous</option>
                <option value="driver" {{ request('sender_type') === 'driver' ? 'selected' : '' }}>Chauffeurs</option>
                <option value="user"   {{ request('sender_type') === 'user'   ? 'selected' : '' }}>Utilisateurs</option>
            </select>
        </div>
        <div style="display:flex;flex-direction:column;gap:4px">
            <label class="ttg-label">Date</label>
            <input type="date" name="date" value="{{ request('date') }}" class="ttg-input" style="width:160px">
        </div>
        <button type="submit" class="btn btn-danger" style="align-self:flex-end">Filtrer</button>
        <a href="{{ route('admin.sos.index') }}" class="btn btn-secondary" style="align-self:flex-end">Reset</a>
    </form>

    {{-- LISTE ALERTES --}}
    <div class="panel" style="overflow:hidden">
        <div class="panel-header">
            Liste des alertes
            <span class="badge badge-danger">{{ $alerts->total() }}</span>
        </div>

        <div style="border-top:1px solid var(--border)">
            @forelse($alerts as $alert)
                @php
                    $isDriver   = str_contains($alert->sender_type, 'Driver');
                    $senderName = ($alert->sender->first_name ?? '—') . ' ' . ($alert->sender->last_name ?? '');
                @endphp

                <div style="padding:14px 20px;display:flex;align-items:flex-start;gap:14px;border-bottom:1px solid var(--border);
                            {{ $alert->status === 'active' ? 'border-left:3px solid var(--danger);background:#FFFBFB' : '' }}
                            transition:background .1s" onmouseenter="this.style.background='#EBF5FB'" onmouseleave="this.style.background='{{ $alert->status === 'active' ? '#FFFBFB' : 'transparent' }}'">

                    {{-- Icône type --}}
                    <div style="width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;
                                background:{{ $isDriver ? '#FDF3F0' : '#FFF5EB' }};color:{{ $isDriver ? 'var(--danger)' : '#C45226' }}">
                        @if($isDriver)
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 12l2 2 4-4"/></svg>
                        @else
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        @endif
                    </div>

                    {{-- Infos --}}
                    <div style="flex:1;min-width:0">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
                            <span style="font-weight:600;color:var(--text-1)">{{ $senderName }}</span>
                            <span class="badge {{ $isDriver ? 'badge-danger' : 'badge-orange' }}">{{ $isDriver ? 'Chauffeur' : 'Utilisateur' }}</span>
                            @if($alert->status === 'active')
                                <span class="badge badge-danger" style="animation:pulse 1.5s infinite">SOS ACTIF</span>
                            @else
                                <span class="badge badge-success">Traité</span>
                            @endif
                        </div>

                        @if($alert->message)
                            <p style="font-size:13px;color:var(--text-2);margin:4px 0">{{ $alert->message }}</p>
                        @endif

                        <div style="display:flex;align-items:center;gap:14px;margin-top:6px;font-size:12px;color:var(--text-3);flex-wrap:wrap">
                            <span>{{ $alert->created_at->format('d/m/Y H:i') }} ({{ $alert->created_at->diffForHumans() }})</span>
                            @if($alert->trip_id)
                                <span>Course #{{ $alert->trip_id }}</span>
                            @endif
                            @if($alert->lat && $alert->lng)
                                <span>{{ number_format($alert->lat,4) }}, {{ number_format($alert->lng,4) }}</span>
                            @endif
                            @if($alert->status === 'treated' && $alert->treatedBy)
                                <span>Traité par {{ $alert->treatedBy->name ?? '—' }}
                                    le {{ $alert->treated_at ? \Carbon\Carbon::parse($alert->treated_at)->format('d/m/Y H:i') : '—' }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
                        @if($alert->lat && $alert->lng)
                            <button onclick="zoomSos({{ $alert->lat }},{{ $alert->lng }})" class="btn btn-secondary btn-sm">
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="10" r="3"/><path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 1 0-16 0c0 3 2.7 6.9 8 11.7z"/></svg>
                                Localiser
                            </button>
                        @endif

                        <a href="{{ route('admin.sos.show', $alert->id) }}" class="btn btn-primary btn-sm">Détail</a>

                        @if($alert->status === 'active')
                            <form method="POST" action="{{ route('admin.sos.treat', $alert->id) }}" style="margin:0"
                                  onsubmit="return confirm('Marquer cette alerte comme traitée ?')">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">Traiter</button>
                            </form>
                        @endif
                    </div>
                </div>

            @empty
                <div style="text-align:center;padding:64px;color:var(--text-3)">
                    <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;opacity:.3"><circle cx="12" cy="12" r="10"/><path d="M8 12l2 2 4-4"/></svg>
                    <div style="font-weight:600;color:var(--text-2)">Aucune alerte</div>
                    <div style="font-size:13px;margin-top:4px">Aucune alerte SOS dans cette catégorie</div>
                </div>
            @endforelse
        </div>

        @if($alerts->hasPages())
            <div style="padding:14px 20px;border-top:1px solid var(--border)">
                {{ $alerts->appends(request()->query())->links('pagination::tailwind') }}
            </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<style>
@keyframes ping{0%{transform:scale(1);opacity:1}100%{transform:scale(1.8);opacity:0}}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
</style>
<script>
const sosMap = L.map('sosMap').setView([2.0, 15.0], 4);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap',maxZoom:19}).addTo(sosMap);
let sosMarkers={};

function makeSosIcon(type){
    const color=type==='driver'?'#D13212':'#f97316';
    const svg=`<svg xmlns="http://www.w3.org/2000/svg" width="36" height="44" viewBox="0 0 36 44">
        <ellipse cx="18" cy="42" rx="8" ry="2.5" fill="rgba(0,0,0,.2)"/>
        <path d="M18 0C8 0 0 8 0 18c0 12 18 44 18 44s18-32 18-44C36 8 28 0 18 0Z" fill="${color}" stroke="white" stroke-width="2"/>
        <circle cx="18" cy="16" r="7" fill="white"/>
        <text x="18" y="21" text-anchor="middle" font-size="10" fill="${color}" font-weight="bold">SOS</text>
    </svg>`;
    return L.divIcon({html:svg,iconSize:[36,44],iconAnchor:[18,44],popupAnchor:[0,-48],className:''});
}

function updateSosMap(alerts){
    const seen=new Set();
    alerts.forEach(a=>{
        if(!a.lat||!a.lng)return;
        seen.add(a.id);
        const popup=`<div style="min-width:200px;font-family:Inter,sans-serif">
            <div style="font-weight:700;color:#D13212;font-size:13px;margin-bottom:6px">Alerte SOS</div>
            <div style="font-size:12px;color:#545B64;line-height:1.8">
                <strong>${a.sender_name||'—'}</strong><br>
                ${a.phone?a.phone+'<br>':''}
                ${a.vehicle?a.vehicle+'<br>':''}
                ${a.message?a.message+'<br>':''}
                ${a.created_at}
                ${a.trip_id?'<br>Course #'+a.trip_id:''}
            </div>
            <a href="/admin/sos/${a.id}" style="display:inline-block;margin-top:8px;background:#D13212;color:white;padding:4px 10px;border-radius:4px;font-size:11px;text-decoration:none">Voir le détail</a>
        </div>`;
        if(sosMarkers[a.id]){sosMarkers[a.id].setPopupContent(popup);}
        else{sosMarkers[a.id]=L.marker([a.lat,a.lng],{icon:makeSosIcon(a.sender_type)}).addTo(sosMap).bindPopup(popup).openPopup();}
    });
    Object.keys(sosMarkers).forEach(id=>{
        if(!seen.has(parseInt(id))){sosMap.removeLayer(sosMarkers[id]);delete sosMarkers[id];}
    });
    if(alerts.length>0){
        const coords=alerts.filter(a=>a.lat&&a.lng).map(a=>[a.lat,a.lng]);
        if(coords.length===1)sosMap.setView(coords[0],14);
        else if(coords.length>1)sosMap.fitBounds(L.latLngBounds(coords),{padding:[40,40]});
    }
}

function zoomSos(lat,lng){
    document.getElementById('sosMap').scrollIntoView({behavior:'smooth'});
    setTimeout(()=>sosMap.setView([lat,lng],16),400);
}

function fetchSosAlerts(){
    fetch("{{ route('admin.sos.live') }}",{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
        .then(r=>r.json())
        .then(data=>updateSosMap(data.alerts||[]))
        .catch(e=>console.error('SOS fetch error:',e));
}

fetchSosAlerts();
setInterval(fetchSosAlerts,10000);

@if($totalActive > 0)
    document.title='SOS ({{ $totalActive }}) — TopTopGo Admin';
@endif
</script>
@endpush
