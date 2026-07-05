@extends('company.layouts.app')
@section('title', 'Nouvel Agent')

@section('content')

<div class="aws-crumb">
    <a href="{{ route('company.dashboard') }}">Dashboard</a> ›
    <a href="{{ route('company.agents.index') }}">Agents</a> ›
    Nouveau
</div>
<div class="aws-page-title" style="margin-bottom:16px">Ajouter un agent</div>

<div style="max-width:640px">

    @if($errors->any())
    <div class="aws-alert aws-alert-error">
        <div>@foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach</div>
    </div>
    @endif

    <form method="POST" action="{{ route('company.agents.store') }}">
    @csrf

    <div class="aws-panel">
        <div class="aws-panel-header"><span class="aws-panel-title">Informations</span></div>
        <div class="aws-panel-body">
            <div class="aws-grid-2">
                <div class="aws-field">
                    <label class="aws-label">Nom complet *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="aws-input" placeholder="Ex: Jean Mballa">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Rôle *</label>
                    <select name="role" id="role_select" required class="aws-input">
                        <option value="">— Choisir —</option>
                        @foreach(\App\Models\CompanyAgent::ROLES as $key => $label)
                            <option value="{{ $key }}" {{ old('role') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="aws-field">
                    <label class="aws-label">Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="aws-input" placeholder="agent@societe.com">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Téléphone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="aws-input">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Mot de passe *</label>
                    <input type="password" name="password" required class="aws-input" minlength="8">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Confirmer le mot de passe *</label>
                    <input type="password" name="password_confirmation" required class="aws-input" minlength="8">
                </div>
            </div>
        </div>
    </div>

    <div class="aws-panel">
        <div class="aws-panel-header"><span class="aws-panel-title">Accès autorisés</span></div>
        <div class="aws-panel-body">
            <p class="aws-hint" id="dg-hint" style="display:none;margin-top:0">
                Le Directeur Général a automatiquement accès à toutes les sections.
            </p>
            <div id="permissions-box" style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                @foreach(\App\Models\CompanyAgent::PERMISSIONS as $key => $label)
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                    <input type="checkbox" name="permissions[]" value="{{ $key }}" class="perm-checkbox"
                        {{ in_array($key, old('permissions', [])) ? 'checked' : '' }}>
                    {{ $label }}
                </label>
                @endforeach
            </div>
        </div>
    </div>

    <div style="display:flex;align-items:center;gap:14px;padding:4px 0 20px">
        <button type="submit" class="aws-btn aws-btn-primary">Ajouter l'agent</button>
        <a href="{{ route('company.agents.index') }}" style="font-size:13px;color:var(--aws-blue);text-decoration:none">Annuler</a>
    </div>

    </form>
</div>

@push('scripts')
<script>
(function () {
    const roleSelect = document.getElementById('role_select');
    const permBox     = document.getElementById('permissions-box');
    const dgHint      = document.getElementById('dg-hint');
    const checkboxes  = document.querySelectorAll('.perm-checkbox');

    function applyDgState() {
        const isDg = roleSelect.value === 'directeur_general';
        dgHint.style.display = isDg ? '' : 'none';
        permBox.style.opacity = isDg ? '0.5' : '1';
        checkboxes.forEach(cb => {
            cb.disabled = isDg;
            if (isDg) cb.checked = true;
        });
    }

    roleSelect.addEventListener('change', applyDgState);
    applyDgState();
})();
</script>
@endpush
@endsection
