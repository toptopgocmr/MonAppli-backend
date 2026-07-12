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

{{-- ✅ Toast flottant en plus du bandeau ci-dessus : le bandeau est en
     haut de page et peut passer inaperçu (formulaire de retrait tout en
     bas de la colonne de droite). Le toast reste visible à l'écran
     n'importe où on se trouve après l'envoi de la demande. --}}
@if(session('success') || session('error') || $errors->any())
<div id="w-toast" style="position:fixed;top:20px;right:20px;z-index:9999;max-width:360px;
    padding:14px 18px;border-radius:8px;font-size:14px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.18);
    {{ session('success') ? 'background:#1d8102;color:#fff;' : 'background:#d13212;color:#fff;' }}">
    {{ session('success') ?? session('error') ?? $errors->first() }}
</div>
<script>
    setTimeout(function () {
        const t = document.getElementById('w-toast');
        if (t) { t.style.transition = 'opacity .4s'; t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }
    }, 4500);
</script>
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
        <div class="aws-hint" style="padding:10px 20px 0">
            ⏳ Chaque demande « En attente » est vérifiée manuellement par l'administration avant que le paiement soit envoyé — comptez 24h à 48h de traitement.
        </div>
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
                                        data-mm="{{ $c['mobile_money_ok'] ? '1' : '0' }}"
                                        data-bank="{{ $c['bank_ok'] ? '1' : '0' }}"
                                        {{ old('country') === $c['code'] ? 'selected' : '' }}>
                                    {{ $c['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if(empty($payoutCountries))
                    <p class="aws-hint">Aucun pays de retrait n'est actuellement disponible.</p>
                    @endif
                    <p class="aws-hint" id="w-country-scope-hint"></p>
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
                            <img id="w-dial-flag" src="" alt="" style="width:20px;height:14px;border-radius:2px;object-fit