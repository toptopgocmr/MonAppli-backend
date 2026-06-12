@extends('admin.layouts.app')
@section('title','Nouvelle Société')

@section('content')

<style>
:root {
    --aws-bg:      #f2f3f3;
    --aws-border:  #d5dbdb;
    --aws-panel:   #ffffff;
    --aws-header:  #16191f;
    --aws-orange:  #ec7211;
    --aws-orange2: #dd6b10;
    --aws-blue:    #0073bb;
    --aws-text:    #16191f;
    --aws-sub:     #687078;
    --aws-red:     #d13212;
}

.aws-crumb   { font-size:12px; color:var(--aws-sub); margin-bottom:4px; }
.aws-crumb a { color:var(--aws-blue); text-decoration:none; }
.aws-crumb a:hover { text-decoration:underline; }
.aws-h1      { font-size:20px; font-weight:700; color:var(--aws-header); margin:0 0 4px; }
.aws-sub-txt { font-size:13px; color:var(--aws-sub); margin:0 0 20px; }

.aws-panel        { background:var(--aws-panel); border:1px solid var(--aws-border); border-radius:4px; margin-bottom:16px; }
.aws-panel-header { background:#fafafa; border-bottom:1px solid var(--aws-border); padding:12px 20px; border-radius:4px 4px 0 0; }
.aws-panel-title  { font-size:14px; font-weight:700; color:var(--aws-header); margin:0; }
.aws-panel-desc   { font-size:12px; color:var(--aws-sub); margin:3px 0 0; }
.aws-panel-body   { padding:20px; }

.aws-field        { margin-bottom:18px; }
.aws-label        { display:block; font-size:13px; font-weight:600; color:var(--aws-header); margin-bottom:5px; }
.aws-label-opt    { font-size:12px; font-weight:400; color:var(--aws-sub); margin-left:4px; }
.aws-input        { width:100%; border:1px solid var(--aws-border); border-radius:4px; padding:8px 12px; font-size:14px; color:var(--aws-header); box-sizing:border-box; outline:none; background:#fff; transition:border-color .15s, box-shadow .15s; }
.aws-input:focus  { border-color:var(--aws-orange); box-shadow:0 0 0 2px rgba(236,114,17,.15); }
.aws-input::placeholder { color:#aab7b8; }
.aws-hint         { font-size:12px; color:var(--aws-sub); margin:4px 0 0; }

.aws-grid-2  { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
.aws-grid-3  { display:grid; grid-template-columns:1fr 1fr 1fr; gap:18px; }

.aws-btn-primary { display:inline-flex; align-items:center; gap:6px; background:var(--aws-orange); border:1px solid var(--aws-orange2); color:#fff; font-size:13px; font-weight:700; padding:8px 18px; border-radius:4px; cursor:pointer; text-decoration:none; }
.aws-btn-primary:hover { background:var(--aws-orange2); }
.aws-btn-cancel  { font-size:13px; color:var(--aws-blue); text-decoration:none; padding:8px 12px; }
.aws-btn-cancel:hover { text-decoration:underline; }

.aws-divider { border:none; border-top:1px solid var(--aws-border); margin:0; }

.aws-error { background:#fdf3f1; border:1px solid #f5b6a7; color:var(--aws-red); padding:12px 16px; border-radius:4px; font-size:13px; margin-bottom:16px; }
.aws-error ul { margin:0; padding-left:16px; }

@media (max-width:640px) { .aws-grid-2, .aws-grid-3 { grid-template-columns:1fr; } }
</style>

<div style="padding:20px 24px;max-width:860px">

    <div class="aws-crumb">
        <a href="{{ route('admin.companies.index') }}">Sociétés</a> › Nouvelle société
    </div>

    <h1 class="aws-h1">Créer une société</h1>
    <p class="aws-sub-txt">Remplissez les informations du nouveau compte société partenaire.</p>

    @if($errors->any())
    <div class="aws-error">
        <strong>Les erreurs suivantes ont été détectées :</strong>
        <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.companies.store') }}">
    @csrf

    <!-- Section 1 : Identité -->
    <div class="aws-panel">
        <div class="aws-panel-header">
            <p class="aws-panel-title">Identité de la société</p>
            <p class="aws-panel-desc">Informations de connexion et de contact principal</p>
        </div>
        <div class="aws-panel-body">
            <div class="aws-grid-2">
                <div class="aws-field">
                    <label class="aws-label">Nom de la société</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="aws-input" placeholder="TopTransport SARL">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Adresse email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="aws-input" placeholder="contact@societe.com">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Mot de passe</label>
                    <input type="password" name="password" required class="aws-input">
                    <p class="aws-hint">Minimum 8 caractères.</p>
                </div>
                <div class="aws-field">
                    <label class="aws-label">Confirmer le mot de passe</label>
                    <input type="password" name="password_confirmation" required class="aws-input">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Téléphone <span class="aws-label-opt">- facultatif</span></label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="aws-input" placeholder="+237 6XX XXX XXX">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Personne de contact <span class="aws-label-opt">- facultatif</span></label>
                    <input type="text" name="contact_name" value="{{ old('contact_name') }}" class="aws-input" placeholder="M. Dupont">
                </div>
            </div>
        </div>
    </div>

    <!-- Section 2 : Paramètres -->
    <div class="aws-panel">
        <div class="aws-panel-header">
            <p class="aws-panel-title">Paramètres du compte</p>
            <p class="aws-panel-desc">Type d'activité, statut initial et taux de commission</p>
        </div>
        <div class="aws-panel-body">
            <div class="aws-grid-3">
                <div class="aws-field">
                    <label class="aws-label">Type d'activité</label>
                    <select name="type" required class="aws-input">
                        <option value="">— Choisir —</option>
                        @foreach(['location'=>'Location de véhicules','covoiturage'=>'Covoiturage privé','both'=>'Les deux'] as $v => $l)
                            <option value="{{ $v }}" {{ old('type') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="aws-field">
                    <label class="aws-label">Statut initial</label>
                    <select name="status" required class="aws-input">
                        @foreach(['active'=>'Active','pending'=>'En attente','suspended'=>'Suspendue'] as $v => $l)
                            <option value="{{ $v }}" {{ old('status', 'pending') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                    <p class="aws-hint">L'accès société sera actif si vous choisissez "Active".</p>
                </div>
                <div class="aws-field">
                    <label class="aws-label">Commission (%)</label>
                    <input type="number" name="commission_rate" value="{{ old('commission_rate', '10') }}" min="0" max="100" step="0.01" class="aws-input">
                    <p class="aws-hint">Taux prélevé sur chaque course.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3 : Localisation -->
    <div class="aws-panel">
        <div class="aws-panel-header">
            <p class="aws-panel-title">Localisation <span style="font-size:12px;font-weight:400;color:var(--aws-sub)">— facultatif</span></p>
        </div>
        <div class="aws-panel-body">
            <div class="aws-grid-2">
                <div class="aws-field">
                    <label class="aws-label">Ville</label>
                    <input type="text" name="city" value="{{ old('city') }}" class="aws-input" placeholder="Yaoundé">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Pays</label>
                    <input type="text" name="country" value="{{ old('country', 'Cameroun') }}" class="aws-input">
                </div>
                <div class="aws-field" style="grid-column:span 2">
                    <label class="aws-label">Adresse complète</label>
                    <input type="text" name="address" value="{{ old('address') }}" class="aws-input" placeholder="Rue, quartier, ville...">
                </div>
            </div>
        </div>
    </div>

    <!-- Section 4 : Description -->
    <div class="aws-panel">
        <div class="aws-panel-header">
            <p class="aws-panel-title">Description <span style="font-size:12px;font-weight:400;color:var(--aws-sub)">— facultatif</span></p>
        </div>
        <div class="aws-panel-body">
            <div class="aws-field" style="margin-bottom:0">
                <textarea name="description" rows="3" class="aws-input" style="resize:vertical"
                          placeholder="Présentation rapide de la société...">{{ old('description') }}</textarea>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div style="display:flex;align-items:center;gap:16px;padding:4px 0 24px">
        <button type="submit" class="aws-btn-primary">Créer la société</button>
        <a href="{{ route('admin.companies.index') }}" class="aws-btn-cancel">Annuler</a>
    </div>

    </form>
</div>
@endsection
