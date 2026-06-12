@extends('admin.layouts.app')
@section('title','Modifier — ' . $company->name)

@section('content')

<style>
:root {
    --aws-border:  #d5dbdb;
    --aws-header:  #16191f;
    --aws-orange:  #ec7211;
    --aws-orange2: #dd6b10;
    --aws-blue:    #0073bb;
    --aws-sub:     #687078;
    --aws-red:     #d13212;
}
.aws-crumb   { font-size:12px; color:var(--aws-sub); margin-bottom:4px; }
.aws-crumb a { color:var(--aws-blue); text-decoration:none; }
.aws-crumb a:hover { text-decoration:underline; }
.aws-h1      { font-size:20px; font-weight:700; color:var(--aws-header); margin:0 0 4px; }
.aws-sub-txt { font-size:13px; color:var(--aws-sub); margin:0 0 20px; }

.aws-panel        { background:#fff; border:1px solid var(--aws-border); border-radius:4px; margin-bottom:16px; }
.aws-panel-header { background:#fafafa; border-bottom:1px solid var(--aws-border); padding:12px 20px; border-radius:4px 4px 0 0; }
.aws-panel-title  { font-size:14px; font-weight:700; color:var(--aws-header); margin:0; }
.aws-panel-desc   { font-size:12px; color:var(--aws-sub); margin:3px 0 0; }
.aws-panel-body   { padding:20px; }

.aws-field       { margin-bottom:18px; }
.aws-label       { display:block; font-size:13px; font-weight:600; color:var(--aws-header); margin-bottom:5px; }
.aws-label-opt   { font-size:12px; font-weight:400; color:var(--aws-sub); margin-left:4px; }
.aws-input       { width:100%; border:1px solid var(--aws-border); border-radius:4px; padding:8px 12px; font-size:14px; color:var(--aws-header); box-sizing:border-box; outline:none; background:#fff; transition:border-color .15s, box-shadow .15s; }
.aws-input:focus { border-color:var(--aws-orange); box-shadow:0 0 0 2px rgba(236,114,17,.15); }
.aws-hint        { font-size:12px; color:var(--aws-sub); margin:4px 0 0; }

.aws-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
.aws-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:18px; }

.aws-btn-primary { display:inline-flex; align-items:center; background:var(--aws-orange); border:1px solid var(--aws-orange2); color:#fff; font-size:13px; font-weight:700; padding:8px 18px; border-radius:4px; cursor:pointer; }
.aws-btn-primary:hover { background:var(--aws-orange2); }
.aws-btn-cancel  { font-size:13px; color:var(--aws-blue); text-decoration:none; padding:8px 12px; }

.aws-error { background:#fdf3f1; border:1px solid #f5b6a7; color:var(--aws-red); padding:12px 16px; border-radius:4px; font-size:13px; margin-bottom:16px; }
.aws-error ul { margin:0; padding-left:16px; }

@media (max-width:640px) { .aws-grid-2, .aws-grid-3 { grid-template-columns:1fr; } }
</style>

<div style="padding:20px 24px;max-width:860px">

    <div class="aws-crumb">
        <a href="{{ route('admin.companies.index') }}">Sociétés</a> ›
        <a href="{{ route('admin.companies.show', $company) }}">{{ $company->name }}</a> ›
        Modifier
    </div>

    <h1 class="aws-h1">Modifier : {{ $company->name }}</h1>
    <p class="aws-sub-txt">Mise à jour des informations du compte société.</p>

    @if($errors->any())
    <div class="aws-error">
        <strong>Erreurs détectées :</strong>
        <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.companies.update', $company) }}">
    @csrf
    @method('PUT')

    <!-- Identité -->
    <div class="aws-panel">
        <div class="aws-panel-header">
            <p class="aws-panel-title">Identité</p>
            <p class="aws-panel-desc">Informations de base et accès au compte</p>
        </div>
        <div class="aws-panel-body">
            <div class="aws-grid-2">
                <div class="aws-field">
                    <label class="aws-label">Nom de la société</label>
                    <input type="text" name="name" value="{{ old('name', $company->name) }}" required class="aws-input">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Adresse email</label>
                    <input type="email" name="email" value="{{ old('email', $company->email) }}" required class="aws-input">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Nouveau mot de passe <span class="aws-label-opt">- vide = inchangé</span></label>
                    <input type="password" name="password" class="aws-input">
                    <p class="aws-hint">Laissez vide pour conserver le mot de passe actuel.</p>
                </div>
                <div class="aws-field">
                    <label class="aws-label">Confirmer le nouveau mot de passe</label>
                    <input type="password" name="password_confirmation" class="aws-input">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Téléphone <span class="aws-label-opt">- facultatif</span></label>
                    <input type="text" name="phone" value="{{ old('phone', $company->phone) }}" class="aws-input">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Personne de contact <span class="aws-label-opt">- facultatif</span></label>
                    <input type="text" name="contact_name" value="{{ old('contact_name', $company->contact_name) }}" class="aws-input">
                </div>
            </div>
        </div>
    </div>

    <!-- Paramètres -->
    <div class="aws-panel">
        <div class="aws-panel-header">
            <p class="aws-panel-title">Paramètres</p>
        </div>
        <div class="aws-panel-body">
            <div class="aws-grid-3">
                <div class="aws-field">
                    <label class="aws-label">Type d'activité</label>
                    <select name="type" required class="aws-input">
                        @foreach(['location'=>'Location','covoiturage'=>'Covoiturage','both'=>'Les deux'] as $v => $l)
                            <option value="{{ $v }}" {{ old('type', $company->type) === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="aws-field">
                    <label class="aws-label">Statut</label>
                    <select name="status" required class="aws-input">
                        @foreach(['active'=>'Active','pending'=>'En attente','suspended'=>'Suspendue'] as $v => $l)
                            <option value="{{ $v }}" {{ old('status', $company->status) === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="aws-field">
                    <label class="aws-label">Commission (%)</label>
                    <input type="number" name="commission_rate" value="{{ old('commission_rate', $company->commission_rate) }}" min="0" max="100" step="0.01" class="aws-input">
                </div>
            </div>
        </div>
    </div>

    <!-- Localisation -->
    <div class="aws-panel">
        <div class="aws-panel-header">
            <p class="aws-panel-title">Localisation <span style="font-size:12px;font-weight:400;color:var(--aws-sub)">— facultatif</span></p>
        </div>
        <div class="aws-panel-body">
            <div class="aws-grid-2">
                <div class="aws-field">
                    <label class="aws-label">Ville</label>
                    <input type="text" name="city" value="{{ old('city', $company->city) }}" class="aws-input">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Pays</label>
                    <input type="text" name="country" value="{{ old('country', $company->country) }}" class="aws-input">
                </div>
                <div class="aws-field" style="grid-column:span 2">
                    <label class="aws-label">Adresse</label>
                    <input type="text" name="address" value="{{ old('address', $company->address) }}" class="aws-input">
                </div>
            </div>
        </div>
    </div>

    <!-- Description -->
    <div class="aws-panel">
        <div class="aws-panel-header">
            <p class="aws-panel-title">Description <span style="font-size:12px;font-weight:400;color:var(--aws-sub)">— facultatif</span></p>
        </div>
        <div class="aws-panel-body">
            <textarea name="description" rows="3" class="aws-input" style="resize:vertical;margin-bottom:0">{{ old('description', $company->description) }}</textarea>
        </div>
    </div>

    <!-- Actions -->
    <div style="display:flex;align-items:center;gap:16px;padding:4px 0 24px">
        <button type="submit" class="aws-btn-primary">Enregistrer les modifications</button>
        <a href="{{ route('admin.companies.show', $company) }}" class="aws-btn-cancel">Annuler</a>
    </div>

    </form>
</div>
@endsection
