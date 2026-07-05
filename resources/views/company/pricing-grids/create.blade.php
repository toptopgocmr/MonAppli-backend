@extends('company.layouts.app')
@section('title', 'Nouvelle grille tarifaire')

@section('content')

<div class="aws-crumb">
    <a href="{{ route('company.dashboard') }}">Dashboard</a> ›
    <a href="{{ route('company.pricing-grids.index') }}">Grilles tarifaires</a> ›
    Nouvelle
</div>
<div class="aws-page-title" style="margin-bottom:16px">Créer une grille tarifaire</div>

<div style="max-width:640px">

    @if($errors->any())
    <div class="aws-alert aws-alert-error">
        <div>@foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach</div>
    </div>
    @endif

    <form method="POST" action="{{ route('company.pricing-grids.store') }}">
    @csrf
    <div class="aws-panel">
        <div class="aws-panel-header"><span class="aws-panel-title">Informations</span></div>
        <div class="aws-panel-body">
            <div class="aws-field">
                <label class="aws-label">Nom de la grille</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="aws-input" placeholder="Ex: Standard, Premium, Nuit...">
            </div>
            <div class="aws-field">
                <label class="aws-label">Description <span class="aws-label-opt">— facultatif</span></label>
                <textarea name="description" rows="2" class="aws-input">{{ old('description') }}</textarea>
            </div>
        </div>
    </div>

    <div style="display:flex;align-items:center;gap:14px;padding:4px 0 20px">
        <button type="submit" class="aws-btn aws-btn-primary">Créer la grille</button>
        <a href="{{ route('company.pricing-grids.index') }}" style="font-size:13px;color:var(--aws-blue);text-decoration:none">Annuler</a>
    </div>
    </form>
</div>
@endsection
