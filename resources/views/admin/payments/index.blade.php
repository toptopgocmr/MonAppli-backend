@extends('admin.layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">💱 Partenaires Payeurs</h2>
            <p class="text-gray-500 text-sm mt-1">Suivi en temps réel des paiements, retraits et wallets</p>
        </div>
        <a href="{{ route('admin.payments.export') }}?period={{ $period }}"
           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
            📥 Exporter CSV
        </a>
    </div>

    {{-- Période --}}
    <div class="flex flex-wrap gap-2 mb-6">
        @foreach(['today' => "Aujourd'hui", 'week' => 'Cette semaine', 'month' => 'Ce mois', 'year' => 'Cette année'] as $key => $label)
        <a href="?period={{ $key }}"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition
           {{ $period === $key ? 'bg-[#1DA1F2] text-white' : 'bg-white text-gray-600 hover:bg-gray-100 shadow-sm' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    {{-- ===== KPI GLOBAUX ===== --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-xs text-gray-400 font-semibold uppercase">Revenus total</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalRevenue, 0, ',', ' ') }}</p>
            <p class="text-xs text-gray-400">FCFA</p>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-xs text-gray-400 font-semibold uppercase">Commission TTG</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($totalCommission, 0, ',', ' ') }}</p>
            <p class="text-xs text-gray-400">FCFA</p>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-xs text-gray-400 font-semibold uppercase">Net Chauffeurs</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($totalDriverNet, 0, ',', ' ') }}</p>
            <p class="text-xs text-gray-400">FCFA</p>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-xs text-gray-400 font-semibold uppercase">En attente</p>
            <p class="text-2xl font-bold text-orange-500 mt-1">{{ $totalPending }}</p>
            <p class="text-xs text-red-400">{{ $totalFailed }} échoués</p>
        </div>
    </div>

    {{-- ===== CARTES PARTENAIRES ===== --}}
    <h3 class="text-lg font-bold text-gray-700 mb-3">📊 Par partenaire</h3>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        @foreach($partnerStats as $key => $partner)
        <div class="bg-white rounded-xl shadow p-4 border-t-4
            @if($key === 'mtn') border-yellow-400
            @elseif($key === 'orange') border-orange-400
            @elseif($key === 'airtel') border-red-500
            @elseif($key === 'moov') border-blue-500
            @elseif($key === 'visa') border-indigo-500
            @else border-purple-500 @endif">

            <div class="text-2xl mb-1">{{ $partner['icon'] }}</div>
            <p class="text-xs font-bold text-gray-700">{{ $partner['name'] }}</p>
            <p class="text-xl font-bold text-gray-800 mt-2">{{ number_format($partner['total'], 0, ',', ' ') }}</p>
            <p class="text-xs text-gray-400">FCFA — {{ $partner['count'] }} paiements</p>
            <div class="flex gap-2 mt-2 text-xs">
                <span class="text-orange-500">⏳ {{ $partner['pending'] }}</span>
                <span class="text-red-500">✗ {{ $partner['failed'] }}</span>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ===== WALLET APPLICATION + RETRAITS ===== --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

        {{-- Wallet App --}}
        <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl shadow p-5 text-white">
            <p class="text-sm font-semibold opacity-80">💼 Wallet Application</p>
            <p class="text-3xl font-bold mt-2">{{ number_format($totalWalletBalance, 0, ',', ' ') }}</p>
            <p class="text-sm opacity-70">FCFA — {{ $totalWallets }} wallets actifs</p>
            <div class="flex justify-between mt-4 text-sm">
                <div>
                    <p class="opacity-70">Crédits</p>
                    <p class="font-bold text-green-300">+{{ number_format($totalCredits, 0, ',', ' ') }}</p>
                </div>
                <div>
                    <p class="opacity-70">Débits</p>
                    <p class="font-bold text-red-300">-{{ number_format($totalDebits, 0, ',', ' ') }}</p>
                </div>
            </div>
        </div>

        {{-- Retraits --}}
        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-sm font-semibold text-gray-600">💸 Retraits Chauffeurs</p>
            <div class="mt-3 space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-xs text-orange-500 font-semibold">⏳ En attente</span>
                    <span class="text-lg font-bold text-orange-500">{{ $withdrawalsPending }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-green-600 font-semibold">✓ Validés</span>
                    <span class="text-lg font-bold text-green-600">{{ number_format($withdrawalsSuccess, 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-red-500 font-semibold">✗ Échoués</span>
                    <span class="text-lg font-bold text-red-500">{{ $withdrawalsFailed }}</span>
                </div>
            </div>
        </div>

        {{-- Top Wallets --}}
        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-sm font-semibold text-gray-600 mb-3">🏆 Top Wallets</p>
            <div class="space-y-2">
                @forelse($topWallets as $wallet)
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-700 truncate">
                        {{ optional($wallet->driver)->first_name }} {{ optional($wallet->driver)->last_name }}
                    </span>
                    <span class="font-bold text-blue-600 shrink-0 ml-2">
                        {{ number_format($wallet->balance, 0, ',', ' ') }} {{ $wallet->currency }}
                    </span>
                </div>
                @empty
                <p class="text-gray-400 text-xs">Aucun wallet</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ===== RETRAITS EN ATTENTE ===== --}}
    @if($withdrawals->where('status', 'pending')->count() > 0)
    <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 mb-6">
        <h3 class="text-sm font-bold text-orange-700 mb-3">⚠️ Retraits en attente d'approbation</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-orange-600 uppercase">
                        <th class="text-left py-2 px-3">Chauffeur</th>
                        <th class="text-left py-2 px-3">Méthode</th>
                        <th class="text-left py-2 px-3">Téléphone</th>
                        <th class="text-right py-2 px-3">Montant</th>
                        <th class="text-left py-2 px-3">Date</th>
                        <th class="text-center py-2 px-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-orange-100">
                    @foreach($withdrawals->where('status', 'pending') as $w)
                    <tr>
                        <td class="py-2 px-3 font-medium">
                            {{ optional($w->driver)->first_name }} {{ optional($w->driver)->last_name }}
                        </td>
                        <td class="py-2 px-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                @if($w->method === 'mtn') bg-yellow-100 text-yellow-700
                                @elseif($w->method === 'orange') bg-orange-100 text-orange-700
                                @elseif($w->method === 'airtel') bg-red-100 text-red-700
                                @else bg-blue-100 text-blue-700 @endif">
                                {{ strtoupper($w->method) }}
                            </span>
                        </td>
                        <td class="py-2 px-3 text-gray-500">{{ $w->phone_number }}</td>
                        <td class="py-2 px-3 text-right font-bold text-gray-800">
                            {{ number_format($w->amount, 0, ',', ' ') }} XAF
                        </td>
                        <td class="py-2 px-3 text-gray-400 text-xs">
                            {{ $w->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="py-2 px-3 text-center">
                            <div class="flex justify-center gap-1">
                                <form action="{{ route('admin.payments.approve-withdrawal', $w->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded-lg transition"
                                        onclick="return confirm('Approuver ce retrait de {{ number_format($w->amount, 0) }} XAF ?')">
                                        ✓ Approuver
                                    </button>
                                </form>
                                <form action="{{ route('admin.payments.reject-withdrawal', $w->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button class="bg-red-500 hover:bg-red-600 text-white text-xs px-3 py-1 rounded-lg transition"
                                        onclick="return confirm('Rejeter ce retrait ?')">
                                        ✗ Rejeter
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ===== FILTRES PAIEMENTS ===== --}}
    <div class="bg-white rounded-xl shadow p-4 mb-4">
        <form method="GET" action="{{ route('admin.payments.index') }}" class="flex flex-wrap gap-3 items-end">
            <input type="hidden" name="period" value="{{ $period }}">

            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Méthode</label>
                <select name="method" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Toutes</option>
                    @foreach(['mtn' => 'MTN Money', 'orange' => 'Orange Money', 'airtel' => 'Airtel Money', 'moov' => 'Moov Money', 'visa' => 'Visa/Stripe', 'mastercard' => 'Mastercard'] as $val => $label)
                        <option value="{{ $val }}" {{ request('method') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Statut</label>
                <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous</option>
                    @foreach(['pending' => '⏳ En attente', 'success' => '✓ Succès', 'failed' => '✗ Échoué', 'cancelled' => 'Annulé', 'refunded' => 'Remboursé'] as $val => $label)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Pays</label>
                <select name="country" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous</option>
                    @foreach($countries as $country)
                        <option value="{{ $country }}" {{ request('country') === $country ? 'selected' : '' }}>{{ $country }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="bg-[#1DA1F2] hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    🔍 Filtrer
                </button>
                <a href="{{ route('admin.payments.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm transition">
                    ✕ Reset
                </a>
            </div>
        </form>
    </div>

    {{-- ===== TABLEAU PAIEMENTS ===== --}}
    <div class="bg-white rounded-xl shadow overflow-hidden mb-6">
        <div class="px-5 py-3 border-b flex items-center justify-between">
            <span class="font-bold text-gray-700">💳 Transactions récentes</span>
            <span class="text-xs text-gray-400">{{ $payments->total() }} transactions</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Référence</th>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Client</th>
                        <th class="px-4 py-3 text-left">Chauffeur</th>
                        <th class="px-4 py-3 text-left">Méthode</th>
                        <th class="px-4 py-3 text-right">Montant</th>
                        <th class="px-4 py-3 text-right">Commission</th>
                        <th class="px-4 py-3 text-right">Net</th>
                        <th class="px-4 py-3 text-center">Statut</th>
                        <th class="px-4 py-3 text-left">Pays</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payments as $payment)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 font-mono text-xs text-gray-400">
                            {{ Str::limit($payment->transaction_ref, 12) ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">
                            {{ ($payment->paid_at ?? $payment->created_at)?->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3">
                            {{ optional($payment->user)->name ?? (optional($payment->user)->first_name . ' ' . optional($payment->user)->last_name) ?? '—' }}
                        </td>
                        <td class="px-4 py-3 font-medium">
                            {{ optional($payment->driver)->first_name }} {{ optional($payment->driver)->last_name }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold
                                @if($payment->method === 'mtn') bg-yellow-100 text-yellow-700
                                @elseif($payment->method === 'orange') bg-orange-100 text-orange-700
                                @elseif($payment->method === 'airtel') bg-red-100 text-red-700
                                @elseif($payment->method === 'moov') bg-blue-100 text-blue-700
                                @elseif($payment->method === 'visa') bg-indigo-100 text-indigo-700
                                @else bg-purple-100 text-purple-700 @endif">
                                {{ strtoupper($payment->method) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-medium">{{ number_format($payment->amount, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-right text-blue-600">{{ number_format($payment->commission, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-right text-green-600 font-semibold">{{ number_format($payment->driver_net, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                @if($payment->status === 'success') bg-green-100 text-green-700
                                @elseif($payment->status === 'pending') bg-orange-100 text-orange-700
                                @elseif($payment->status === 'failed') bg-red-100 text-red-700
                                @elseif($payment->status === 'refunded') bg-purple-100 text-purple-700
                                @else bg-gray-100 text-gray-600 @endif">
                                @if($payment->status === 'success') ✓ Succès
                                @elseif($payment->status === 'pending') ⏳ Attente
                                @elseif($payment->status === 'failed') ✗ Échoué
                                @elseif($payment->status === 'refunded') ↩ Remboursé
                                @else {{ $payment->status }} @endif
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $payment->country ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-gray-400 py-10">Aucune transaction pour cette période</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())
        <div class="px-4 py-3 border-t">{{ $payments->links() }}</div>
        @endif
    </div>

    {{-- ===== WALLET TRANSACTIONS RÉCENTES ===== --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

        {{-- Transactions wallet --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-3 border-b font-bold text-gray-700">💼 Mouvements Wallet</div>
            <div class="divide-y">
                @forelse($walletTransactions as $wt)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-gray-700">
                            {{ optional(optional($wt->wallet)->driver)->first_name }}
                            {{ optional(optional($wt->wallet)->driver)->last_name }}
                        </p>
                        <p class="text-xs text-gray-400">{{ $wt->description ?? $wt->reference ?? '—' }}</p>
                        <p class="text-xs text-gray-300">{{ $wt->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold {{ $wt->type === 'credit' ? 'text-green-600' : 'text-red-500' }}">
                            {{ $wt->type === 'credit' ? '+' : '-' }}{{ number_format($wt->amount, 0, ',', ' ') }} XAF
                        </p>
                        <p class="text-xs text-gray-400">Solde : {{ number_format($wt->balance_after, 0, ',', ' ') }}</p>
                    </div>
                </div>
                @empty
                <div class="text-center text-gray-400 py-8">Aucun mouvement</div>
                @endforelse
            </div>
        </div>

        {{-- Derniers retraits --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-3 border-b font-bold text-gray-700">💸 Derniers Retraits</div>
            <div class="divide-y">
                @forelse($withdrawals as $w)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-gray-700">
                            {{ optional($w->driver)->first_name }} {{ optional($w->driver)->last_name }}
                        </p>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-xs px-2 py-0.5 rounded-full font-semibold
                                @if($w->method === 'mtn') bg-yellow-100 text-yellow-700
                                @elseif($w->method === 'orange') bg-orange-100 text-orange-700
                                @elseif($w->method === 'airtel') bg-red-100 text-red-700
                                @else bg-blue-100 text-blue-700 @endif">
                                {{ strtoupper($w->method) }}
                            </span>
                            <span class="text-xs text-gray-400">{{ $w->phone_number }}</span>
                        </div>
                        <p class="text-xs text-gray-300">{{ $w->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-gray-800">{{ number_format($w->amount, 0, ',', ' ') }} XAF</p>
                        <span class="text-xs px-2 py-0.5 rounded-full font-semibold
                            @if($w->status === 'success') bg-green-100 text-green-700
                            @elseif($w->status === 'pending') bg-orange-100 text-orange-700
                            @else bg-red-100 text-red-700 @endif">
                            @if($w->status === 'success') ✓ Validé
                            @elseif($w->status === 'pending') ⏳ Attente
                            @else ✗ Échoué @endif
                        </span>
                    </div>
                </div>
                @empty
                <div class="text-center text-gray-400 py-8">Aucun retrait</div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection
