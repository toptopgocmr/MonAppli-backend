@extends('admin.layouts.app')
@section('title', 'Société — ' . $company->name)

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;max-width:900px">

    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px">
        <a href="{{ route('admin.companies.index') }}" style="color:#2563eb;font-size:14px;text-decoration:none">← Retour aux sociétés</a>
        <div style="display:flex;gap:10px">
            <a href="{{ route('admin.companies.edit', $company) }}" class="btn btn-primary" style="font-size:13px;padding:8px 16px">Modifier</a>
            @if($company->status === 'active')
                <form method="POST" action="{{ route('admin.companies.suspend', $company) }}" style="display:inline">
                    @csrf
                    <button type="submit" onclick="return confirm('Suspendre cette société ?')"
                            style="font-size:13px;padding:8px 16px;border-radius:10px;background:#fff7ed;color:#ea580c;font-weight:600;border:1px solid #fed7aa;cursor:pointer">
                        Suspendre
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.companies.activate', $company) }}" style="display:inline">
                    @csrf
                    <button type="submit"
                            style="font-size:13px;padding:8px 16px;border-radius:10px;background:#f0fdf4;color:#16a34a;font-weight:600;border:1px solid #bbf7d0;cursor:pointer">
                        Activer
                    </button>
                </form>
            @endif
            <form method="POST" action="{{ route('admin.companies.destroy', $company) }}" style="display:inline"
                  onsubmit="return confirm('Supprimer définitivement cette société ?')">
                @csrf @method('DELETE')
                <button type="submit"
                        style="font-size:13px;padding:8px 16px;border-radius:10px;background:#fee2e2;color:#dc2626;font-weight:600;border:1px solid #fca5a5;cursor:pointer">
                    Supprimer
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;padding:12px 16px;border-radius:10px;font-size:13px">
        ✓ {{ session('success') }}
    </div>
    @endif

    <!-- Profil société -->
    <div class="card">
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid #f1f5f9">
            @if($company->logo_url)
                <img src="{{ $company->logo_url }}" style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:3px solid #e0e7ff">
            @else
                <div style="width:60px;height:60px;border-radius:50%;background:#6366f1;color:#fff;font-size:24px;font-weight:700;display:flex;align-items:center;justify-content:center">
                    {{ strtoupper(substr($company->name, 0, 1)) }}
                </div>
            @endif
            <div>
                <h2 style="font-size:20px;font-weight:700;color:#1e293b;margin:0">{{ $company->name }}</h2>
                <p style="color:#64748b;font-size:13px;margin:2px 0">{{ $company->email }}</p>
                @php
                    $statusStyle = match($company->status) {
                        'active'    => 'background:#dcfce7;color:#16a34a',
                        'pending'   => 'background:#fef9c3;color:#ca8a04',
                        'suspended' => 'background:#fee2e2;color:#dc2626',
                        default     => 'background:#f1f5f9;color:#475569',
                    };
                @endphp
                <span style="font-size:12px;font-weight:600;padding:3px 10px;border-radius:20px;{{ $statusStyle }}">
                    {{ ['active'=>'Active','pending'=>'En attente','suspended'=>'Suspendue'][$company->status] ?? $company->status }}
                </span>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px">
            @foreach([
                ['Type','type_libelle'],['Téléphone','phone'],['Ville','city'],
                ['Pays','country'],['Adresse','address'],['Contact','contact_name'],
                ['Commission','commission_rate'],['Inscrit le','created_at']
            ] as [$label,$field])
            <div style="background:#f8fafc;border-radius:10px;padding:12px 14px">
                <p style="font-size:11px;color:#94a3b8;text-transform:uppercase;font-weight:600;margin-bottom:4px">{{ $label }}</p>
                <p style="font-size:14px;font-weight:600;color:#334155">
                    @if($field === 'commission_rate')
                        {{ $company->commission_rate }}%
                    @elseif($field === 'created_at')
                        {{ $company->created_at->format('d/m/Y') }}
                    @else
                        {{ $company->{$field} ?? '—' }}
                    @endif
                </p>
            </div>
            @endforeach
        </div>

        @if($company->description)
        <div style="margin-top:16px;padding-top:16px;border-top:1px solid #f1f5f9">
            <p style="font-size:12px;color:#94a3b8;font-weight:600;text-transform:uppercase;margin-bottom:6px">Description</p>
            <p style="font-size:14px;color:#475569;line-height:1.6">{{ $company->description }}</p>
        </div>
        @endif
    </div>

    <!-- Chauffeurs assignés -->
    <div class="card">
        <h3 style="font-size:16px;font-weight:700;color:#1e293b;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f1f5f9">
            Chauffeurs assignés ({{ $company->drivers->count() }})
        </h3>

        @if($company->drivers->count())
        <div style="overflow-x:auto;margin-bottom:20px">
            <table style="width:100%;border-collapse:collapse">
                <thead style="background:#f8fafc">
                    <tr>
                        @foreach(['Chauffeur','Téléphone','Véhicule','Statut','Action'] as $h)
                        <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;color:#64748b">{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($company->drivers as $driver)
                    <tr style="border-top:1px solid #f1f5f9">
                        <td style="padding:12px 14px">
                            <div style="display:flex;align-items:center;gap:8px">
                                @if($driver->profile_photo)
                                    <img src="{{ $driver->profile_photo }}" style="width:30px;height:30px;border-radius:50%;object-fit:cover">
                                @else
                                    <div style="width:30px;height:30px;border-radius:50%;background:#2563eb;color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center">
                                        {{ strtoupper(substr($driver->first_name, 0, 1)) }}
                                    </div>
                                @endif
                                <span style="font-size:13px;font-weight:600;color:#1e293b">{{ $driver->first_name }} {{ $driver->last_name }}</span>
                            </div>
                        </td>
                        <td style="padding:12px 14px;font-size:13px;color:#64748b">{{ $driver->phone }}</td>
                        <td style="padding:12px 14px;font-size:13px;color:#64748b">
                            {{ $driver->vehicle_brand ?? '—' }} {{ $driver->vehicle_plate ? '('.$driver->vehicle_plate.')' : '' }}
                        </td>
                        <td style="padding:12px 14px">
                            @php $sc = ['approved'=>['#dcfce7','#16a34a','Approuvé'],'pending'=>['#fef9c3','#ca8a04','En attente'],'rejected'=>['#fee2e2','#dc2626','Rejeté']][$driver->status] ?? ['#f1f5f9','#475569',$driver->status]; @endphp
                            <span style="font-size:11px;font-weight:600;padding:3px 8px;border-radius:20px;background:{{ $sc[0] }};color:{{ $sc[1] }}">{{ $sc[2] }}</span>
                        </td>
                        <td style="padding:12px 14px">
                            <form method="POST" action="{{ route('admin.companies.remove-driver', [$company, $driver]) }}">
                                @csrf @method('DELETE')
                                <button type="submit" onclick="return confirm('Retirer ce chauffeur ?')"
                                        style="font-size:12px;padding:4px 10px;border-radius:8px;background:#fee2e2;color:#dc2626;font-weight:600;border:none;cursor:pointer">
                                    Retirer
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p style="color:#94a3b8;font-size:14px;margin-bottom:20px">Aucun chauffeur assigné.</p>
        @endif

        <!-- Assigner un chauffeur -->
        @if($availableDrivers->count())
        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:16px">
            <p style="font-size:13px;font-weight:600;color:#0369a1;margin-bottom:10px">Assigner un chauffeur approuvé</p>
            <form method="POST" action="{{ route('admin.companies.assign-driver', $company) }}" style="display:flex;gap:10px;flex-wrap:wrap">
                @csrf
                <select name="driver_id" required style="border:1px solid #bae6fd;border-radius:10px;padding:8px 14px;font-size:13px;flex:1;min-width:220px;outline:none">
                    <option value="">— Choisir un chauffeur —</option>
                    @foreach($availableDrivers as $d)
                        <option value="{{ $d->id }}">{{ $d->first_name }} {{ $d->last_name }} ({{ $d->phone }})</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary" style="font-size:13px;padding:8px 16px">Assigner</button>
            </form>
        </div>
        @else
        <p style="font-size:13px;color:#94a3b8">Aucun chauffeur approuvé disponible sans société.</p>
        @endif
    </div>

</div>
@endsection
