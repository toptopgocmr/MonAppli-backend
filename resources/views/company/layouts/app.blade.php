<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel Société') — TopTopGo</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/logo3.ico') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo3.ico') }}">
    <style>
    /* ── AWS Console Variables ── */
    :root {
        --aws-nav:     #232f3e;
        --aws-nav2:    #1a2433;
        --aws-orange:  #ec7211;
        --aws-orange2: #dd6b10;
        --aws-blue:    #0073bb;
        --aws-border:  #d5dbdb;
        --aws-bg:      #f2f3f3;
        --aws-white:   #ffffff;
        --aws-header:  #16191f;
        --aws-sub:     #687078;
        --aws-green:   #1d8102;
        --aws-red:     #d13212;
        --aws-yellow:  #8a6116;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Amazon Ember','Helvetica Neue',Helvetica,Arial,sans-serif; background: var(--aws-bg); min-height: 100vh; display: flex; flex-direction: column; }

    /* ── TOP BAR ── */
    .aws-topbar {
        background: var(--aws-nav);
        height: 44px;
        display: flex;
        align-items: center;
        padding: 0 16px;
        gap: 16px;
        flex-shrink: 0;
        position: sticky;
        top: 0;
        z-index: 100;
    }
    .aws-topbar-logo {
        font-size: 18px;
        font-weight: 900;
        letter-spacing: -0.5px;
        white-space: nowrap;
        text-decoration: none;
    }
    .aws-topbar-logo span.t { color: #1DA1F2; }
    .aws-topbar-logo span.g { color: var(--aws-orange); }
    .aws-topbar-divider { width: 1px; height: 24px; background: rgba(255,255,255,0.2); }
    .aws-topbar-company { font-size: 13px; color: rgba(255,255,255,0.85); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px; }
    .aws-topbar-right { margin-left: auto; display: flex; align-items: center; gap: 12px; }
    .aws-topbar-avatar { width: 28px; height: 28px; border-radius: 50%; background: var(--aws-orange); color: #fff; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden; }
    .aws-topbar-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .aws-topbar-email { font-size: 12px; color: rgba(255,255,255,0.7); }

    /* ── BODY LAYOUT ── */
    .aws-body { display: flex; flex: 1; min-height: 0; }

    /* ── SIDEBAR ── */
    .aws-sidebar {
        width: 220px;
        background: var(--aws-nav2);
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        min-height: calc(100vh - 44px);
    }
    .aws-nav-section {
        font-size: 11px;
        font-weight: 700;
        color: rgba(255,255,255,0.45);
        text-transform: uppercase;
        letter-spacing: .07em;
        padding: 16px 16px 6px;
    }
    .aws-nav-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 16px;
        font-size: 13px;
        color: rgba(255,255,255,0.75);
        text-decoration: none;
        border-left: 3px solid transparent;
        transition: all .12s;
        cursor: pointer;
    }
    .aws-nav-item:hover { background: rgba(255,255,255,0.08); color: #fff; border-left-color: rgba(236,114,17,.5); }
    .aws-nav-item.active { background: rgba(236,114,17,.12); color: #fff; border-left-color: var(--aws-orange); }
    .aws-nav-item svg { width: 16px; height: 16px; flex-shrink: 0; }
    .aws-sidebar-footer { margin-top: auto; border-top: 1px solid rgba(255,255,255,.1); padding: 8px 0; }

    /* ── MAIN ── */
    .aws-main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
    .aws-content { flex: 1; padding: 20px 24px; }
    .aws-footer { text-align: center; font-size: 11px; color: var(--aws-sub); padding: 10px; border-top: 1px solid var(--aws-border); background: var(--aws-white); }

    /* ── PAGE HEADER ── */
    .aws-page-header { margin-bottom: 16px; }
    .aws-crumb { font-size: 12px; color: var(--aws-sub); margin-bottom: 4px; }
    .aws-crumb a { color: var(--aws-blue); text-decoration: none; }
    .aws-crumb a:hover { text-decoration: underline; }
    .aws-page-title { font-size: 20px; font-weight: 700; color: var(--aws-header); }

    /* ── PANELS ── */
    .aws-panel { background: var(--aws-white); border: 1px solid var(--aws-border); border-radius: 4px; margin-bottom: 16px; }
    .aws-panel-header { background: #fafafa; border-bottom: 1px solid var(--aws-border); padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; border-radius: 4px 4px 0 0; }
    .aws-panel-title { font-size: 14px; font-weight: 700; color: var(--aws-header); }
    .aws-panel-body { padding: 20px; }

    /* ── BUTTONS ── */
    .aws-btn { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; padding: 7px 16px; border-radius: 4px; cursor: pointer; border: 1px solid; text-decoration: none; transition: all .15s; }
    .aws-btn-primary { background: var(--aws-orange); border-color: var(--aws-orange2); color: #fff; }
    .aws-btn-primary:hover { background: var(--aws-orange2); }
    .aws-btn-normal { background: #fff; border-color: #aab7b8; color: var(--aws-header); }
    .aws-btn-normal:hover { background: var(--aws-bg); }
    .aws-btn-danger { background: #fff; border-color: var(--aws-red); color: var(--aws-red); }
    .aws-btn-danger:hover { background: #fdf3f1; }
    /* ✅ FIX : aucun style :disabled n'existait — un bouton désactivé (ex:
       solde insuffisant sur le formulaire de retrait) avait exactement la
       même apparence qu'un bouton actif, donnant l'impression que le clic
       ne faisait "aucun effet" alors qu'il était en fait bloqué. */
    .aws-btn:disabled, .aws-btn[disabled] { background: #e9ebed !important; border-color: #d5dbdb !important; color: #8b979c !important; cursor: not-allowed; opacity: .8; }
    .aws-btn:disabled:hover, .aws-btn[disabled]:hover { background: #e9ebed !important; }

    /* ── BADGES ── */
    .aws-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600; padding: 2px 8px; border-radius: 3px; }
    .aws-badge::before { content: '●'; font-size: 8px; }
    .aws-badge-green  { background: #ebf8ee; color: var(--aws-green);  border: 1px solid #b2dfba; }
    .aws-badge-yellow { background: #fef9f0; color: var(--aws-yellow); border: 1px solid #f5d798; }
    .aws-badge-red    { background: #fdf3f1; color: var(--aws-red);    border: 1px solid #f5b6a7; }
    .aws-badge-blue   { background: #e8f4fb; color: #0073bb;           border: 1px solid #a9cfe2; }
    .aws-badge-gray   { background: #f2f3f3; color: var(--aws-sub);    border: 1px solid #aab7b8; }

    /* ── TABLES ── */
    .aws-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .aws-table thead tr { background: #fafafa; border-bottom: 2px solid var(--aws-border); }
    .aws-table th { padding: 10px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--aws-sub); text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }
    .aws-table td { padding: 12px 16px; border-bottom: 1px solid #f2f3f3; color: var(--aws-header); vertical-align: middle; }
    .aws-table tbody tr:hover { background: #fafafa; }
    .aws-table tbody tr:last-child td { border-bottom: none; }

    /* ── FORMS ── */
    .aws-field { margin-bottom: 18px; }
    .aws-label { display: block; font-size: 13px; font-weight: 600; color: var(--aws-header); margin-bottom: 5px; }
    .aws-label-opt { font-size: 12px; font-weight: 400; color: var(--aws-sub); margin-left: 4px; }
    .aws-input { width: 100%; border: 1px solid var(--aws-border); border-radius: 4px; padding: 8px 12px; font-size: 14px; color: var(--aws-header); box-sizing: border-box; outline: none; background: #fff; transition: border-color .15s, box-shadow .15s; }
    .aws-input:focus { border-color: var(--aws-orange); box-shadow: 0 0 0 2px rgba(236,114,17,.15); }
    .aws-input::placeholder { color: #aab7b8; }
    .aws-hint { font-size: 12px; color: var(--aws-sub); margin: 4px 0 0; }
    .aws-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    .aws-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; }

    /* ── ALERTS ── */
    .aws-alert { padding: 10px 16px; border-radius: 4px; font-size: 13px; margin-bottom: 16px; display: flex; align-items: flex-start; gap: 8px; }
    .aws-alert-success { background: #ebf8ee; border: 1px solid #b2dfba; color: var(--aws-green); }
    .aws-alert-error   { background: #fdf3f1; border: 1px solid #f5b6a7; color: var(--aws-red); }

    /* ── STAT CARDS ── */
    .aws-stat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-bottom: 16px; }
    .aws-stat-card { background: var(--aws-white); border: 1px solid var(--aws-border); border-radius: 4px; padding: 16px 20px; transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease; cursor: default; }
    .aws-stat-card:hover { transform: translateY(-4px); box-shadow: 0 10px 24px rgba(0,0,0,.10); border-color: #cbd5e1; }
    .aws-stat-card-link { display: block; text-decoration: none; color: inherit; cursor: pointer; }
    .aws-stat-card-link:hover { text-decoration: none; color: inherit; }
    .aws-stat-label { font-size: 12px; font-weight: 700; color: var(--aws-sub); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 8px; }
    .aws-stat-value { font-size: 28px; font-weight: 700; color: var(--aws-header); }
    .aws-stat-sub { font-size: 12px; color: var(--aws-sub); margin-top: 4px; }

    /* ── DETAIL GRID ── */
    .aws-detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 0; }
    .aws-detail-item { padding: 12px 0; border-bottom: 1px solid #f2f3f3; padding-right: 20px; }
    .aws-detail-label { font-size: 11px; font-weight: 700; color: var(--aws-sub); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; }
    .aws-detail-value { font-size: 14px; color: var(--aws-header); font-weight: 500; }

    /* ── RESPONSIVE ── */
    @media (max-width: 768px) {
        .aws-sidebar { width: 180px; }
        .aws-grid-2, .aws-grid-3 { grid-template-columns: 1fr; }
    }
    </style>
</head>
<body>

    <!-- TOP BAR -->
    <div class="aws-topbar">
        <a href="{{ route('company.dashboard') }}" class="aws-topbar-logo">
            <span class="t">TopTop</span><span class="g">Go</span>
        </a>
        <div class="aws-topbar-divider"></div>
        <span class="aws-topbar-company">{{ auth('company')->user()->name }}</span>
        @if(\App\Support\CompanyContext::isAgent())
        <span class="aws-badge aws-badge-blue" style="background:rgba(255,255,255,.12);color:#fff;border-color:transparent">
            {{ \App\Support\CompanyContext::agent()->name }} — {{ \App\Support\CompanyContext::agent()->role_label }}
        </span>
        @endif
        <div class="aws-topbar-right">
            <button type="button" onclick="TTCall.callSupport()"
                    style="background:rgba(29,140,73,.18);border:1px solid rgba(29,140,73,.4);color:#3ddc84;border-radius:4px;padding:6px 12px;font-size:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px">
                📞 Appeler le support
            </button>
            <span class="aws-topbar-email" style="color:rgba(255,255,255,.6)">{{ auth('company')->user()->email }}</span>
            <div class="aws-topbar-avatar">
                @if(auth('company')->user()->logo_url)
                    <img src="{{ auth('company')->user()->logo_url }}" alt="">
                @else
                    {{ strtoupper(substr(auth('company')->user()->name, 0, 1)) }}
                @endif
            </div>
        </div>
    </div>

    <!-- BODY -->
    <div class="aws-body">

        <!-- SIDEBAR -->
        <nav class="aws-sidebar">

            <div class="aws-nav-section" style="padding-top:12px">Services</div>

            <a href="{{ route('company.dashboard') }}"
               class="aws-nav-item {{ request()->routeIs('company.dashboard') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

            @php $ctx = \App\Support\CompanyContext::class; @endphp

            @if($ctx::can('drivers') || $ctx::can('vehicles') || $ctx::can('schedule') || $ctx::can('pricing_grids') || $ctx::can('reservations') || $ctx::can('itineraries') || $ctx::can('messages'))
            <div class="aws-nav-section">Gestion</div>
            @endif

            @if($ctx::can('drivers'))
            <a href="{{ route('company.drivers.index') }}"
               class="aws-nav-item {{ request()->routeIs('company.drivers.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Chauffeurs
            </a>
            @endif

            @if($ctx::can('vehicles'))
            <a href="{{ route('company.vehicles.index') }}"
               class="aws-nav-item {{ request()->routeIs('company.vehicles.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 2h1m6 0h4m0 0l2-2V8a1 1 0 00-1-1h-4a1 1 0 00-1 1v8h1z"/>
                </svg>
                Flotte Véhicules
            </a>
            @endif

            @if($ctx::can('schedule'))
            <a href="{{ route('company.schedule.index') }}"
               class="aws-nav-item {{ request()->routeIs('company.schedule.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Planning chauffeurs
            </a>
            @endif

            @if($ctx::can('pricing_grids'))
            <a href="{{ route('company.pricing-grids.index') }}"
               class="aws-nav-item {{ request()->routeIs('company.pricing-grids.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m-6 4h6m-6 4h4M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                </svg>
                Grilles tarifaires
            </a>
            @endif

            @if($ctx::can('reservations'))
            <a href="{{ route('company.reservations.index') }}"
               class="aws-nav-item {{ request()->routeIs('company.reservations.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Réservations
            </a>
            @endif

            @if($ctx::can('itineraries'))
            <a href="{{ route('company.itineraries.index') }}"
               class="aws-nav-item {{ request()->routeIs('company.itineraries.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
                Itinéraires
            </a>
            @endif

            @if($ctx::can('messages'))
            <a href="{{ route('company.messages.index') }}"
               class="aws-nav-item {{ request()->routeIs('company.messages.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
                Messages
            </a>
            <a href="{{ route('company.calls.index') }}"
               class="aws-nav-item {{ request()->routeIs('company.calls.index') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                Journal des appels
            </a>
            <a href="{{ route('company.support.index') }}"
               class="aws-nav-item {{ request()->routeIs('company.support.index') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m-3.536-3.536a4 4 0 010-5.656M9 12a1 1 0 11-2 0 1 1 0 012 0zm4 0a1 1 0 11-2 0 1 1 0 012 0z"/>
                </svg>
                Support TopTopGo
            </a>
            @endif

            @if($ctx::can('withdrawals') || $ctx::can('revenus'))
            <div class="aws-nav-section">Finances</div>
            @endif

            @if($ctx::can('withdrawals'))
            <a href="{{ route('company.withdrawals.index') }}"
               class="aws-nav-item {{ request()->routeIs('company.withdrawals.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m8-8V5a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m8-2h-2a2 2 0 00-2 2v2m4-4a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-2"/>
                </svg>
                Retraits
            </a>
            @endif

            @if($ctx::can('revenus'))
            <a href="{{ route('company.revenus.index') }}"
               class="aws-nav-item {{ request()->routeIs('company.revenus.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Revenus
            </a>
            @endif

            @if($ctx::can('agents'))
            <div class="aws-nav-section">Équipe</div>
            <a href="{{ route('company.agents.index') }}"
               class="aws-nav-item {{ request()->routeIs('company.agents.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M9 20H4v-2a3 3 0 015.356-1.857M9 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M13 7a3 3 0 11-6 0 3 3 0 016 0zM21 10a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Agents
            </a>
            @endif

            <div class="aws-sidebar-footer">
                <form method="POST" action="{{ route('company.logout') }}">
                    @csrf
                    <button type="submit" class="aws-nav-item" style="width:100%;background:none;border:none;text-align:left">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Déconnexion
                    </button>
                </form>
            </div>

        </nav>

        <!-- MAIN -->
        <div class="aws-main">
            <div class="aws-content">

                @if(session('success'))
                <div class="aws-alert aws-alert-success">✓ {{ session(