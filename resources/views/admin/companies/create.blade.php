@extends('admin.layouts.app')
@section('title','Nouvelle Société')

@section('content')
<div style="max-width:720px">

    <div style="margin-bottom:20px">
        <a href="{{ route('admin.companies.index') }}" style="color:#2563eb;font-size:14px;text-decoration:none">← Retour aux sociétés</a>
    </div>

    <div class="card">
        <h2 style="font-size:18px;font-weight:700;color:#1e293b;margin-bottom:24px;padding-bottom:12px;border-bottom:1px solid #f1f5f9">
            Créer une nouvelle société
        </h2>

        @if($errors->any())
        <div style="background:#fee2e2;border:1px solid #fca5a5;color:#dc2626;padding:12px 16px;border-radius:10px;font-size:13px;margin-bottom:20px">
            <ul style="margin:0;padding-left:16px">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.companies.store') }}">
            @csrf

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px">
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px">Nom de la société *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:14px;box-sizing:border-box;outline:none"
                           placeholder="TopTransport SARL">
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px">Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:14px;box-sizing:border-box;outline:none"
                           placeholder="contact@societe.com">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px">
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px">Mot de passe *</label>
                    <input type="password" name="password" required
                           style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:14px;box-sizing:border-box;outline:none">
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px">Confirmer mot de passe *</label>
                    <input type="password" name="password_confirmation" required
                           style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:14px;box-sizing:border-box;outline:none">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px;margin-bottom:18px">
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px">Téléphone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                           style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:14px;box-sizing:border-box;outline:none"
                           placeholder="+237 6XX XXX XXX">
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px">Type d'activité *</label>
                    <select name="type" required style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:14px;box-sizing:border-box;outline:none">
                        <option value="">— Choisir —</option>
                        @foreach(['location'=>'Location de véhicules','covoiturage'=>'Covoiturage privé','both'=>'Les deux'] as $v => $l)
                            <option value="{{ $v }}" {{ old('type') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px">Statut *</label>
                    <select name="status" required style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:14px;box-sizing:border-box;outline:none">
                        @foreach(['active'=>'Active','pending'=>'En attente','suspended'=>'Suspendue'] as $v => $l)
                            <option value="{{ $v }}" {{ old('status', 'pending') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px;margin-bottom:18px">
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px">Ville</label>
                    <input type="text" name="city" value="{{ old('city') }}"
                           style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:14px;box-sizing:border-box;outline:none"
                           placeholder="Yaoundé">
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px">Pays</label>
                    <input type="text" name="country" value="{{ old('country', 'Cameroun') }}"
                           style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:14px;box-sizing:border-box;outline:none">
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px">Commission (%)</label>
                    <input type="number" name="commission_rate" value="{{ old('commission_rate', '10') }}" min="0" max="100" step="0.01"
                           style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:14px;box-sizing:border-box;outline:none">
                </div>
            </div>

            <div style="margin-bottom:18px">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px">Adresse</label>
                <input type="text" name="address" value="{{ old('address') }}"
                       style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:14px;box-sizing:border-box;outline:none"
                       placeholder="Rue, quartier...">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:24px">
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px">Personne de contact</label>
                    <input type="text" name="contact_name" value="{{ old('contact_name') }}"
                           style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:14px;box-sizing:border-box;outline:none"
                           placeholder="M. Dupont">
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px">Description</label>
                    <textarea name="description" rows="2"
                              style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:14px;box-sizing:border-box;outline:none;resize:vertical"
                              placeholder="Présentation rapide...">{{ old('description') }}</textarea>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:14px">
                <button type="submit" class="btn btn-primary">Créer la société</button>
                <a href="{{ route('admin.companies.index') }}" style="font-size:14px;color:#64748b;text-decoration:none">Annuler</a>
            </div>
        </form>
    </div>

</div>
@endsection
