@extends('admin.layouts.app')
@section('title','Messagerie Users ↔ Chauffeurs')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px">

    <div class="page-header">
        <h1 class="page-title">Conversations Users ↔ Chauffeurs</h1>
        <p class="page-sub">Toutes les conversations entre utilisateurs et chauffeurs</p>
    </div>

    {{-- Stat cards --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px">
        <div class="stat-card">
            <div class="stat-icon" style="background:#EFF8FF">
                <svg width="18" height="18" fill="none" stroke="#1DA1F2" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div class="stat-val">{{ $totalMessages }}</div>
            <div class="stat-lbl">Total Messages</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#FFFBEB">
                <svg width="18" height="18" fill="none" stroke="#F59E0B" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div class="stat-val">{{ $unreadMessages }}</div>
            <div class="stat-lbl">Non lus</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#ECFDF5">
                <svg width="18" height="18" fill="none" stroke="#10B981" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="stat-val">{{ $totalTripsWithMessages }}</div>
            <div class="stat-lbl">Conversations actives</div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="filter-bar">
        <form method="GET" action="{{ route('admin.messages.index') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;width:100%">
            <div style="flex:1;min-width:200px">
                <label class="ttg-label">Filtrer par Utilisateur</label>
                <select name="user_id" class="ttg-select">
                    <option value="">Tous les utilisateurs</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->first_name }} {{ $u->last_name }}
                            @if($u->phone) ({{ $u->phone }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div style="flex:1;min-width:200px">
                <label class="ttg-label">Filtrer par Chauffeur</label>
                <select name="driver_id" class="ttg-select">
                    <option value="">Tous les chauffeurs</option>
                    @foreach($drivers as $d)
                        <option value="{{ $d->id }}" {{ request('driver_id') == $d->id ? 'selected' : '' }}>
                            {{ $d->first_name }} {{ $d->last_name }}
                            @if($d->phone) ({{ $d->phone }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Filtrer
                </button>
                <a href="{{ route('admin.messages.index') }}" class="btn btn-secondary">Réinitialiser</a>
            </div>
        </form>
    </div>

    {{-- Corps principal --}}
    <div style="display:flex;gap:16px;height:65vh">

        {{-- Liste conversations --}}
        <div class="panel" style="width:320px;flex-shrink:0;display:flex;flex-direction:column;overflow:hidden">
            <div class="panel-header">
                <span>Conversations</span>
                <span class="badge badge-info">{{ $trips->total() }}</span>
            </div>
            <div style="flex:1;overflow-y:auto">
                @forelse($trips as $t)
                    @php
                        $isActive = isset($trip) && $trip->id === $t->id;
                        $lastMsg  = $t->messages->first();
                        $params   = array_filter(['user_id' => request('user_id'), 'driver_id' => request('driver_id')]);
                    @endphp
                    <a href="{{ route('admin.messages.show', array_merge(['trip' => $t->id], $params)) }}"
                        style="display:block;padding:14px 16px;border-bottom:1px solid #F7F8FA;text-decoration:none;transition:background .12s;
                               {{ $isActive ? 'background:#EFF8FF;border-left:3px solid #1DA1F2;' : '' }}"
                        onmouseover="this.style.background='#F8FAFF'" onmouseout="this.style.background='{{ $isActive ? '#EFF8FF' : '' }}'">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                            <div class="avatar" style="background:#EEF2FF;color:#3730A3">{{ strtoupper(substr($t->user->first_name ?? 'U',0,1)) }}</div>
                            <svg width="12" height="12" fill="none" stroke="#94A3B8" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            <div class="avatar" style="background:#ECFDF5;color:#065F46">{{ strtoupper(substr($t->driver->first_name ?? 'D',0,1)) }}</div>
                        </div>
                        <div style="font-size:13px;font-weight:600;color:#0F172A">
                            {{ $t->user->first_name ?? 'User' }} ↔ {{ $t->driver->first_name ?? 'Driver' }}
                        </div>
                        <div style="font-size:11px;color:#94A3B8;margin-top:1px">Trip #{{ $t->id }}</div>
                        @if($lastMsg)
                            <div style="font-size:12px;color:#64748B;margin-top:4px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">{{ Str::limit($lastMsg->content, 45) }}</div>
                            <div style="font-size:11px;color:#CBD5E1;margin-top:2px">{{ $lastMsg->created_at->diffForHumans() }}</div>
                        @endif
                    </a>
                @empty
                    <div style="padding:40px;text-align:center;color:#94A3B8">
                        <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <p style="font-size:13px">Aucune conversation trouvée</p>
                    </div>
                @endforelse
            </div>
            @if($trips->hasPages())
                <div style="padding:10px;border-top:1px solid #F1F5F9;text-align:center">
                    {{ $trips->appends(request()->query())->links('pagination::simple-tailwind') }}
                </div>
            @endif
        </div>

        {{-- Zone messages --}}
        <div class="panel" style="flex:1;display:flex;flex-direction:column;overflow:hidden">
            @if(isset($trip) && isset($messages))
                {{-- Header --}}
                <div class="panel-header">
                    <div style="display:flex;align-items:center;gap:12px">
                        <div style="display:flex">
                            <div class="avatar" style="background:#EEF2FF;color:#3730A3">{{ strtoupper(substr($trip->user->first_name ?? 'U',0,1)) }}</div>
                            <div class="avatar" style="background:#ECFDF5;color:#065F46;margin-left:-8px;border:2px solid #fff">{{ strtoupper(substr($trip->driver->first_name ?? 'D',0,1)) }}</div>
                        </div>
                        <div>
                            <div style="font-size:13px;font-weight:600">
                                <span style="color:#3730A3">{{ $trip->user->first_name ?? 'User' }} {{ $trip->user->last_name ?? '' }}</span>
                                <span style="color:#94A3B8;margin:0 6px">↔</span>
                                <span style="color:#065F46">{{ $trip->driver->first_name ?? 'Driver' }} {{ $trip->driver->last_name ?? '' }}</span>
                            </div>
                            <div style="font-size:11px;color:#94A3B8">Trip #{{ $trip->id }} · {{ $messages->count() }} message(s)</div>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px">
                        @if(isset($trip->user) && $trip->user->phone)
                            <span class="badge badge-indigo">{{ $trip->user->phone }}</span>
                        @endif
                        @if(isset($trip->driver) && $trip->driver->phone)
                            <span class="badge badge-success">{{ $trip->driver->phone }}</span>
                        @endif
                    </div>
                </div>

                {{-- Messages --}}
                <div id="messagesBox" style="flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:14px;background:#FAFBFC">
                    @forelse($messages as $message)
                        @php $isUser = str_contains($message->sender_type, 'User'); @endphp
                        <div style="display:flex;{{ $isUser ? '' : 'flex-direction:row-reverse' }};align-items:flex-end;gap:8px">
                            <div class="avatar" style="{{ $isUser ? 'background:#EEF2FF;color:#3730A3' : 'background:#ECFDF5;color:#065F46' }};flex-shrink:0">
                                {{ $isUser ? strtoupper(substr($trip->user->first_name ?? 'U',0,1)) : strtoupper(substr($trip->driver->first_name ?? 'D',0,1)) }}
                            </div>
                            <div style="max-width:60%">
                                <div style="font-size:11px;color:#94A3B8;margin-bottom:4px;text-align:{{ $isUser ? 'left' : 'right' }}">
                                    {{ $isUser ? ($trip->user->first_name ?? 'User') : ($trip->driver->first_name ?? 'Driver') }}
                                </div>
                                <div style="padding:10px 14px;border-radius:12px;font-size:13px;line-height:1.5;
                                    {{ $isUser ? 'background:#fff;border:1px solid #E2E8F0;color:#0F172A;border-radius:12px 12px 12px 4px' : 'background:#1DA1F2;color:#fff;border-radius:12px 12px 4px 12px' }}">
                                    {{ $message->content }}
                                </div>
                                <div style="font-size:11px;color:#CBD5E1;margin-top:3px;text-align:{{ $isUser ? 'left' : 'right' }}">
                                    {{ $message->created_at->format('H:i') }}
                                    @if(!$isUser)
                                        @if($message->is_read) @else @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div style="flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;color:#94A3B8">
                            <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            <p style="font-size:14px">Aucun message dans cette conversation</p>
                        </div>
                    @endforelse
                </div>

                <div style="padding:10px 16px;border-top:1px solid #F1F5F9;background:#FAFBFC;text-align:center">
                    <span style="font-size:11px;color:#94A3B8">Mode lecture seule — Interface d'administration</span>
                </div>

            @else
                <div style="flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;color:#94A3B8">
                    <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <div style="text-align:center">
                        <p style="font-size:15px;font-weight:600;color:#64748B">Sélectionnez une conversation</p>
                        <p style="font-size:13px;margin-top:4px">Cliquez sur une conversation dans la liste</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const box = document.getElementById('messagesBox');
if (box) box.scrollTop = box.scrollHeight;
@if(isset($trip))
setInterval(() => location.reload(), 10000);
@endif
</script>
@endpush
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     