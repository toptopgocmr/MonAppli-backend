@extends('admin.layouts.app')
@section('title','Nouvelle Société')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;max-width:780px">

    {{-- Header --}}
    <div>
        <a href="{{ route('admin.companies.index') }}"
           style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#64748b;text-decoration:none;margin-bottom:10px">
            ← Retour aux sociétés
        </a>
        <h1 class="page-title">Créer une nouvelle société</h1>
        <p class="page-sub">Remplissez les informations du compte société</p>
    </div>

    @if($errors->any())
    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#dc2626;padding:12px 16px;border-radius:12px;font-size:13px">
        <ul style="margin:0;padding-left:16px">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.companies.store') }}">
    @csrf

    {{-- Identité --}}
    <div class="card" style="margin-bottom:16px">
        <div style="font-size:13px;font-weight:700;color:#1e293b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:18px;padding-bottom:10px;border-bottom:1px solid #f1f5f9">
            Identité de la société
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

            <div>
                <label class="lbl">Nom de la société *</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="form-input" placeholder="TopTransport SARL">
            </div>

            <div>
                <label class="lbl">Email *</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="form-input" placeholder="contact@societe.com">
            </div>

            <div>
                <label class="lbl">Mot de passe *</label>
                <input type="password" name="password" required class="form-input">
            </div>

            <div>
                <label class="lbl">Confirmer le mot de passe *</label>
                <input type="password" name="password_confirmation" required class="form-input">
            </div>

            <div>
                <label class="lbl">Téléphone</label>
                <input type="text" name="phone" value="{{ old('phone') }}"
                       class="form-input" placeholder="+237 6XX XXX XXX">
            </div>

            <div>
                <label class="lbl">Personne de contact</label>
                <input type="text" name="contact_name" value="{{ old('contact_name') }}"
                       class="form-input" placeholder="M. Dupont">
            </div>

        </div>
    </div>

    {{-- Activité & Paramètres --}}
    <div class="card" style="margin-bottom:16px">
        <div style="font-size:13px;font-weight:700;color:#1e293b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:18px;padding-bottom:10px;border-bottom:1px solid #f1f5f9">
            Activité & Paramètres
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">

            <div>
                <label class="lbl">Type d'activité *</label>
                <select name="type" required class="form-input">
                    <option value="">— Choisir —</option>
                    @foreach(['location'=>'Location de véhicules','covoiturage'=>'Covoiturage privé','both'=>'Les deux'] as $v => $l)
                        <option value="{{ $v }}" {{ old('type') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="lbl">Statut *</label>
                <select name="status" required class="form-input">
                    @foreach(['active'=>'Active','pending'=>'En attente','suspended'=>'Suspendue'] as $v => $l)
                        <option value="{{ $v }}" {{ old('status', 'pending') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="lbl">Commission (%)</label>
                <input type="number" name="commission_rate" value="{{ old('commission_rate', '10') }}"
                       min="0" max="100" step="0.01" class="form-input">
            </div>

        </div>
    </div>

    {{-- Localisation --}}
    <div class="card" style="margin-bottom:16px">
        <div style="font-size:13px;font-weight:700;color:#1e293b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:18px;padding-bottom:10px;border-bottom:1px solid #f1f5f9">
            Localisation
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

            <div>
                <label class="lbl">Ville</label>
                <input type="text" name="city" value="{{ old('city') }}"
                       class="form-input" placeholder="Yaoundé">
            </div>

            <div>
                <label class="lbl">Pays</label>
                <input type="text" name="country" value="{{ old('country', 'Cameroun') }}"
                       class="form-input">
            </div>

            <div style="grid-column:span 2">
                <label class="lbl">Adresse</label>
                <input type="text" name="address" value="{{ old('address') }}"
                       class="form-input" placeholder="Rue, quartier...">
            </div>

        </div>
    </div>

    {{-- Description --}}
    <div class="card" style="margin-bottom:20px">
        <div style="font-size:13px;font-weight:700;color:#1e293b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:18px;padding-bottom:10px;border-bottom:1px solid #f1f5f9">
            Description
        </div>
        <textarea name="description" rows="3" class="form-input"
                  placeholder="Présentation rapide de la société...">{{ old('description') }}</textarea>
    </div>

    {{-- Actions --}}
    <div style="display:flex;align-items:center;gap:14px">
        <button type="submit" class="btn btn-primary">Créer la société</button>
        <a href="{{ route('admin.companies.index') }}"
           style="font-size:14px;color:#64748b;text-decoration:none">Annuler</a>
    </div>

    </form>

</div>

<style>
.form-input {
    width: 100%;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 14px;
    box-sizing: border-box;
    outline: none;
    background: #f8fafc;
    color: #1e293b;
    transition: border-color .15s, box-shadow .15s;
}
.form-input:focus {
    border-color: #1DA1F2;
    box-shadow: 0 0 0 3px rgba(29,161,242,.1);
    background: #fff;
}
.lbl {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: .03em;
}
</style>
@endsection
