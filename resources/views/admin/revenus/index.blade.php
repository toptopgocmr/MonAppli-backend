@extends('admin.layouts.app')
@section('title','Revenus')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px">

{{-- Header --}}
<div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px">
    <div>
        <h1 class="page-title">Revenus</h1>
        <p class="page-sub">Analyse complète des revenus générés par la plateforme</p>
    </div>
    <a href="{{ route('admin.revenus.export', request()->query()) }}" class="btn btn-success">
        Exporter Excel
    </a>
</div>

{{-- KPI Cards --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px">
    @php $diffDay = $kpis['yesterday'] > 0 ? (($kpis['today'] - $kpis['yesterday']) / $kpis['yesterday']) * 100 : 0; @endphp
    <div class="stat-card" style="border-left:4px solid #1DA1F2">
        <div class="lbl">AUJOURD'HUI</div>
        <div class="val" style="color:#1DA1F2;font-size:20px">{{ number_format($kpis['today'],0,',',' ') }} XAF</div>
        <div class="sub" style="color:{{ $diffDay>=0?'#16a34a':'#dc2626' }};font-weight:600">{{ $diffDay>=0?'↑':'↓' }} {{ number_format(abs($diffDay),1) }}% vs hier</div>
    </div>
    @php $diffWeek = $kpis['last_week_total'] > 0 ? (($kpis['this_week'] - $kpis['last_week_total']) / $kpis['last_week_total']) * 100 : 0; @endphp
    <div class="stat-card" style="border-left:4px solid #8b5cf6">
        <div class="lbl">CETTE SEMAINE</div>
        <div class="val" style="color:#7c3aed;font-size:20px">{{ number_format($kpis['this_week'],0,',',' ') }} XAF</div>
        <div class="sub" style="color:{{ $diffWeek>=0?'#16a34a':'#dc2626' }};font-weight:600">{{ $diffWeek>=0?'↑':'↓' }} {{ number_format(abs($diffWeek),1) }}% vs sem. dern.</div>
    </div>
    @php $diffMonth = $kpis['last_month'] > 0 ? (($kpis['this_month'] - $kpis['last_month']) / $kpis['last_month']) * 100 : 0; @endphp
    <div class="stat-card" style="border-left:4px solid #22c55e">
        <div class="lbl">CE MOIS</div>
        <div class="val" style="color:#16a34a;font-size:20px">{{ number_format($kpis['this_month'],0,',',' ') }} XAF</div>
        <div class="sub" style="color:{{ $diffMonth>=0?'#16a34a':'#dc2626' }};font-weight:600">{{ $diffMonth>=0?'↑':'↓' }} {{ number_format(abs($diffMonth),1) }}% vs mois dern.</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #f59e0b">
        <div class="lbl">CETTE ANNÉE</div>
        <div class="val" style="color:#d97706;font-size:20px">{{ number_format($kpis['this_year'],0,',',' ') }} XAF</div>
        <div class="sub">Total all time : {{ number_format($kpis['total_all'],0,',',' ') }} XAF</div>
    </div>
</div>

{{-- Onglets période --}}
<div style="display:flex;gap:8px;flex-wrap:wrap">
    @foreach(['day'=>'Journalier','week'=>'Hebdomadaire','month'=>'Mensuel','year'=>'Annuel'] as $key=>$label)
        <a href="{{ route('admin.revenus.index', array_merge(request()->query(), ['period'=>$key])) }}"
           style="padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:1.5px solid;text-decoration:none;transition:all .2s;
           {{ request('period','month')===$key ? 'background:#1DA1F2;color:#fff;border-color:#1DA1F2' : 'background:#fff;color:#475569;border-color:#e2e8f0' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

{{-- ═══════════════════════════════════════════════════════════
     FILTRES
═══════════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl shadow p-6 mb-6">
    <p class="text-sm font-bold text-gray-700 mb-4">Filtres de recherche</p>

    <form method="GET" action="{{ route('admin.revenus.index') }}">
        <input type="hidden" name="period" value="{{ request('period', 'month') }}">

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">

            <div>
                <label class="text-xs text-gray-400 font-semibold uppercase block mb-1">Date début</label>
                <input type="date" name="date_start" value="{{ request('date_start') }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#1DA1F2]">
            </div>

            <div>
                <label class="text-xs text-gray-400 font-semibold uppercase block mb-1">Date fin</label>
                <input type="date" name="date_end" value="{{ request('date_end') }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#1DA1F2]">
            </div>

            <div>
                <label class="text-xs text-gray-400 font-semibold uppercase block mb-1">Pays</label>
                <select name="country_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#1DA1F2]">
                    <option value="">Tous les pays</option>
                    @foreach($filters['countries'] as $country)
                        <option value="{{ $country->id }}" {{ request('country_id') == $country->id ? 'selected' : '' }}>
                            {{ $country->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs text-gray-400 font-semibold uppercase block mb-1">Ville</label>
                <select name="city_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#1DA1F2]">
                    <option value="">Toutes les villes</option>
                    @foreach($filters['cities'] as $city)
                        <option value="{{ $city->id }}" {{ request('city_id') == $city->id ? 'selected' : '' }}>
                            {{ $city->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs text-gray-400 font-semibold uppercase block mb-1">Chauffeur</label>
                <select name="driver_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#1DA1F2]">
                    <option value="">Tous les chauffeurs</option>
                    @foreach($filters['drivers'] as $driver)
                        <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>
                            {{ $driver->user->name ?? '-' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs text-gray-400 font-semibold uppercase block mb-1">Client</label>
                <select name="user_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#1DA1F2]">
                    <option value="">Tous les clients</option>
                    @foreach($filters['clients'] as $client)
                        <option value="{{ $client->id }}" {{ request('user_id') == $client->id ? 'selected' : '' }}>
                            {{ $client->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs text-gray-400 font-semibold uppercase block mb-1">Montant min (XAF)</label>
                <input type="number" name="montant_min" value="{{ request('montant_min') }}"
                       placeholder="0"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#1DA1F2]">
            </div>

            <div>
                <label class="text-xs text-gray-400 font-semibold uppercase block mb-1">Montant max (XAF)</label>
                <input type="number" name="montant_max" value="{{ request('montant_max') }}"
                       placeholder="999999"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#1DA1F2]">
            </div>

        </div>

        <div class="flex gap-3 mt-5">
            <button type="submit"
                    class="bg-[#1DA1F2] hover:bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-semibold transition-all duration-300">
                Appliquer
            </button>
            <a href="{{ route('admin.revenus.index') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-6 py-2 rounded-lg text-sm font-semibold transition-all duration-300">
                Réinitialiser
            </a>
        </div>

    </form>
</div>

{{-- ═══════════════════════════════════════════════════════════
     TABLEAU
═══════════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl shadow overflow-hidden">

    <div class="flex items-center justify-between px-6 py-4 border-b">
        <p class="font-bold text-gray-700">Détail des transactions</p>
        <p class="text-sm text-gray-400">
            {{ $data->total() }} résultat(s) —
            Total filtré : <span class="font-bold text-green-600">{{ number_format($total_query, 0, ',', ' ') }} XAF</span>
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-400 font-semibold">
                <tr>
                    <th class="px-4 py-3 text-left">#</th>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-left">Chauffeur</th>
                    <th class="px-4 py-3 text-left">Client</th>
                    <th class="px-4 py-3 text-left">Pays</th>
                    <th class="px-4 py-3 text-left">Ville</th>
                    <th class="px-4 py-3 text-left">Montant course</th>
                    <th class="px-4 py-3 text-left">Taux</th>
                    <th class="px-4 py-3 text-left">Commission</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($data as $commission)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-gray-400 text-xs">#{{ $commission->id }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $commission->earned_at?->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3 font-semibold text-gray-800">
                            {{ $commission->driver?->user?->name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $commission->user?->name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $commission->country?->name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $commission->city?->name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-gray-700 font-semibold">
                            {{ number_format($commission->montant_course, 0, ',', ' ') }} {{ $commission->currency }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded-full text-xs font-semibold">
                                {{ $commission->taux_applique }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 font-bold text-green-600">
                            {{ number_format($commission->montant_commission, 0, ',', ' ') }} {{ $commission->currency }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center text-gray-400">
                            Aucune commission trouvée pour ces filtres.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    @if($data->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $data->appends(request()->query())->links() }}
        </div>
    @endif

</div>
</div>
@endsection
