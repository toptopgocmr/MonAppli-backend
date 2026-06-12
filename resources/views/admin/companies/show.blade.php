@extends('admin.layouts.app')
@section('title', 'Société — ' . $company->name)

@section('content')

<style>
/* ── AWS-style variables ── */
:root {
    --aws-bg:       #f2f3f3;
    --aws-border:   #d5dbdb;
    --aws-panel:    #ffffff;
    --aws-header:   #16191f;
    --aws-orange:   #ec7211;
    --aws-orange2:  #dd6b10;
    --aws-blue:     #0073bb;
    --aws-text:     #16191f;
    --aws-sub:      #687078;
    --aws-red:      #d13212;
    --aws-green:    #1d8102;
    --aws-yellow:   #8a6116;
}

.aws-page    { background:var(--aws-bg); min-height:100vh; font-family:'Amazon Ember','Helvetica Neue',Helvetica,Arial,sans-serif; }
.aws-crumb   { font-size:12px; color:var(--aws-sub); margin-bottom:4px; }
.aws-crumb a { color:var(--aws-blue); text-decoration:none; }
.aws-crumb a:hover { text-decoration:underline; }
.aws-h1      { font-size:20px; font-weight:700; color:var(--aws-header); margin:0; }

/* Panels */
.aws-panel        { background:var(--aws-panel); border:1px solid var(--aws-border); border-radius:4px; margin-bottom:16px; }
.aws-panel-header { background:#fafafa; border-bottom:1px solid var(--aws-border); padding:12px 20px; display:flex; align-items:center; justify-content:space-between; border-radius:4px 4px 0 0; }
.aws-panel-title  { font-size:14px; font-weight:700; color:var(--aws-header); margin:0; }
.aws-panel-body   { padding:20px; }

/* Buttons */
.aws-btn          { display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:600; padding:7px 16px; border-radius:4px; cursor:pointer; border:1px solid; text-decoration:none; transition:all .15s; }
.aws-btn-primary  { background:var(--aws-orange); border-color:var(--aws-orange2); color:#fff; }
.aws-btn-primary:hover { background:var(--aws-orange2); }
.aws-btn-normal   { background:#fff; border-color:#aab7b8; color:var(--aws-header); }
.aws-btn-normal:hover { background:#f2f3f3; }
.aws-btn-danger   { background:#fff; border-color:var(--aws-red); color:var(--aws-red); }
.aws-btn-danger:hover { background:#fdf3f1; }
.aws-btn-warn     { background:#fff; border-color:#df8244; color:#df8244; }
.aws-btn-warn:hover { background:#fef9f5; }

/* Detail grid */
.aws-detail-grid  { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:0; }
.aws-detail-item  { padding:14px 0; border-bottom:1px solid #f2f3f3; }
.aws-detail-item:nth-child(odd) { padding-right:24px; }
.aws-detail-label { font-size:11px; font-weight:700; color:var(--aws-sub); text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; }
.aws-detail-value { font-size:14px; color:var(--aws-header); font-weight:500; }

/* Status badge */
.aws-badge        { display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:600; padding:2px 8px; border-radius:3px; }
.aws-badge-green  { background:#ebf8ee; color:var(--aws-green); border:1px solid #b2dfba; }
.aws-badge-yellow { background:#fef9f0; color:var(--aws-yellow); border:1px solid #f5d798; }
.aws-badge-red    { background:#fdf3f1; color:var(--aws-red);   border:1px solid #f5b6a7; }
.aws-badge::before { content:'●'; font-size:8px; }

/* Table */
.aws-table        { width:100%; border-collapse:collapse; font-size:13px; }
.aws-table thead tr { background:#fafafa; border-bottom:2px solid var(--aws-border); }
.aws-table th     { padding:10px 16px; text-align:left; font-size:11px; font-weight:700; color:var(--aws-sub); text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
.aws-table td     { padding:12px 16px; border-bottom:1px solid #f2f3f3; color:var(--aws-header); vertical-align:middle; }
.aws-table tbody tr:hover { background:#fafafa; }

/* Alert */
.aws-alert-success { background:#ebf8ee; border:1px solid #b2dfba; color:var(--aws-green); padding:10px 16px; border-radius:4px; font-size:13px; margin-bottom:16px; }
</style>

<div class="aws-page" style="padding:20px 24px">

    <!-- Breadcrumb -->
    <div class="aws-crumb">
        <a href="{{ route('admin.companies.index') }}">Sociétés</a> › {{ $company->name }}
    </div>

    <!-- Page header -->
    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;margin-bottom:20px">
        <div style="display:flex;align-items:center;gap:12px">
            <div style="width:42px;height:42px;border-radius:4px;background:#{{ substr(md5($company->name),0,6) }};display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;font-weight:700;flex-shrink:0">
                {{ strtoupper(substr($company->name,0,1)) }}
            </div>
            <div>
                <h1 class="aws-h1">{{ $company->name }}</h1>
                <p style="font-size:13px;color:var(--aws-sub);margin:2px 0 0">{{ $company->email }}</p>
            </div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="{{ route('admin.companies.edit', $company) }}" class="aws-btn aws-btn-primary">
                ✎ Modifier
            </a>
            @if($company->status === 'active')
                <form method="POST" action="{{ route('admin.companies.suspend', $company) }}" style="display:inline">
                    @csrf
                    <button type="submit" onclick="return confirm('Suspendre cette société ?')" class="aws-btn aws-btn-warn">
                        ⏸ Suspendre
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.companies.activate', $company) }}" style="display:inline">
                    @csrf
                    <button type="submit" class="aws-btn aws-btn-normal">
                        ▶ Activer
                    </button>
                </form>
            @endif
            <form method="POST" action="{{ route('admin.companies.destroy', $company) }}" style="display:inline"
                  onsubmit="return confirm('Supprimer définitivement cette société ?')">
                @csrf @method('DELETE')
                <button type="submit" class="aws-btn aws-btn-danger">
                    ✕ Supprimer
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
    <div class="aws-alert-success">✓ {{ session('success') }}</div>
    @endif

    <!-- Details panel -->
    <div class="aws-panel">
        <div class="aws-panel-header">
            <p class="aws-panel-title">Détails</p>
            @php
                $statusConf = [
                    'active'    => ['aws-badge-green',  'Active'],
                    'pending'   => ['aws-badge-yellow', 'En attente'],
                    'suspended' => ['aws-badge-red',    'Suspendue'],
                ];
                $sc = $statusConf[$company->status] ?? ['aws-badge-yellow', $company->status];
            @endphp
            <span class="aws-badge {{ $sc[0] }}">{{ $sc[1] }}</span>
        </div>
        <div class="aws-panel-body">
            <div class="aws-detail-grid">
                @php
                    $fields = [
                        ['Type',         $company->type_libelle],
                        ['Téléphone',    $company->phone ?? '—'],
                        ['Ville',        $company->city  ?? '—'],
                        ['Pays',         $company->country ?? '—'],
                        ['Adresse',      $company->address ?? '—'],
                        ['Contact',      $company->contact_name ?? '—'],
                        ['Commission',   $company->commission_rate . '%'],
                        ['Inscrit le',   $company->created_at->format('d/m/Y')],
                    ];
                @endphp
                @foreach($fields as [$label, $value])
                <div class="aws-detail-item">
                    <div class="aws-detail-label">{{ $label }}</div>
                    <div class="aws-detail-value">{{ $value }}</div>
                </div>
                @endforeach
            </div>
            @if($company->description)
            <div style="margin-top:16px;padding-top:14px;border-top:1px solid #f2f3f3">
                <div class="aws-detail-label" style="margin-bottom:6px">Description</div>
                <p style="font-size:14px;color:var(--aws-header);line-height:1.6;margin:0">{{ $company->description }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Chauffeurs panel -->
    <div class="aws-panel">
        <div class="aws-panel-header">
            <p class="aws-panel-title">Chauffeurs assignés <span style="font-weight:400;color:var(--aws-sub)">({{ $company->drivers->count() }})</span></p>
        </div>

        @if($company->drivers->count())
        <div style="overflow-x:auto">
            <table class="aws-table">
                <thead>
                    <tr>
                        <th>Chauffeur</th>
                        <th>Téléphone</th>
                        <th>Véhicule</th>
                        <th>Plaque</th>
                        <th>Statut KYC</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($company->drivers as $driver)
                    @php
                        $kycConf = [
                            'approved'  => ['aws-badge-green',  'Approuvé'],
                            'pending'   => ['aws-badge-yellow', 'En attente'],
                            'rejected'  => ['aws-badge-red',    'Rejeté'],
                            'suspended' => ['aws-badge-red',    'Suspendu'],
                        ];
                        $kc = $kycConf[$driver->status] ?? ['aws-badge-yellow', $driver->status];
                    @endphp
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                @if($driver->profile_photo)
                                    <img src="{{ $driver->profile_photo }}" style="width:30px;height:30px;border-radius:50%;object-fit:cover;border:1px solid var(--aws-border)">
                                @else
                                    <div style="width:30px;height:30px;border-radius:50%;background:#0073bb;color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center">
                                        {{ strtoupper(substr($driver->first_name,0,1)) }}
                                    </div>
                                @endif
                                <span style="font-weight:600">{{ $driver->first_name }} {{ $driver->last_name }}</span>
                            </div>
                        </td>
                        <td style="color:var(--aws-sub)">{{ $driver->phone }}</td>
                        <td>{{ $driver->vehicle_brand ?? '—' }} {{ $driver->vehicle_model ?? '' }}</td>
                        <td>
                            @if($driver->vehicle_plate)
                                <code style="background:#f2f3f3;padding:2px 8px;border-radius:3px;font-size:12px;border:1px solid var(--aws-border)">{{ $driver->vehicle_plate }}</code>
                            @else —
                            @endif
                        </td>
                        <td><span class="aws-badge {{ $kc[0] }}">{{ $kc[1] }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('admin.companies.remove-driver', [$company, $driver]) }}">
                                @csrf @method('DELETE')
                                <button type="submit" onclick="return confirm('Retirer ce chauffeur ?')"
                                        style="font-size:12px;padding:4px 10px;border-radius:3px;background:#fff;border:1px solid var(--aws-red);color:var(--aws-red);cursor:pointer;font-weight:600">
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
        <div class="aws-panel-body" style="color:var(--aws-sub);font-size:14px">
            Aucun chauffeur assigné à cette société.
        </div>
        @endif

        <!-- Assigner -->
        @if($availableDrivers->count())
        <div style="padding:16px 20px;border-top:1px solid var(--aws-border);background:#fafafa;border-radius:0 0 4px 4px">
            <p style="font-size:13px;font-weight:700;color:var(--aws-header);margin:0 0 10px">Assigner un chauffeur disponible</p>
            <form method="POST" action="{{ route('admin.companies.assign-driver', $company) }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                @csrf
                <select name="driver_id" required style="border:1px solid var(--aws-border);border-radius:4px;padding:7px 12px;font-size:13px;flex:1;min-width:240px;color:var(--aws-header);outline:none">
                    <option value="">— Sélectionner un chauffeur approuvé —</option>
                    @foreach($availableDrivers as $d)
                        <option value="{{ $d->id }}">{{ $d->first_name }} {{ $d->last_name }} ({{ $d->phone }})</option>
                    @endforeach
                </select>
                <button type="submit" class="aws-btn aws-btn-primary">Assigner</button>
            </form>
        </div>
        @endif
    </div>

</div>
@endsection
