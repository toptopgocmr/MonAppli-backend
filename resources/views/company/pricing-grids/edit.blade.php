@extends('company.layouts.app')
@section('title', 'Modifier la grille')

@section('content')

<div class="aws-crumb">
    <a href="{{ route('company.dashboard') }}">Dashboard</a> ›
    <a href="{{ route('company.pricing-grids.index') }}">Grilles tarifaires</a> ›
    Modifier
</div>
<div class="aws-page-title" style="margin-bottom:16px">Modifier la grille</div>

<div style="max-width:640px">

    @if($errors->any())
    <div class="aws-alert aws-alert-error">
        <div>@foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach</div>
    </div>
    @endif

    <form method="POST" action="{{ route('company.pricing-grids.update', $grid->id) }}">
    @csrf @method('PUT')
    <div class="aws-panel">
        <div class="aws-panel-header"><span class="aws-panel-title">Informations</span></div>
        <div class="aws-panel-body">
            <div class="aws-field">
                <label class="aws-label">Nom de la grille</label>
                <input type="text" name="name" value="{{ old('name', $grid->name) }}" required class="aws-input">
            </div>
            <div class="aws-field">
                <label class="aws-label">Description</label>
                <textarea name="description" rows="2" class="aws-input">{{ old('description', $grid->description) }}</textarea>
            </div>
            <div class="aws-field">
                <label style="font-size:13px"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $grid->is_active) ? 'checked' : '' }}> Grille active (proposable sur les itinéraires)</label>
            </div>
        </div>
    </div>

    <div style="display:flex;align-items:center;gap:14px;padding:4px 0 20px">
        <button type="submit" class="aws-btn aws-btn-primary">Enregistrer</button>
        <a href="{{ route('company.pricing-grids.show', $grid->id) }}" style="font-size:13px;color:var(--aws-blue);text-decoration:none">Annuler</a>
    </div>
    </form>
</div>
@endsection
