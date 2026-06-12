@extends('admin.layouts.app')
@section('title','Sociétés')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px">

    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px">
        <div>
            <h1 class="page-title">Gestion des Sociétés</h1>
            <p class="page-sub">Location de véhicules & covoiturage privé</p>
        </div>
        <a href="{{ route('admin.companies.create') }}" class="btn btn-primary">+ Nouvelle Société</a>
    </div>

    {{-- Stats --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px">
        <div class="stat-card" style="border-left:4px solid #6366f1">
            <div class="lbl">Total</div>
            <div class="val" style="color:#6366f1">{{ $stats['total'] }}</div>
        </div>
        <div class="stat-card" style="border-left:4px solid #22c55e">
            <div class="lbl">Actives</div>
            <div class="val" style="color:#16a34a">{{ $stats['active'] }}</div>
        </div>
        <div class="stat-card" style="border-left:4px solid #f59e0b">
            <div class="lbl">En attente</div>
            <div class="val" style="color:#d97706">{{ $stats['pending'] }}</div>
        </div>
        <div class="stat-card" style="border-left:4px solid #ef4444">
            <div class="lbl">Suspendues</div>
            <div class="val" style="color:#dc2626">{{ $stats['suspended'] }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, email, ville..."
               style="border:1px solid #e2e8f0;border-radius:10px;padding:8px 14px;font-size:14px;width:220px;outline:none">
        <select name="status" style="border:1px solid #e2e8f0;border-radius:10px;padding:8px 14px;font-size:14px;outline:none">
            <option value="">Tous les statuts</option>
            @foreach(['active'=>'Active','pending'=>'En attente','suspended'=>'Suspendue'] as $v => $l)
                <option value="{{ $v }}" {{ request('status') === $v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
        <select name="type" style="border:1px solid #e2e8f0;border-radius:10px;padding:8px 14px;font-size:14px;outline:none">
            <option value="">Tous les types</option>
            @foreach(['location'=>'Location','covoiturage'=>'Covoiturage','both'=>'Les deux'] as $v => $l)
                <option value="{{ $v }}" {{ request('type') === $v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary" style="padding:8px 18px;font-size:14px">Filtrer</button>
    </form>

    {{-- Table --}}
    <div class="card" style="padding:0;overflow:hidden">
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse">
                <thead style="background:#f8fafc">
                    <tr>
                        @foreach(['Société','Contact','Type','Statut','Chauffeurs','Commission','Actions'] as $h)
                        <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;color:#64748b;white-space:nowrap">{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($companies as $company)
                    @php
                        $statusStyle = match($company->status) {
                            'active'    => 'background:#dcfce7;color:#16a34a',
                            'pending'   => 'background:#fef9c3;color:#ca8a04',
                            'suspended' => 'background:#fee2e2;color:#dc2626',
                            default     => 'background:#f1f5f9;color:#475569',
                        };
                        $statusLabel = ['active'=>'Active','pending'=>'En attente','suspended'=>'Suspendue'][$company->status] ?? $company->status;
                        $typeLabel   = ['location'=>'Location','covoiturage'=>'Covoiturage','both'=>'Les deux'][$company->type] ?? $company->type;
                    @endphp
                    <tr style="border-top:1px solid #f1f5f9">
                        <td style="padding:14px 16px">
                            <div style="display:flex;align-items:center;gap:10px">
                                @if($company->logo_url)
                                    <img src="{{ $company->logo_url }}" style="width:34px;height:34px;border-radius:50%;object-fit:cover">
                                @else
                                    <div style="width:34px;height:34px;border-radius:50%;background:#6366f1;color:#fff;font-weight:700;font-size:14px;display:flex;align-items:center;justify-content:center">
                                        {{ strtoupper(substr($company->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div style="font-size:14px;font-weight:600;color:#1e293b">{{ $company->name }}</div>
                                    <div style="font-size:12px;color:#94a3b8">{{ $company->city ?? '' }}{{ $company->country ? ', '.$company->country : '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="padding:14px 16px;font-size:13px;color:#475569">
                            <div>{{ $company->email }}</div>
                            <div style="color:#94a3b8;font-size:12px">{{ $company->phone ?? '' }}</div>
                        </td>
                        <td style="padding:14px 16px;font-size:13px;color:#475569">{{ $typeLabel }}</td>
                        <td style="padding:14px 16px">
                            <span style="font-size:12px;font-weight:600;padding:4px 10px;border-radius:20px;{{ $statusStyle }}">{{ $statusLabel }}</span>
                        </td>
                        <td style="padding:14px 16px;font-size:14px;font-weight:600;color:#334155;text-align:center">
                            {{ $company->drivers_count }}
                        </td>
                        <td style="padding:14px 16px;font-size:13px;color:#475569">{{ $company->commission_rate }}%</td>
                        <td style="padding:14px 16px">
                            <div style="display:flex;gap:6px;flex-wrap:wrap">
                                <a href="{{ route('admin.companies.show', $company) }}"
                                   style="font-size:12px;padding:5px 10px;border-radius:8px;background:#eff6ff;color:#2563eb;font-weight:600;text-decoration:none">Voir</a>
                                <a href="{{ route('admin.companies.edit', $company) }}"
                                   style="font-size:12px;padding:5px 10px;border-radius:8px;background:#f0fdf4;color:#16a34a;font-weight:600;text-decoration:none">Modifier</a>
                                @if($company->status === 'active')
                                    <form method="POST" action="{{ route('admin.companies.suspend', $company) }}" style="display:inline">
                                        @csrf
                                        <button type="submit" onclick="return confirm('Suspendre cette société ?')"
                                                style="font-size:12px;padding:5px 10px;border-radius:8px;background:#fff7ed;color:#ea580c;font-weight:600;border:none;cursor:pointer">Suspendre</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.companies.activate', $company) }}" style="display:inline">
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
                        <td colspan="7" style="padding:40px;text-align:center;color:#94a3b8;font-size:14px">Aucune société trouvée</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($companies->hasPages())
        <div style="padding:16px 20px;border-top:1px solid #f1f5f9">
            {{ $companies->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
