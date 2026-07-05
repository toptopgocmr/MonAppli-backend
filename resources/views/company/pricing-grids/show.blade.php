@extends('company.layouts.app')
@section('title', $grid->name)

@section('content')

<div class="aws-crumb">
    <a href="{{ route('company.dashboard') }}">Dashboard</a> ›
    <a href="{{ route('company.pricing-grids.index') }}">Grilles tarifaires</a> ›
    {{ $grid->name }}
</div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <div class="aws-page-title">{{ $grid->name }}</div>
    <a href="{{ route('company.pricing-grids.edit', $grid->id) }}" class="aws-btn aws-btn-normal">Modifier la grille</a>
</div>

@if(session('success'))
<div style="background:#f0f9ec;border:1px solid #b7e0a0;color:#1d8102;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:16px">{{ session('success') }}</div>
@endif
@if($errors->any())
<div style="background:#fdf3f1;border:1px solid #f5b6a7;color:#d13212;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:16px">
    @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
</div>
@endif

<div style="display:grid;grid-template-columns:1.3fr 1fr;gap:16px">

    <!-- Tarifs -->
    <div class="aws-panel">
        <div class="aws-panel-header"><span class="aws-panel-title">Tarifs de cette grille</span></div>
        <div style="overflow-x:auto">
            <table class="aws-table">
                <thead>
                    <tr><th>Libellé</th><th>Type de véhicule</th><th>Prix (FCFA)</th><th>Action</th></tr>
                </thead>
                <tbody>
                    @forelse($grid->rates as $rate)
                    <tr>
                        <td style="font-weight:600">{{ $rate->label }}</td>
                        <td>{{ $rate->vehicle_type ?? 'Tous types' }}</td>
                        <td>{{ number_format($rate->price, 0, ',', ' ') }}</td>
                        <td>
                            <form method="POST" action="{{ route('company.pricing-grids.rates.destroy', [$grid->id, $rate->id]) }}" onsubmit="return confirm('Retirer ce tarif ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="aws-btn aws-btn-danger" style="padding:4px 10px;font-size:12px">Retirer</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" style="text-align:center;padding:30px;color:var(--aws-sub)">Aucun tarif — ajoutez-en un à droite.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Ajouter un tarif -->
    <div class="aws-panel">
        <div class="aws-panel-header"><span class="aws-panel-title">Ajouter un tarif</span></div>
        <div class="aws-panel-body">
            <form method="POST" action="{{ route('company.pricing-grids.rates.store', $grid->id) }}">
            @csrf
            <div class="aws-field">
                <label class="aws-label">Libellé</label>
                <input type="text" name="label" required class="aws-input" placeholder="Ex: Standard, 0-10km, Nuit...">
                <p class="aws-hint">Libre — décrivez ce que couvre ce tarif (type de véhicule, tranche de distance, horaire...).</p>
            </div>
            <div class="aws-field">
                <label class="aws-label">Type de véhicule <span class="aws-label-opt">— facultatif</span></label>
                <select name="vehicle_type" class="aws-input">
                    <option value="">— Tous types —</option>
                    @foreach(['Berline','SUV','Monospace','Pickup','Minibus','Moto'] as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aws-field">
                <label class="aws-label">Prix (FCFA)</label>
                <input type="number" name="price" required min="0" step="100" class="aws-input" placeholder="5000">
            </div>
            <button type="submit" class="aws-btn aws-btn-primary" style="width:100%">Ajouter le tarif</button>
            </form>
        </div>
    </div>

</div>
@endsection
