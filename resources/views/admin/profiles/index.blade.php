@extends('admin.layouts.app')
@section('title','Administrateurs')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px">

<div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px">
    <div>
        <h1 class="page-title">Profils Administrateurs</h1>
        <p class="page-sub">Gérez les administrateurs de la plateforme</p>
    </div>
    <a href="{{ route('admin.profiles.create') }}" class="btn btn-primary">Nouvel Admin</a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px">
    <div class="stat-card" style="border-left:4px solid #1DA1F2">
        <div class="lbl">Total Admins</div>
        <div class="val" style="color:#1DA1F2">{{ $admins->count() }}</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #22c55e">
        <div class="lbl">Actifs</div>
        <div class="val" style="color:#16a34a">{{ $admins->where('status','active')->count() }}</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #ef4444">
        <div class="lbl">Bloqués</div>
        <div class="val" style="color:#dc2626">{{ $admins->where('status','inactive')->count() }}</div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2>Liste des administrateurs</h2>
    </div>
    <div style="overflow-x:auto">
        <table class="ttg-table">
            <thead>
                <tr>
                    <th>Administrateur</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Créé le</th>
                    <th style="text-align:center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admins as $admin)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div class="avatar" style="background:{{ $admin->status==='active' ? '#1DA1F2' : '#9ca3af' }};color:#fff">
                                {{ strtoupper(substr($admin->first_name,0,1)) }}
                            </div>
                            <div>
                                <p style="font-weight:600;color:#0f172a;font-size:13px">
                                    {{ $admin->first_name }} {{ $admin->last_name }}
                                    @if($admin->id === session('admin_id'))
                                        <span style="font-size:11px;color:#1DA1F2;font-weight:400">(Vous)</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </td>
                    <td style="color:#475569">{{ $admin->email }}</td>
                    <td style="color:#475569">{{ $admin->phone ?? '—' }}</td>
                    <td><span class="badge badge-yellow">{{ $admin->role->name ?? '—' }}</span></td>
                    <td>
                        @if($admin->status==='active')
                            <span class="badge badge-green">Actif</span>
                        @else
                            <span class="badge badge-red">Bloqué</span>
                        @endif
                    </td>
                    <td style="font-size:12px;color:#64748b">{{ $admin->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div style="display:flex;justify-content:center;gap:6px;flex-wrap:wrap">
                            <a href="{{ route('admin.profiles.show',$admin->id) }}" class="btn btn-gray btn-sm">Voir</a>
                            <a href="{{ route('admin.profiles.edit',$admin->id) }}" class="btn btn-primary btn-sm">Modifier</a>
                            @if($admin->id !== session('admin_id'))
                                @if($admin->status==='active')
                                    <form method="POST" action="{{ route('admin.profiles.block',$admin->id) }}">@csrf
                                        <button class="btn btn-sm" style="background:#fff7ed;color:#c2410c;border:1px solid #fed7aa" onclick="return confirm('Bloquer ?')">Bloquer</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.profiles.activate',$admin->id) }}">@csrf
                                        <button class="btn btn-success btn-sm">Activer</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.profiles.destroy',$admin->id) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm" onclick="return confirm('Supprimer ?')">Supprimer </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="padding:40px;text-align:center;color:#94a3b8">Aucun administrateur trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

</div>
@endsection
