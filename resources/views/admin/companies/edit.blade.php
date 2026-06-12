@extends('admin.layouts.app')
@section('title','Modifier Société')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;max-width:780px">

    <div>
        <a href="{{ route('admin.companies.show', $company) }}"
           style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#64748b;text-decoration:none;margin-bottom:10px">
            ← Retour au profil
        </a>
        <h1 class="page-title">Modifier : {{ $company->name }}</h1>
        <p class="page-sub">Mise à jour des informations du compte société</p>
    </div>

    @if($errors->any())
    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#dc2626;padding:12px 16px;border-radius:12px;font-size:13px">
        <ul style="margin:0;padding-left:16px">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.companies.update', $company) }}">
    @csrf
    @method('PUT')

    {{-- Identité --}}
    <div class="card" style="margin-bottom:16px">
        <div style="font-size:13px;font-weight:700;color:#1e293b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:18px;padding-bottom:10px;border-bottom:1px solid #f1f5f9">
            Identité de la société
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

            <div>
                <label class="lbl">Nom de la société *</label>
                <input type="text" name="name" value="{{ old('name', $company->name) }}" required class="form-input">
            </div>

            <div>
                <label class="lbl">Email *</label>
                <input type="email" name="email" value="{{ old('email', $company->email) }}" required class="form-input">
            </div>

            <div>
                <label class="lbl">Nouveau mot de passe <span style="font-weight:400;color:#94a3b8">(vide = inchangé)</span></label>
                <input type="password" name="password" class="form-input">
            </div>

            <div>
                <label class="lbl">Confirmer nouveau mot de passe</label>
                <input type="password" name="password_confirmation" class="form-input">
            </div>

            <div>
                <label class="lbl">Téléphone</label>
                <input type="text" name="phone" value="{{ old('phone', $company->phone) }}" class="form-input">
            </div>

            <div>
                <label class="lbl">Personne de contact</label>
                <input type="text" name="contact_name" value="{{ old('contact_name', $company->contact_name) }}" class="form-input">
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
                    @foreach(['location'=>'Location de véhicules','covoiturage'=>'Covoiturage privé','both'=>'Les deux'] as $v => $l)
                        <option value="{{ $v }}" {{ old('type', $company->type) === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="lbl">Statut *</label>
                <select name="status" required class="form-input">
                    @foreach(['active'=>'Active','pending'=>'En attente','suspended'=>'Suspendue'] as $v => $l)
                        <option value="{{ $v }}" {{ old('status', $company->status) === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="lbl">Commission (%)</label>
                <input type="number" name="commission_rate" value="{{ old('commission_rate', $company->commission_rate) }}"
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
                <input type="text" name="city" value="{{ old('city', $company->city) }}" class="form-input">
            </div>

            <div>
                <label class="lbl">Pays</label>
                <input type="text" name="country" value="{{ old('country', $company->country) }}" class="form-input">
            </div>

            <div style="grid-column:span 2">
                <label class="lbl">Adresse</label>
                <input type="text" name="address" value="{{ old('address', $company->address) }}" class="form-input">
            </div>

        </div>
    </div>

    {{-- Description --}}
    <div class="card" style="margin-bottom:20px">
        <div style="font-size:13px;font-weight:700;color:#1e293b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:18px;padding-bottom:10px;border-bottom:1px solid #f1f5f9">
            Description
        </div>
        <textarea name="description" rows="3" class="form-input">{{ old('description', $company->description) }}</textarea>
    </div>

    <div style="display:flex;align-items:center;gap:14px">
        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
        <a href="{{ route('admin.companies.show', $company) }}"
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
