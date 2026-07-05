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
<div class="aws-stat-grid" style="grid-template-columns:repeat(2,1fr);margin-bottom:16px">
    <div class="aws-stat-card" style="border-top:3px solid #1d8102">
        <div class="aws-stat-label">Solde disponible au retrait</div>
        <div class="aws-stat-value" style="font-size:22px">{{ number_format($availableBalance, 0, ',', ' ') }}<span style="font-size:12px;font-weight:400;margin-left:4px">FCFA</span></div>
        <div class="aws-stat-sub">Net de commission, après retraits déjà demandés</div>
    </div>
    <div class="aws-stat-card" style="border-top:3px solid #0073bb">
        <div class="aws-stat-label">Revenu net total (historique)</div>
        <div class="aws-stat-value" style="font-size:22px">{{ number_format($totalNetRevenue, 0, ',', ' ') }}<span style="font-size:12px;font-weight:400;margin-left:4px">FCFA</span></div>
        <div class="aws-stat-sub">Courses terminées, commission déduite</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1.3fr 1fr;gap:16px">

    <!-- Historique -->
    <div class="aws-panel">
        <div class="aws-panel-header"><span class="aws-panel-title">Historique des demandes</span></div>
        <div style="overflow-x:auto">
            <table class="aws-table">
                <thead>
                    <tr><th>Montant</th><th>Statut</th><th>Référence</th><th>Demandé le</th><th>Traité le</th></tr>
                </thead>
                <tbody>
                    @forelse($withdrawals as $w)
                    @php
                        $stMap = ['pending'=>['aws-badge-yellow','En attente'],'success'=>['aws-badge-green','Payé'],'failed'=>['aws-badge-red','Rejeté']];
                        $sc = $stMap[$w->status] ?? ['aws-badge-gray', $w->status];
                    @endphp
                    <tr>
                        <td style="font-weight:600">{{ number_format($w->amount, 0, ',', ' ') }} FCFA</td>
                        <td><span class="aws-badge {{ $sc[0] }}">{{ $sc[1] }}</span></td>
                        <td>{{ $w->transaction_ref ?? '—' }}</td>
                        <td>{{ $w->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $w->processed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--aws-sub)">Aucune demande de retrait pour le moment.</td></tr>
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
                    <input type="number" name="amount" min="1000" max="{{ $availableBalance }}" step="100" required class="aws-input" placeholder="Ex: 50000">
                    <p class="aws-hint">Solde disponible : {{ number_format($availableBalance, 0, ',', ' ') }} FCFA</p>
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
                        Renseignées — le virement bancaire sera tenté en priorité. Sinon, un virement mobile money ou un paiement manuel sera utilisé.
                    @else
                        Non renseignées — vos retraits seront payés par mobile money (si couvert) ou manuellement par l'administration.
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
@endsection
