@extends('admin.layouts.app')
@section('title','Comptes Agents')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px">

    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px">
        <div>
            <h1 class="page-title">Comptes Agents des Sociétés</h1>
            <p class="page-sub">Comptable, RH, directeur général, flotte, marketing, commercial — toutes sociétés confondues</p>
        </div>
    </div>

    {{-- Stats --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px">
        <div class="stat-card" style="border-left:4px solid #6366f1">
            <div class="lbl">Total agents</div>
            <div class="val" style="color:#6366f1">{{ $stats['total'] }}</div>
        </div>
        <div class="stat-card" style="border-left:4px solid #22c55e">
            <div class="lbl">Actifs</div>
            <div class="val" style="color:#16a34a">{{ $stats['active'] }}</div>
        </div>
        <div class="stat-card" style="border-left:4px solid #ef4444">
            <div class="lbl">Suspendus</div>
            <div class="val" style="color:#dc2626">{{ $stats['suspended'] }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, email, téléphone..."
               style="border:1px solid #e2e8f0;border-radius:10px;padding:8px 14px;font-size:14px;width:220px;outline:none">

        <select name="company_id" style="border:1px solid #e2e8f0;border-radius:10px;padding:8px 14px;font-size:14px;outline:none">
            <option value="">Toutes les sociétés</option>
            @foreach($companies as $company)
                <option value="{{ $company->id }}" {{ (string) request('company_id') === (string) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
            @endforeach
        </select>

        <select name="role" style="border:1px solid #e2e8f0;border-radius:10px;padding:8px 14px;font-size:14px;outline:none">
            <option value="">Tous les rôles</option>
            @foreach(\App\Models\CompanyAgent::ROLES as $val => $label)
                <option value="{{ $val }}" {{ request('role') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>

        <select name="status" style="border:1px solid #e2e8f0;border-radius:10px;padding:8px 14px;font-size:14px;outline:none">
            <option value="">Tous les statuts</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspendu</option>
        </select>

        <button type="submit" class="btn btn-primary" style="padding:8px 18px;font-size:14px">Filtrer</button>
        <a href="{{ route('admin.company-agents.index') }}" class="btn btn-secondary" style="padding:8px 18px;font-size:14px">Reset</a>
    </form>

    {{-- Table --}}
    <div class="card" style="padding:0;overflow:hidden">
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse">
                <thead style="background:#f8fafc">
                    <tr>
                        @foreach(['Agent','Société','Rôle','Permissions','Statut','Créé le','Actions'] as $h)
                        <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;color:#64748b;white-space:nowrap">{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($agents as $agent)
                    @php
                        $statusStyle = $agent->status === 'active'
                            ? 'background:#dcfce7;color:#16a34a'
                            : 'background:#fee2e2;color:#dc2626';
                        $statusLabel = $agent->status === 'active' ? 'Actif' : 'Suspendu';
                        $permCount = is_array($agent->permissions) ? count($agent->permissions) : 0;
                    @endphp
                    <tr style="border-top:1px solid #f1f5f9">
                        <td style="padding:14px 16px">
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:34px;height:34px;border-radius:50%;background:#6366f1;color:#fff;font-weight:700;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                    {{ strtoupper(substr($agent->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div style="font-size:14px;font-weight:600;color:#1e293b">{{ $agent->name }}</div>
                                    <div style="font-size:12px;color:#94a3b8">{{ $agent->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="padding:14px 16px;font-size:13px;color:#475569">
                            {{ $agent->company->name ?? '—' }}
                        </td>
                        <td style="padding:14px 16px;font-size:13px;color:#475569">
                            {{ $agent->role_label }}
                            @if($agent->isDirecteurGeneral())
                                <div style="font-size:11px;color:#7c3aed;font-weight:600">Accès complet</div>
                            @endif
                        </td>
                        <td style="padding:14px 16px;font-size:13px;color:#475569;text-align:center">
                            {{ $agent->isDirecteurGeneral() ? 'Toutes' : $permCount . ' section(s)' }}
                        </td>
                        <td style="padding:14px 16px">
                            <span style="font-size:12px;font-weight:600;padding:4px 10px;border-radius:20px;{{ $statusStyle }}">{{ $statusLabel }}</span>
                        </td>
                        <td style="padding:14px 16px;font-size:12px;color:#94a3b8">{{ $agent->created_at?->format('d/m/Y') }}</td>
                        <td style="padding:14px 16px">
                            <div style="display:flex;gap:6px;flex-wrap:wrap">
                                <a href="{{ route('admin.company-agents.show', $agent->id) }}"
                                   style="font-size:12px;padding:5px 10px;border-radius:8px;background:#eff6ff;color:#2563eb;font-weight:600;text-decoration:none">Voir</a>
                                @if($agent->status === 'active')
                                    <form method="POST" action="{{ route('admin.company-agents.suspend', $agent->id) }}" style="display:inline">
                                        @csrf
                                        <button type="submit" onclick="return confirm('Suspendre cet agent ?')"
                                                style="font-size:12px;padding:5px 10px;border-radius:8px;background:#fff7ed;color:#ea580c;font-weight:600;border:none;cursor:pointer">Suspendre</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.company-agents.activate', $agent->id) }}" style="display:inline">
                                        @csrf
                                        <button type="submit"
                                                style="font-size:12px;padding:5px 10px;border-radius:8px;background:#f0fdf4;color:#16a34a;font-weight:600;border:none;cursor:pointer">Activer</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="padding:40px;text-align:center;color:#94a3b8;font-size:14px">Aucun agent trouvé</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($agents->hasPages())
        <div style="padding:16px 20px;border-top:1px solid #f1f5f9">
            {{ $agents->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
