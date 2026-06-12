@extends('admin.layouts.app')
@section('title','Clients')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px">

<div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px">
    <div>
        <h1 class="page-title">Gestion des Clients</h1>
        <p class="page-sub">Liste de tous les utilisateurs inscrits</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px">
    <div class="stat-card" style="border-left:4px solid #1DA1F2">
        <div class="lbl">Total Clients</div>
        <div class="val" style="color:#1DA1F2">{{ $users->total() }}</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #22c55e">
        <div class="lbl">Actifs</div>
        <div class="val" style="color:#16a34a">{{ \App\Models\User\User::where('status','active')->count() }}</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #ef4444">
        <div class="lbl">Bloqués</div>
        <div class="val" style="color:#dc2626">{{ \App\Models\User\User::where('status','inactive')->count() }}</div>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" action="{{ route('admin.users.index') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;width:100%">
        <div style="flex:1;min-width:200px">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, téléphone, email..." class="ttg-input">
        </div>
        <select name="status" class="ttg-select" style="min-width:150px">
            <option value="">Tous les statuts</option>
            <option value="active"   {{ request('status')=='active'   ?'selected':'' }}>Actifs</option>
            <option value="inactive" {{ request('status')=='inactive' ?'selected':'' }}>Bloqués</option>
        </select>
        <select name="country" class="ttg-select" style="min-width:150px">
            <option value="">Tous les pays</option>
            @foreach($countries as $country)
                <option value="{{ $country }}" {{ request('country')==$country?'selected':'' }}>{{ $country }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary">Filtrer</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-gray">Reset</a>
    </form>
</div>

<div class="panel">
    <div style="overflow-x:auto">
        <table class="ttg-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Téléphone</th>
                    <th>Email</th>
                    <th>Pays / Ville</th>
                    <th>Statut</th>
                    <th>Inscrit le</th>
                    <th style="text-align:center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            @if($user->profile_photo)
                                <img src="{{ asset('storage/'.$user->profile_photo) }}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid #e2e8f0">
                            @else
                                <div class="avatar" style="background:#FFC107;color:#0a0f1e">{{ strtoupper(substr($user->first_name,0,1)) }}</div>
                            @endif
                            <p style="font-weight:600;color:#0f172a;font-size:13px">{{ $user->first_name }} {{ $user->last_name }}</p>
                        </div>
                    </td>
                    <td>{{ $user->phone }}</td>
                    <td style="color:#64748b">{{ $user->email ?? '—' }}</td>
                    <td>
                        <p style="font-size:13px;color:#374151">{{ $user->country }}</p>
                        <p style="font-size:11px;color:#94a3b8">{{ $user->city }}</p>
                    </td>
                    <td>
                        @if($user->status=='active')
                            <span class="badge badge-green">Actif</span>
                        @else
                            <span class="badge badge-red">Bloqué</span>
                        @endif
                    </td>
                    <td style="font-size:12px;color:#64748b">{{ $user->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div style="display:flex;justify-content:center;gap:6px">
                            <a href="{{ route('admin.users.show',$user->id) }}" class="btn btn-gray btn-sm">Voir</a>
                            @if($user->status=='active')
                                <form method="POST" action="{{ route('admin.users.block',$user->id) }}">@csrf
                                    <button class="btn btn-sm" style="background:#fff7ed;color:#c2410c;border:1px solid #fed7aa" onclick="return confirm('Bloquer ?')"></button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.users.activate',$user->id) }}">@csrf
                                    <button class="btn btn-success btn-sm"></button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.users.destroy',$user->id) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm" onclick="return confirm('Supprimer ?')">Supprimer </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="padding:40px;text-align:center;color:#94a3b8">Aucun client trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div style="padding:16px 20px;border-top:1px solid #f1f5f9">
        {{ $users->appends(request()->query())->links() }}
    </div>
    @endif
</div>

</div>
@endsection
