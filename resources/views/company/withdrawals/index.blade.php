@extends('company.layouts.app')
@section('title', 'Retraits')

@section('content')

<div class="aws-crumb"><a href="{{ route('company.dashboard') }}">Dashboard</a> › Retraits</div>
<div class="aws-page-title" style="margin-bottom:16px">Retraits</div>

@if(session('success'))
<div style="background:#f0f9ec;border:1px solid #b7e0a0;color:#1d8102;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:16px">{{ session('success') }}</div>
@endif
@if(session('error'))
<div style="background:#fdf3f1;border:1px solid #f5b6a7;color:#d13212;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:16px">{{ session('error') }}</div>
@endif
@if($errors->any())
<div style="background:#fdf3f1;border:1px solid #f5b6a7;color:#d13212;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:16px">
    @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
</div>
@endif

<!-- KPI -->
<div class="aws-stat-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:16px">
    <div class="aws-stat-card" style="border-top:3px solid #687078">
        <div class="aws-stat-label">Chiffre d'affaires brut</div>
        <div class="aws-stat-value" style="font-size:20px">{{ number_format($recap['gross_revenue'], 0, ',', ' ') }}<span style="font-size:12px;font-weight:400;margin-left:4px">FCFA</span></div>
        <div class="aws-stat-sub">Courses terminées, avant commission</div>
    </div>
    <div class="aws-stat-card" style="border-top:3px solid #d13212">
        <div class="aws-stat-label">Commission TopTopGo prélevée</div>
        <div class="aws-stat-value" style="font-size:20px;color:#d13212">− {{ number_format($recap['commission_taken'], 0, ',', ' ') }}<span style="font-size:12px;font-weight:400;margin-left:4px">FCFA</span></div>
        <div class="aws-stat-sub">{{ number_format($company->commission_rate ?? 0, 2) }} % du CA brut</div>
    </div>
    <div class="aws-stat-card" style="border-top:3px solid #0073bb">
        <div class="aws-stat-label">Déjà retiré (payé)</div>
        <div class="aws-stat-value" style="font-size:20px">{{ number_format($recap['withdrawals_paid'], 0, ',', ' ') }}<span style="font-size:12px;font-weight:400;margin-left:4px">FCFA</span></div>
        <div class="aws-stat-sub">Retraits déjà versés par l'administration</div>
    </div>
    <div class="aws-stat-card" style="border-top:3px solid #1d8102">
        <div class="aws-stat-label">Solde réel disponible</div>
        <div class="aws-stat-value" style="font-size:20px;color:#1d8102">{{ number_format($availableBalance, 0, ',', ' ') }}<span style="font-size:12px;font-weight:400;margin-left:4px">FCFA</span></div>
        <div class="aws-stat-sub">Net de commission, après retraits déjà demandés</div>
    </div>
</div>

<div class="aws-hint" style="margin:-8px 0 16px">
    Revenu net total (historique) : <strong>{{ number_format($totalNetRevenue, 0, ',', ' ') }} FCFA</strong>
    = CA brut ({{ number_format($recap['gross_revenue'], 0, ',', ' ') }}) − commission TopTopGo ({{ number_format($recap['commission_taken'], 0, ',', ' ') }}).
    Solde réel = revenu net − retraits déjà demandés (payés + en attente : {{ number_format($recap['withdrawals_committed'], 0, ',', ' ') }} FCFA).
</div>

<div style="display:grid;grid-template-columns:1.3fr 1fr;gap:16px">

    <!-- Historique -->
    <div class="aws-panel">
        <div class="aws-panel-header"><span class="aws-panel-title">Historique des demandes</span></div>
        <div style="overflow-x:auto">
            <table class="aws-table">
                <thead>
                    <tr><th>Montant</th><th>Méthode</th><th>Statut</th><th>Référence</th><th>Demandé le</th><th>Traité le</th></tr>
                </thead>
                <tbody>
                    @forelse($withdrawals as $w)
                    @php
                        $stMap = ['pending'=>['aws-badge-yellow','En attente'],'success'=>['aws-badge-green','Payé'],'failed'=>['aws-badge-red','Rejeté']];
                        $sc = $stMap[$w->status] ?? ['aws-badge-gray', $w->status];
                        $methodLabel = ['mobile_money'=>'Mobile Money','bank'=>'Virement bancaire','manual'=>'Manuel'][$w->method] ?? ($w->method ?? '—');
                    @endphp
                    <tr>
                        <td style="font-weight:600">{{ number_format($w->amount, 0, ',', ' ') }} FCFA</td>
                        <td>{{ $methodLabel }}{{ $w->operator ? ' · '.$w->operator : '' }}{{ $w->country ? ' · '.$w->country : '' }}</td>
                        <td><span class="aws-badge {{ $sc[0] }}">{{ $sc[1] }}</span></td>
                        <td>{{ $w->transaction_ref ?? '—' }}</td>
                        <td>{{ $w->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $w->processed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--aws-sub)">Aucune demande de retrait pour le moment.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($withdrawals->hasPages())
        <div style="padding:12px 20px;border-top:1px solid var(--aws-border)">{{ $withdrawals->links() }}</div>
        @endif
    </div>

    <div style="display:flex;flex-direction:column;gap:16px">

        <!-- Demande de retrait -->
        <div class="aws-panel">
            <div class="aws-panel-header"><span class="aws-panel-title">Demander un retrait</span></div>
            <div class="aws-panel-body">
                <form method="POST" action="{{ route('company.withdrawals.store') }}">
                @csrf
                <div class="aws-field">
                    <label class="aws-label">Montant (FCFA)</label>
                    <input type="number" name="amount" value="{{ old('amount') }}" min="1000" max="{{ $availableBalance }}" step="100" required class="aws-input" placeholder="Ex: 50000">
                    <p class="aws-hint">Solde disponible : {{ number_format($availableBalance, 0, ',', ' ') }} FCFA</p>
                </div>

                <div class="aws-field">
                    <label class="aws-label">Moyen de paiement</label>
                    <select name="method" id="w-method" required class="aws-input">
                        <option value="">— Choisir —</option>
                        <option value="mobile_money" {{ old('method') === 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                        <option value="bank" {{ old('method') === 'bank' ? 'selected' : '' }} {{ !$hasBankInfo ? 'disabled' : '' }}>Virement bancaire{{ !$hasBankInfo ? ' (coordonnées manquantes)' : '' }}</option>
                    </select>
                    @if(!$hasBankInfo)
                    <p class="aws-hint">Renseignez vos coordonnées bancaires ci-dessous pour activer le virement bancaire.</p>
                    @endif
                </div>

                <div class="aws-field">
                    <label class="aws-label">Pays du retrait</label>
                    <div style="display:flex;gap:8px;align-items:center">
                        <img id="w-country-flag" src="" alt=""
                             style="width:28px;height:20px;border-radius:2px;object-fit:cover;border:1px solid var(--aws-border);display:none;flex-shrink:0">
                        <select name="country" id="w-country" required class="aws-input" style="flex:1">
                            <option value="">— Choisir —</option>
                            @foreach($payoutCountries as $c)
                                <option value="{{ $c['code'] }}"
                                        data-dial="{{ $c['dial'] ?? '' }}"
                                        data-flag-url="{{ $c['flag_url'] }}"
                                        data-operators='@json($c['operators'] ?? [])'
                                        {{ old('country') === $c['code'] ? 'selected' : '' }}>
                                    {{ $c['name'] }}{{ !empty($c['dial']) ? ' ('.$c['dial'].')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if(empty($payoutCountries))
                    <p class="aws-hint">Aucun pays de retrait n'est actuellement disponible.</p>
                    @endif
                </div>

                <div class="aws-field" id="w-phone-field">
                    <label class="aws-label">Opérateur Mobile Money</label>
                    <div id="w-operator-chips" style="display:flex;flex-wrap:wrap;gap:8px">
                        <span style="font-size:12px;color:var(--aws-sub)">— Choisir un pays d'abord —</span>
                    </div>
                    <input type="hidden" name="operator" id="w-operator-hidden" value="{{ old('operator') }}">

                    <label class="aws-label" style="margin-top:10px">Numéro Mobile Money</label>
                    <div style="display:flex;gap:8px;align-items:center">
                        <span id="w-dial-code" class="aws-badge aws-badge-gray" style="display:flex;align-items:center;gap:6px;padding:8px 10px;font-size:14px;white-space:nowrap">
                            <img id="w-dial-flag" src="" alt="" style="width:20px;height:14px;border-radius:2px;object-fit:cover;display:none">
                            <span id="w-dial-text">—</span>
                        </span>
                        <input type="text" id="w-phone-local" value="{{ old('phone_local') }}" class="aws-input" placeholder="Ex: 6XXXXXXXX" style="flex:1">
                    </div>
                    <p class="aws-hint">Saisissez le numéro sans l'indicatif — il est ajouté automatiquement.</p>
                    <input type="hidden" name="phone_number" id="w-phone-hidden" value="{{ old('phone_number', $company->phone ?? '') }}">
                </div>

                <button type="submit" class="aws-btn aws-btn-primary" style="width:100%" {{ $availableBalance < 1000 ? 'disabled' : '' }}>Envoyer la demande</button>
                </form>
            </div>
        </div>

        <!-- Coordonnées bancaires -->
        <div class="aws-panel">
            <div class="aws-panel-header"><span class="aws-panel-title">Coordonnées bancaires <span style="font-size:12px;font-weight:400;color:var(--aws-sub)">— facultatif</span></span></div>
            <div class="aws-panel-body">
                <p class="aws-hint" style="margin-top:0">
                    @if($hasBankInfo)
                        Renseignées — vous pouvez choisir « Virement bancaire » comme moyen de paiement lors d'une demande de retrait.
                    @else
                        Non renseignées — renseignez-les ici pour pouvoir choisir le virement bancaire comme moyen de paiement.
                    @endif
                </p>
                <form method="POST" action="{{ route('company.withdrawals.bank-info') }}">
                @csrf
                <div class="aws-field">
                    <label class="aws-label">Nom de la banque</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name', $company->bank_name ?? '') }}" class="aws-input">
                </div>
                <div class="aws-field">
                    <label class="aws-label">IBAN</label>
                    <input type="text" name="bank_iban" value="{{ old('bank_iban', $company->bank_iban ?? '') }}" class="aws-input" style="font-family:monospace">
                </div>
                <div class="aws-field">
                    <label class="aws-label">SWIFT/BIC</label>
                    <input type="text" name="bank_swift" value="{{ old('bank_swift', $company->bank_swift ?? '') }}" class="aws-input" style="font-family:monospace">
                </div>
                <div class="aws-field">
                    <label class="aws-label">Adresse de la banque</label>
                    <input type="text" name="bank_address" value="{{ old('bank_address', $company->bank_address ?? '') }}" class="aws-input">
                </div>
                <button type="submit" class="aws-btn aws-btn-normal" style="width:100%">Enregistrer</button>
                </form>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
(function () {
    const methodSelect   = document.getElementById('w-method');
    const phoneField     = document.getElementById('w-phone-field');
    const countrySelect  = document.getElementById('w-country');
    const countryFlagImg = document.getElementById('w-country-flag');
    const chipsContainer = document.getElementById('w-operator-chips');
    const operatorHidden = document.getElementById('w-operator-hidden');
    const dialFlagImg    = document.getElementById('w-dial-flag');
    const dialText       = document.getElementById('w-dial-text');
    const phoneLocal     = document.getElementById('w-phone-local');
    const phoneHidden    = document.getElementById('w-phone-hidden');
    const form           = phoneHidden ? phoneHidden.closest('form') : null;
    if (!methodSelect || !phoneField) return;

    function togglePhone() {
        phoneField.style.display = methodSelect.value === 'mobile_money' ? '' : 'none';
    }
    methodSelect.addEventListener('change', togglePhone);
    togglePhone();

    function initials(name) {
        return name.split(' ').map(w => w[0]).filter(Boolean).slice(0, 2).join('').toUpperCase();
    }

    function renderOperatorChips(ops, preselect) {
        if (!chipsContainer) return;
        chipsContainer.innerHTML = '';

        if (!ops.length) {
            chipsContainer.innerHTML = '<span style="font-size:12px;color:var(--aws-sub)">— Choisir un pays d\'abord —</span>';
            if (operatorHidden) operatorHidden.value = '';
            return;
        }

        // Si l'opérateur précédemment choisi n'existe plus pour ce pays, on repart à vide.
        const stillValid = ops.some(op => op.name === preselect);
        if (operatorHidden) operatorHidden.value = stillValid ? preselect : '';

        ops.forEach(op => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.dataset.value = op.name;
            const active = stillValid && op.name === preselect;
            chip.style.cssText = 'display:flex;align-items:center;gap:8px;padding:6px 12px 6px 6px;'
                + 'border-radius:20px;cursor:pointer;background:#fff;'
                + 'border:2px solid ' + (active ? op.color : 'var(--aws-border)') + ';'
                + (active ? 'box-shadow:0 0 0 2px ' + op.color + '22;' : '');
            chip.innerHTML =
                '<span style="width:22px;height:22px;border-radius:50%;background:' + op.color + ';'
                + 'color:#fff;font-size:9px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0">'
                + initials(op.name) + '</span>'
                + '<span style="font-size:13px;font-weight:' + (active ? '700' : '500') + '">' + op.name + '</span>';

            chip.addEventListener('click', () => {
                if (operatorHidden) operatorHidden.value = op.name;
                renderOperatorChips(ops, op.name);
            });

            chipsContainer.appendChild(chip);
        });
    }

    // ✅ Met à jour le drapeau + l'indicatif + les "logos" (badges colorés)
    // d'opérateurs (issus de config/mobile_money.php via les data-attributes
    // des <option>) dès qu'un pays est choisi.
    function refreshCountryMeta() {
        if (!countrySelect) return;
        const opt = countrySelect.options[countrySelect.selectedIndex];
        const dial = opt ? (opt.dataset.dial || '') : '';
        const flagUrl = opt ? (opt.dataset.flagUrl || '') : '';
        let ops = [];
        try { ops = opt && opt.dataset.operators ? JSON.parse(opt.dataset.operators) : []; } catch (e) { ops = []; }

        if (countryFlagImg) {
            if (flagUrl) { countryFlagImg.src = flagUrl; countryFlagImg.style.display = ''; }
            else { countryFlagImg.style.display = 'none'; }
        }
        if (dialFlagImg) {
            if (flagUrl) { dialFlagImg.src = flagUrl; dialFlagImg.style.display = ''; }
            else { dialFlagImg.style.display = 'none'; }
        }
        if (dialText) dialText.textContent = dial || '—';

        renderOperatorChips(ops, operatorHidden ? operatorHidden.value : '');
    }
    if (countrySelect) {
        countrySelect.addEventListener('change', refreshCountryMeta);
        refreshCountryMeta();
    }

    // Recompose le numéro complet (indicatif + local) dans le champ caché
    // juste avant l'envoi, pour stocker un numéro exploitable par Peex.
    if (form) {
        form.addEventListener('submit', function () {
            const dial  = countrySelect ? (countrySelect.options[countrySelect.selectedIndex]?.dataset.dial || '') : '';
            const local = (phoneLocal ? phoneLocal.value : '').replace(/\D/g, '').replace(/^0+/, '');
            if (methodSelect.value === 'mobile_money' && dial && local) {
                phoneHidden.value = dial + local;
            }
        });
    }
})();
</script>
@endpush
@endsection
