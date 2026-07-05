@extends('company.layouts.app')
@section('title', 'Grilles tarifaires')

@section('content')

<div class="aws-crumb"><a href="{{ route('company.dashboard') }}">Dashboard</a> › Grilles tarifaires</div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <div class="aws-page-title">Grilles tarifaires</div>
    <a href="{{ route('company.pricing-grids.create') }}" class="aws-btn aws-btn-primary">+ Nouvelle grille</a>
</div>

@if(session('success'))
<div style="background:#f0f9ec;border:1px solid #b7e0a0;color:#1d8102;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:16px">{{ session('success') }}</div>
@endif

<div class="aws-alert" style="background:#eef7ff;border:1px solid #b8daf5;color:#0073bb;margin-bottom:16px">
    Créez une ou plusieurs grilles (ex: Standard, Premium, Nuit...), remplissez-y vos tarifs manuellement, puis attribuez la grille de votre choix à chaque itinéraire.
</div>

<div class="aws-panel">
    <div style="overflow-x:auto">
        <table class="aws-table">
            <thead>
                <tr><th>Nom</th><th>Description</th><th>Tarifs</th><th>Statut</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($grids as $grid)
                <tr>
                    <td style="font-weight:600">{{ $grid->name }}</td>
                    <td style="color:var(--aws-sub)">{{ $grid->description ?? '—' }}</td>
                    <td><span class="aws-badge aws-badge-blue">{{ $grid->rates_count }} tarif{{ $grid->rates_count > 1 ? 's' : '' }}</span></td>
                    <td>
                        @if($grid->is_active)
                            <span class="aws-badge aws-badge-green">Active</span>
                        @else
                            <span class="aws-badge aws-badge-gray">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="{{ route('company.pricing-grids.show', $grid->id) }}" class="aws-btn aws-btn-normal" style="padding:4px 10px;font-size:12px">Gérer les tarifs</a>
                            <a href="{{ route('company.pricing-grids.edit', $grid->id) }}" class="aws-btn aws-btn-normal" style="padding:4px 10px;font-size:12px">Modifier</a>
                            <form method="POST" action="{{ route('company.pricing-grids.destroy', $grid->id) }}" onsubmit="return confirm('Supprimer cette grille ? Les itinéraires liés resteront mais perdront la référence à cette grille.')" style="display:inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="aws-btn aws-btn-danger" style="padding:4px 10px;font-size:12px">Supprimer</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:40px;color:var(--aws-sub)">
                        Aucune grille tarifaire — <a href="{{ route('company.pricing-grids.create') }}" style="color:var(--aws-blue)">Créer votre première grille</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
