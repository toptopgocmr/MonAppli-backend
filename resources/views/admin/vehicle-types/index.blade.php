@extends('admin.layouts.app')
@section('title', 'Types de véhicules')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <div>
        <div class="stat-label" style="font-size:12px;color:var(--aws-sub)">Configuration › Types de véhicules</div>
        <h1 style="font-size:20px;font-weight:700;margin:4px 0 0">Types de véhicules</h1>
    </div>
</div>

@if(session('success'))
<div style="background:#f0f9ec;border:1px solid #b7e0a0;color:#1d8102;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:16px">{{ session('success') }}</div>
@endif
@if(session('error'))
<div style="background:#fdf3f1;border:1px solid #f5b6a7;color:#d13212;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:16px">{{ session('error') }}</div>
@endif
@if($errors->any())
<div style="background:#fdf3f1;border:1px solid #f5b6a7;color:#d13212;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:16px">
    @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
</div>
@endif

<p style="font-size:13px;color:#6b7280;margin-bottom:16px;max-width:720px">
    Cette liste alimente tous les menus « Type de véhicule » de l'application (flotte des sociétés, chauffeurs indépendants,
    grilles tarifaires, itinéraires). Les sociétés et les chauffeurs peuvent aussi ajouter un nouveau type directement depuis
    leur formulaire — il apparaît alors ici automatiquement.
</p>

<div style="display:grid;grid-template-columns:1fr 320px;gap:16px">

    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:6px">
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:13px">
                <thead>
                    <tr style="background:#f9fafb;text-align:left">
                        <th style="padding:10px 16px">Nom</th>
                        <th style="padding:10px 16px">Ajouté par</th>
                        <th style="padding:10px 16px">Statut</th>
                        <th style="padding:10px 16px">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $byMap = ['system'=>'Liste de base','admin'=>'Admin','company'=>'Société','driver'=>'Chauffeur'];
                    @endphp
                    @forelse($vehicleTypes as $t)
                    <tr style="border-top:1px solid #f0f0f0">
                        <td style="padding:10px 16px;font-weight:600">{{ $t->name }}</td>
                        <td style="padding:10px 16px;color:#6b7280">{{ $byMap[$t->added_by_type] ?? ($t->added_by_type ?? '—') }}</td>
                        <td style="padding:10px 16px">
                            @if($t->is_active)
                                <span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:10px;font-size:12px">Actif</span>
                            @else
                                <span style="background:#f3f4f6;color:#374151;padding:2px 8px;border-radius:10px;font-size:12px">Désactivé</span>
                            @endif
                        </td>
                        <td style="padding:10px 16px">
                            <div style="display:flex;gap:6px">
                                <form method="POST" action="{{ route('admin.vehicle-types.toggle', $t->id) }}">
                                    @csrf
                                    <button type="submit" style="background:#f3f4f6;color:#374151;border:1px solid #d1d5db;border-radius:4px;padding:5px 10px;font-size:12px;cursor:pointer">
                                        {{ $t->is_active ? 'Désactiver' : 'Réactiver' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.vehicle-types.destroy', $t->id) }}" onsubmit="return confirm('Supprimer définitivement ce type ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="background:#fee2e2;color:#991b1b;border:none;border-radius:4px;padding:5px 10px;font-size:12px;cursor:pointer">Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" style="text-align:center;padding:30px;color:#9ca3af">Aucun type de véhicule.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:16px;align-self:start">
        <div style="font-weight:700;font-size:14px;margin-bottom:12px">Ajouter un type</div>
        <form method="POST" action="{{ route('admin.vehicle-types.store') }}">
            @csrf
            <div style="margin-bottom:10px">
                <input type="text" name="name" required maxlength="50" placeholder="Ex: Berline VIP"
                    style="width:100%;border:1px solid #d1d5db;border-radius:4px;padding:8px 10px;font-size:13px;box-sizing:border-box">
            </div>
            <button type="submit" style="width:100%;background:#0073bb;color:#fff;border:none;border-radius:4px;padding:8px;font-size:13px;cursor:pointer">Ajouter</button>
        </form>
    </div>

</div>
@endsection
