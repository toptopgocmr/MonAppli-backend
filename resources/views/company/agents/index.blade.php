@extends('company.layouts.app')
@section('title', 'Agents')

@section('content')

<div class="aws-crumb"><a href="{{ route('company.dashboard') }}">Dashboard</a> › Agents</div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <div class="aws-page-title">Agents de la société</div>
    <a href="{{ route('company.agents.create') }}" class="aws-btn aws-btn-primary">+ Nouvel agent</a>
</div>

<p style="font-size:13px;color:var(--aws-sub);margin-bottom:16px;max-width:720px">
    Ajoutez vos collaborateurs (comptable, RH, directeur général, responsable flotte, marketing, commercial...).
    Chacun se connecte avec son propre email/mot de passe sur la page de connexion société, et n'accède qu'aux
    sections que vous lui autorisez ci-dessous.
</p>

<div class="aws-panel">
    <div style="overflow-x:auto">
        <table class="aws-table">
            <thead>
                <tr>
                    <th>Agent</th>
                    <th>Rôle</th>
                    <th>Accès</th>
                    <th>Statut</th>
                    <th>Dernière connexion</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($agents as $agent)
                <tr>
                    <td>
                        <div style="font-weight:600;font-size:13px">{{ $agent->name }}</div>
                        <div style="font-size:12px;color:var(--aws-sub)">{{ $agent->email }}</div>
                    </td>
                    <td>{{ $agent->role_label }}</td>
                    <td style="font-size:12px;color:var(--aws-sub)">
                        @if($agent->role === 'directeur_general')
                            <span class="aws-badge aws-badge-blue">Accès complet</span>
                        @elseif(empty($agent->permissions))
                            —
                        @else
                            {{ collect($agent->permissions)->map(fn($p) => \App\Models\CompanyAgent::PERMISSIONS[$p] ?? $p)->join(', ') }}
                        @endif
                    </td>
                    <td>
                        @if($agent->status === 'active')
                            <span class="aws-badge aws-badge-green">Actif</span>
                        @else
                            <span class="aws-badge aws-badge-red">Suspendu</span>
                        @endif
                    </td>
                    <td style="font-size:12px;color:var(--aws-sub)">{{ $agent->last_login_at?->format('d/m/Y H:i') ?? 'Jamais' }}</td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            <a href="{{ route('company.agents.edit', $agent->id) }}" class="aws-btn aws-btn-normal" style="padding:4px 10px;font-size:12px">Modifier</a>
                            @if($agent->status === 'active')
                            <form method="POST" action="{{ route('company.agents.suspend', $agent->id) }}">
                                @csrf
                                <button type="submit" class="aws-btn" style="padding:4px 10px;font-size:12px;background:#fff;border-color:#df8244;color:#df8244">Suspendre</button>
                            </form>
                            @else
                            <form method="POST" action="{{ route('company.agents.activate', $agent->id) }}">
                                @csrf
                                <button type="submit" class="aws-btn aws-btn-normal" style="padding:4px 10px;font-size:12px">Activer</button>
                            </form>
                            @endif
                            <form method="POST" action="{{ route('company.agents.destroy', $agent->id) }}" onsubmit="return confirm('Supprimer définitivement cet agent ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="aws-btn aws-btn-danger" style="padding:4px 10px;font-size:12px">Supprimer</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:40px;color:var(--aws-sub)">
                        Aucun agent pour le moment —
                        <a href="{{ route('company.agents.create') }}" style="color:var(--aws-blue)">Ajouter un agent</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
