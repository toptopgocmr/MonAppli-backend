@extends('admin.layouts.app')
@section('title','Passerelles de paiement')

@section('content')
@php
    $lbl = fn ($g) => \App\Services\Payment\GatewayRouter::label($g);
@endphp
<style>
    .pg-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px}
    .pg-row{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}
    .pg-row label{font-size:12px;font-weight:600;color:#475569}
    .pg-help{font-size:11px;color:#64748b;line-height:1.5}
    .pg-chain{display:flex;flex-wrap:wrap;align-items:center;gap:6px;font-size:12px}
    .pg-pill{padding:2px 10px;border-radius:999px;font-weight:600;font-size:11px;border:1px solid}
    .pg-main{background:#EFF6FF;color:#1D4ED8;border-color:#BFDBFE}
    .pg-back{background:#FFF7ED;color:#C2410C;border-color:#FED7AA}
    .pg-none{background:#F8FAFC;color:#94A3B8;border-color:#E2E8F0}
    .pg-table{width:100%;border-collapse:collapse;font-size:12px}
    .pg-table th{text-align:left;color:#64748b;font-weight:600;padding:8px 10px;border-bottom:1px solid #e2e8f0;background:#f8fafc}
    .pg-table td{padding:8px 10px;border-bottom:1px solid #f1f5f9;vertical-align:top}
    .ok{color:#067D49;font-weight:700} .ko{color:#D13212;font-weight:700}
    .switch{display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer}
    .switch input{width:16px;height:16px}
</style>

<div style="display:flex;flex-direction:column;gap:20px">

    <div>
        <h1 class="page-title">Passerelles de paiement</h1>
        <p class="page-sub">
            Choisissez la passerelle <strong>par défaut</strong> et la passerelle de <strong>secours</strong>.
            Si la passerelle par défaut refuse ou est en panne, la passerelle de secours prend le relais automatiquement.
            Par sécurité, il n'y a <strong>pas</strong> de bascule quand une passerelle ne répond pas à temps
            (le paiement a pu partir) : il faut alors vérifier dans son tableau de bord.
        </p>
    </div>

    @if($errors->any())
        <div class="flash flash-error"><span>{{ $errors->first() }}</span></div>
    @endif

    {{-- ── Réglages ─────────────────────────────────────────── --}}
    <form method="POST" action="{{ route('admin.payment-gateways.update') }}">
        @csrf
        <div class="pg-grid">
            <div class="card">
                <div class="card-header">💳 Collecte — paiement des réservations</div>
                <div class="card-body">
                    <div class="pg-row">
                        <label>Passerelle par défaut</label>
                        <select name="collect_primary" class="ttg-select">
                            @foreach($collectOptions as $k => $name)
                                <option value="{{ $k }}" @selected(old('collect_primary', $settings['collect_primary']) === $k)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pg-row">
                        <label>Passerelle de secours</label>
                        <select name="collect_fallback" class="ttg-select">
                            <option value="">— Aucune —</option>
                            @foreach($collectOptions as $k => $name)
                                <option value="{{ $k }}" @selected(old('collect_fallback', $settings['collect_fallback']) === $k)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="collect_auto" value="1" @checked(old('collect_auto', $settings['collect_auto']))>
                        Bascule automatique sur la passerelle de secours
                    </label>
                    <p class="pg-help" style="margin-top:10px">
                        Un pays non couvert par ces deux passerelles utilise automatiquement la première passerelle qui le couvre
                        (ex. Flutterwave pour le Ghana ou le Kenya).
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">💸 Décaissement — retraits chauffeurs et sociétés</div>
                <div class="card-body">
                    <div class="pg-row">
                        <label>Passerelle par défaut</label>
                        <select name="payout_primary" class="ttg-select">
                            @foreach($payoutOptions as $k => $name)
                                <option value="{{ $k }}" @selected(old('payout_primary', $settings['payout_primary']) === $k)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pg-row">
                        <label>Passerelle de secours</label>
                        <select name="payout_fallback" class="ttg-select">
                            <option value="">— Aucune —</option>
                            @foreach($payoutOptions as $k => $name)
                                <option value="{{ $k }}" @selected(old('payout_fallback', $settings['payout_fallback']) === $k)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="payout_auto" value="1" @checked(old('payout_auto', $settings['payout_auto']))>
                        Bascule automatique sur la passerelle de secours
                    </label>
                    <p class="pg-help" style="margin-top:10px">
                        La passerelle de secours n'est utilisée que pour les pays qu'elle couvre (voir tableau ci-dessous).
                        Sans passerelle disponible, le retrait reste en attente : utilisez « Payé manuellement » après un paiement hors plateforme.
                    </p>
                </div>
            </div>
        </div>
        <div class="card" style="margin-top:20px">
            <div class="card-header">🧾 Frais de service payés par le client</div>
            <div class="card-body">
                <p class="pg-help" style="margin:0 0 14px">
                    Ajoutés au prix de la course au moment du paiement. Ils couvrent les frais du partenaire de paiement
                    (Peex, Digitwace…) et la marge TopTopGo. La commission prélevée sur le chauffeur reste réglée dans
                    « Commissions ». Le montant est figé à la réservation : un changement ici ne touche que les nouvelles réservations.
                </p>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;align-items:end">
                    <label class="switch" style="margin-bottom:8px">
                        <input type="checkbox" name="service_fee_enabled" value="1" @checked(old('service_fee_enabled', $fee['enabled']))>
                        Frais de service activés
                    </label>
                    <div class="pg-row" style="margin:0">
                        <label>Pourcentage de la course (%)</label>
                        <input class="ttg-input" type="number" step="0.1" min="0" max="50" name="service_fee_percent" value="{{ old('service_fee_percent', $fee['percent']) }}">
                    </div>
                    <div class="pg-row" style="margin:0">
                        <label>Minimum (XAF)</label>
                        <input class="ttg-input" type="number" step="1" min="0" name="service_fee_min" value="{{ old('service_fee_min', $fee['min']) }}">
                    </div>
                    <div class="pg-row" style="margin:0">
                        <label>Plafond (XAF, 0 = aucun)</label>
                        <input class="ttg-input" type="number" step="1" min="0" name="service_fee_max" value="{{ old('service_fee_max', $fee['max']) }}">
                    </div>
                </div>
                <table class="pg-table" style="margin-top:16px">
                    <thead><tr><th>Prix de la course</th><th>Frais de service</th><th>Total payé par le client</th></tr></thead>
                    <tbody>
                    @foreach($feeExamples as $ex)
                        <tr>
                            <td>{{ number_format($ex['course_amount'], 0, ',', ' ') }} XAF</td>
                            <td>+ {{ number_format($ex['service_fee'], 0, ',', ' ') }} XAF</td>
                            <td><strong>{{ number_format($ex['total_to_pay'], 0, ',', ' ') }} XAF</strong></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <p class="pg-help" style="margin-top:6px">Exemples calculés avec les réglages enregistrés.</p>
            </div>
        </div>

        <div style="margin-top:16px;display:flex;justify-content:flex-end">
            <button class="btn btn-primary">Enregistrer</button>
        </div>
    </form>

    {{-- ── Ordre appliqué par pays ───────────────────────────── --}}
    <div class="card">
        <div class="card-header">Ordre réellement appliqué (avec les réglages enregistrés)</div>
        <div class="card-body" style="padding:0">
            <table class="pg-table">
                <thead><tr><th>Pays</th><th>Collecte</th><th>Décaissement</th></tr></thead>
                <tbody>
                @foreach($preview as $country => $p)
                    <tr>
                        <td><strong>{{ $country }}</strong></td>
                        @foreach(['collect', 'payout'] as $kind)
                        <td>
                            <div class="pg-chain">
                                @forelse($p[$kind] as $i => $g)
                                    @if($i) <span>→</span> @endif
                                    <span class="pg-pill {{ $i ? 'pg-back' : 'pg-main' }}">{{ $lbl($g) }}</span>
                                @empty
                                    <span class="pg-pill pg-none">{{ $kind === 'payout' ? 'Aucune — paiement manuel' : 'Aucune' }}</span>
                                @endforelse
                            </div>
                        </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── État des passerelles ──────────────────────────────── --}}
    <div class="card">
        <div class="card-header">État des passerelles</div>
        <div class="card-body" style="padding:0">
            <table class="pg-table">
                <thead><tr><th>Passerelle</th><th>Collecte</th><th>Décaissement</th><th>Remarque</th><th></th></tr></thead>
                <tbody>
                @foreach($gateways as $key => $g)
                    <tr>
                        <td><strong>{{ $lbl($key) }}</strong></td>
                        <td>
                            <span class="{{ $g['collect_ok'] ? 'ok' : 'ko' }}">{{ $g['collect_ok'] ? 'Configurée' : 'Non configurée' }}</span><br>
                            <span class="pg-help">{{ implode(', ', $g['collect_countries']) ?: '—' }}</span>
                        </td>
                        <td>
                            @if(empty($g['payout_countries']) && !$g['payout_ok'])
                                <span class="pg-help">Non utilisée</span>
                            @else
                                <span class="{{ $g['payout_ok'] ? 'ok' : 'ko' }}">{{ $g['payout_ok'] ? 'Configurée' : 'Non configurée' }}</span><br>
                                <span class="pg-help">{{ implode(', ', $g['payout_countries']) ?: '—' }}</span>
                            @endif
                        </td>
                        <td class="pg-help">{{ $g['hint'] }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.payment-gateways.test', $key) }}">
                                @csrf
                                <button class="btn btn-secondary" style="padding:3px 10px;font-size:12px">Tester</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Volumes 30 jours ──────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">Collecte des 30 derniers jours par passerelle</div>
        <div class="card-body" style="padding:0">
            <table class="pg-table">
                <thead><tr><th>Passerelle</th><th>Réussis</th><th>Échoués / annulés</th><th>En attente</th><th>Montant encaissé</th></tr></thead>
                <tbody>
                @forelse($stats as $provider => $rows)
                    @php
                        $by = $rows->keyBy('status');
                        $failed = ($by['failed']->n ?? 0) + ($by['cancelled']->n ?? 0);
                    @endphp
                    <tr>
                        <td><strong>{{ $lbl($provider) }}</strong></td>
                        <td class="ok">{{ $by['success']->n ?? 0 }}</td>
                        <td class="ko">{{ $failed }}</td>
                        <td>{{ $by['pending']->n ?? 0 }}</td>
                        <td>{{ number_format($by['success']->total ?? 0, 0, ',', ' ') }} XAF</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="pg-help">Aucun paiement sur la période.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Dernières bascules / échecs ───────────────────────── --}}
    <div class="card">
        <div class="card-header">Dernières bascules et échecs de passerelle</div>
        <div class="card-body" style="padding:0">
            <table class="pg-table">
                <thead><tr><th>Date</th><th>Opération</th><th>Montant</th><th>Tentatives</th><th>Résultat</th></tr></thead>
                <tbody>
                @forelse($events as $e)
                    <tr>
                        <td>{{ optional($e['date'])->format('d/m/Y H:i') }}</td>
                        <td>{{ $e['type'] }} #{{ $e['id'] }}</td>
                        <td>{{ number_format($e['amount'], 0, ',', ' ') }}</td>
                        <td>
                            @foreach($e['attempts'] as $a)
                                <div>
                                    {{ !empty($a['ok']) ? '✅' : '❌' }}
                                    <strong>{{ $lbl($a['gateway'] ?? '') }}</strong>
                                    <span class="pg-help">({{ $a['role'] ?? '' }})</span>
                                    @if(empty($a['ok']) && !empty($a['error']))
                                        <div class="pg-help">{{ \Illuminate\Support\Str::limit($a['error'], 140) }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </td>
                        <td>{{ $e['status'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="pg-help">Aucune bascule ni aucun échec enregistré pour l'instant.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
