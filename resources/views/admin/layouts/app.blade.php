<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Admin') — TopTopGo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('images/logo3.ico') }}">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { brand:'#1DA1F2','brand-d':'#0b8fd6', accent:'#FFC107' } } } }</script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>

    <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

    *, *::before, *::after { box-sizing: border-box; }
    :root {
        --brand: #1DA1F2;
        --brand-d: #0b8fd6;
        --accent: #FFC107;
        --bg: #F0F4F8;
        --surface: #FFFFFF;
        --border: #E2E8F0;
        --border-strong: #CBD5E1;
        --text-1: #0F172A;
        --text-2: #475569;
        --text-3: #94A3B8;
        --success: #10B981;
        --warning: #F59E0B;
        --danger: #EF4444;
        --sidebar-bg: #0D1117;
        --sidebar-hover: rgba(255,255,255,.07);
        --sidebar-active: rgba(29,161,242,.15);
        --sidebar-text: #8B9DC3;
        --sidebar-text-active: #FFFFFF;
        --radius: 10px;
        --radius-lg: 14px;
        --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
        --shadow: 0 4px 6px -1px rgba(0,0,0,.07), 0 2px 4px -1px rgba(0,0,0,.04);
    }

    html { -webkit-font-smoothing: antialiased; }
    body { font-family: 'Inter', system-ui, sans-serif; background: var(--bg); color: var(--text-1); margin: 0; font-size: 14px; }

    /* ─── SIDEBAR ─── */
    #sidebar { width: 240px; background: var(--sidebar-bg); flex-shrink: 0; display: flex; flex-direction: column; height: 100vh; position: sticky; top: 0; overflow: hidden; transition: width .3s ease; }
    #sidebar .logo-area { padding: 20px 16px 18px; border-bottom: 1px solid rgba(255,255,255,.06); display: flex; align-items: center; gap: 10px; }
    #sidebar .logo-area img { height: 32px; object-fit: contain; }
    #sidebar nav { flex: 1; overflow-y: auto; padding: 12px 10px; }
    #sidebar nav::-webkit-scrollbar { width: 0; }

    .nav-section { font-size: 10px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: rgba(255,255,255,.2); padding: 16px 8px 6px; }
    .nav-item { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 7px; font-size: 13px; font-weight: 500; color: var(--sidebar-text); text-decoration: none; transition: all .15s; white-space: nowrap; overflow: hidden; cursor: pointer; }
    .nav-item svg { flex-shrink: 0; opacity: .6; transition: opacity .15s; }
    .nav-item .nav-label { flex: 1; }
    .nav-item .nav-badge { background: var(--danger); color: #fff; font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 99px; flex-shrink: 0; }
    .nav-item:hover { background: var(--sidebar-hover); color: #fff; }
    .nav-item:hover svg { opacity: 1; }
    .nav-item.active { background: var(--sidebar-active); color: var(--brand); border-left: 2px solid var(--brand); padding-left: 8px; }
    .nav-item.active svg { opacity: 1; color: var(--brand); }

    #sidebar .sidebar-footer { padding: 14px 10px; border-top: 1px solid rgba(255,255,255,.06); }
    .user-card { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 7px; margin-bottom: 8px; }
    .user-avatar { width: 34px; height: 34px; border-radius: 8px; background: linear-gradient(135deg, var(--brand), #0b6abf); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; color: #fff; flex-shrink: 0; }
    .user-name { font-size: 13px; font-weight: 600; color: #e2e8f0; line-height: 1.2; }
    .user-role { font-size: 11px; color: var(--sidebar-text); }

    /* ─── TOPBAR ─── */
    .topbar { height: 60px; background: var(--surface); border-bottom: 1px solid var(--border); display: flex; align-items: center; padding: 0 24px; gap: 16px; position: sticky; top: 0; z-index: 100; }
    .topbar-title { flex: 1; font-size: 15px; font-weight: 600; color: var(--text-1); }
    .topbar-right { display: flex; align-items: center; gap: 12px; }

    /* ─── PAGE ─── */
    .page-header { margin-bottom: 24px; }
    .page-title { font-size: 20px; font-weight: 700; color: var(--text-1); letter-spacing: -.01em; }
    .page-sub { font-size: 13px; color: var(--text-3); margin-top: 2px; }

    /* ─── CARDS ─── */
    .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); }
    .card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
    .card-title { font-size: 13px; font-weight: 600; color: var(--text-1); }
    .card-body { padding: 20px; }

    /* ─── STAT CARDS ─── */
    .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 20px; box-shadow: var(--shadow-sm); position: relative; overflow: hidden; }
    .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 14px; }
    .stat-val { font-size: 26px; font-weight: 800; color: var(--text-1); line-height: 1; letter-spacing: -.02em; }
    .stat-lbl { font-size: 12px; font-weight: 500; color: var(--text-3); margin-top: 5px; text-transform: uppercase; letter-spacing: .04em; }
    .stat-sub { font-size: 12px; color: var(--text-3); margin-top: 4px; }

    /* Compat ancien style */
    .stat-card .val { font-size: 26px; font-weight: 800; color: var(--text-1); line-height: 1; letter-spacing: -.02em; }
    .stat-card .lbl { font-size: 11px; font-weight: 600; color: var(--text-3); margin-bottom: 8px; text-transform: uppercase; letter-spacing: .05em; }
    .stat-card .sub { font-size: 12px; color: var(--text-3); margin-top: 4px; }

    /* ─── PANEL (alias card) ─── */
    .panel { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); }
    .panel-header { padding: 14px 20px; border-bottom: 1px solid var(--border); font-size: 13px; font-weight: 600; color: var(--text-1); display: flex; align-items: center; justify-content: space-between; }

    /* ─── TABLE ─── */
    .ttg-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .ttg-table thead th { padding: 10px 16px; font-size: 11px; font-weight: 600; color: var(--text-3); text-transform: uppercase; letter-spacing: .06em; text-align: left; background: #FAFBFC; border-bottom: 1px solid var(--border); }
    .ttg-table tbody tr { border-bottom: 1px solid #F7F8FA; transition: background .12s; }
    .ttg-table tbody tr:last-child { border-bottom: none; }
    .ttg-table tbody tr:hover { background: #F8FAFF; }
    .ttg-table tbody td { padding: 12px 16px; color: var(--text-2); vertical-align: middle; }

    /* ─── BADGES ─── */
    .badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 5px; font-size: 11px; font-weight: 600; letter-spacing: .01em; }
    .badge-success, .badge-green   { background: #ECFDF5; color: #065F46; }
    .badge-danger,  .badge-red     { background: #FEF2F2; color: #991B1B; }
    .badge-warning, .badge-yellow  { background: #FFFBEB; color: #92400E; }
    .badge-info,    .badge-blue    { background: #EFF6FF; color: #1E40AF; }
    .badge-gray, .badge-secondary  { background: #F1F5F9; color: #475569; }
    .badge-primary                 { background: #EFF8FF; color: #0369A1; }
    .badge-indigo                  { background: #EEF2FF; color: #3730A3; }
    .badge-orange                  { background: #FFF7ED; color: #9A3412; }

    /* ─── BUTTONS ─── */
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 16px; border-radius: 7px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all .15s; border: 1px solid transparent; text-decoration: none; white-space: nowrap; }
    .btn-primary   { background: var(--brand); color: #fff; border-color: var(--brand); }
    .btn-primary:hover { background: var(--brand-d); border-color: var(--brand-d); }
    .btn-success   { background: var(--success); color: #fff; }
    .btn-success:hover { background: #059669; }
    .btn-danger    { background: var(--danger); color: #fff; }
    .btn-danger:hover  { background: #DC2626; }
    .btn-secondary, .btn-gray { background: var(--surface); color: var(--text-2); border-color: var(--border); }
    .btn-secondary:hover, .btn-gray:hover { background: #F8FAFC; border-color: var(--border-strong); }
    .btn-ghost     { background: transparent; color: var(--text-2); border-color: transparent; }
    .btn-ghost:hover { background: #F1F5F9; }
    .btn-sm        { padding: 5px 12px; font-size: 12px; border-radius: 6px; }

    /* ─── INPUTS ─── */
    .ttg-input  { border: 1px solid var(--border); border-radius: 7px; padding: 8px 12px; font-size: 13px; font-family: inherit; outline: none; background: var(--surface); color: var(--text-1); width: 100%; transition: border-color .15s, box-shadow .15s; }
    .ttg-input:focus  { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(29,161,242,.1); }
    .ttg-select { border: 1px solid var(--border); border-radius: 7px; padding: 8px 12px; font-size: 13px; font-family: inherit; outline: none; background: var(--surface); color: var(--text-1); width: 100%; transition: border-color .15s; }
    .ttg-select:focus { border-color: var(--brand); }
    .ttg-label  { display: block; font-size: 12px; font-weight: 600; color: var(--text-2); margin-bottom: 5px; }

    /* ─── FILTER BAR ─── */
    .filter-bar { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 14px 18px; display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; box-shadow: var(--shadow-sm); }

    /* ─── AVATAR ─── */
    .avatar { width: 34px; height: 34px; border-radius: 8px; background: #EFF6FF; color: var(--brand); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0; }

    /* ─── TOAST ─── */
    #toast-container { position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 8px; pointer-events: none; }
    .toast { padding: 12px 18px; border-radius: 10px; font-size: 13px; font-weight: 500; box-shadow: 0 10px 30px rgba(0,0,0,.15); transform: translateX(110%); opacity: 0; transition: all .3s cubic-bezier(.16,1,.3,1); max-width: 320px; display: flex; align-items: center; gap: 10px; border: 1px solid; }
    .toast.show { transform: translateX(0); opacity: 1; }
    .toast-success { background: #F0FDF4; color: #15803D; border-color: #BBF7D0; }
    .toast-error   { background: #FEF2F2; color: #B91C1C; border-color: #FECACA; }
    .toast-icon    { width: 18px; height: 18px; flex-shrink: 0; }

    /* ─── SCROLLBAR ─── */
    ::-webkit-scrollbar { width: 4px; height: 4px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--border-strong); border-radius: 99px; }

    /* ─── MOBILE ─── */
    @media (max-width: 767px) {
        #sidebar { position: fixed; top: 0; left: 0; height: 100%; z-index: 200; transform: translateX(-100%); transition: transform .25s ease; }
        #sidebar.open { transform: translateX(0); }
        #sidebar-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 199; display: none; }
        #sidebar-overlay.show { display: block; }
        .topbar { padding: 0 16px; }
        main { padding: 16px !important; }
    }

    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }
    </style>
</head>

<body>
<div style="display:flex;min-height:100vh">

    <!-- ══════════ SIDEBAR ══════════ -->
    <aside id="sidebar">

        <div class="logo-area">
            <img src="{{ asset('images/logo4.png') }}" alt="TopTopGo">
        </div>

        <nav>
            {{-- Dashboard --}}
            <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                <span class="nav-label">Dashboard</span>
            </a>

            <div class="nav-section">Messagerie</div>

            <a href="{{ route('admin.messages.index') }}" class="nav-item {{ request()->routeIs('admin.messages.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span class="nav-label">Users ↔ Chauffeurs</span>
            </a>

            <a href="{{ route('admin.support.drivers.index') }}" class="nav-item {{ request()->routeIs('admin.support.drivers.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span class="nav-label">Admin ↔ Chauffeurs</span>
            </a>

            <a href="{{ route('admin.support.users.index') }}" class="nav-item {{ request()->routeIs('admin.support.users.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                <span class="nav-label">Admin ↔ Utilisateurs</span>
            </a>

            @php
                try { $activeSos = \App\Models\SosAlert::where('status','active')->count(); }
                catch(\Exception $e) { $activeSos = 0; }
            @endphp
            <a href="{{ route('admin.sos.index') }}" class="nav-item {{ request()->routeIs('admin.sos.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span class="nav-label">Alertes SOS</span>
                @if($activeSos > 0)
                    <span class="nav-badge" style="animation:pulse 1.5s infinite">{{ $activeSos }}</span>
                @endif
            </a>

            <div class="nav-section">Gestion</div>

            @php
                try { $pendingKyc = \App\Models\Driver\Driver::where('status','pending')->count(); }
                catch(\Exception $e) { $pendingKyc = 0; }
            @endphp
            <a href="{{ route('admin.drivers.index') }}" class="nav-item {{ request()->routeIs('admin.drivers.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 12l2 2 4-4"/></svg>
                <span class="nav-label">Chauffeurs</span>
                @if($pendingKyc > 0)
                    <span class="nav-badge">{{ $pendingKyc }}</span>
                @endif
            </a>

            <a href="{{ route('admin.users.index') }}" class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span class="nav-label">Clients</span>
            </a>

            <a href="{{ route('admin.profiles.index') }}" class="nav-item {{ request()->routeIs('admin.profiles.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span class="nav-label">Administrateurs</span>
            </a>

            <a href="{{ route('admin.kyc', ['status' => 'pending']) }}" class="nav-item {{ request()->routeIs('admin.kyc*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                <span class="nav-label">Vérification KYC</span>
            </a>

            <div class="nav-section">Finances</div>

            <a href="{{ route('admin.revenus.index') }}" class="nav-item {{ request()->routeIs('admin.revenus.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                <span class="nav-label">Revenus</span>
            </a>

            <a href="{{ route('admin.commission-rates.index') }}" class="nav-item {{ request()->routeIs('admin.commission-rates.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                <span class="nav-label">Commissions</span>
            </a>

            <a href="{{ route('admin.payments.index') }}" class="nav-item {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                <span class="nav-label">Paiements</span>
            </a>

            <div class="nav-section">Trajets</div>

            <a href="{{ route('admin.trips.index') }}" class="nav-item {{ request()->routeIs('admin.trips.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="10" r="3"/><path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 1 0-16 0c0 3 2.7 6.9 8 11.7z"/></svg>
                <span class="nav-label">Trajets & Courses</span>
            </a>

            <a href="{{ route('admin.geolocation.index') }}" class="nav-item {{ request()->routeIs('admin.geolocation.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
                <span class="nav-label">Géolocalisation</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-card">
                <div class="user-avatar">{{ strtoupper(substr(session('admin_name','A'),0,1)) }}</div>
                <div>
                    <div class="user-name">{{ session('admin_name','Admin') }}</div>
                    <div class="user-role">Super Admin</div>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="btn btn-ghost" style="width:100%;color:var(--sidebar-text);font-size:12px;padding:7px 10px;justify-content:flex-start;gap:8px;border-radius:7px">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Se déconnecter
                </button>
            </form>
        </div>
    </aside>

    <!-- ══════════ MAIN ══════════ -->
    <div style="flex:1;display:flex;flex-direction:column;min-width:0;overflow:hidden">

        <!-- TOPBAR -->
        <header class="topbar">
            <button id="sidebar-toggle" onclick="toggleSidebar()" style="display:none;background:none;border:none;cursor:pointer;padding:6px;border-radius:6px;color:var(--text-2)">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>

            <div class="topbar-title">@yield('title', 'Dashboard')</div>

            <div class="topbar-right">
                @if($activeSos > 0)
                    <a href="{{ route('admin.sos.index') }}" style="display:flex;align-items:center;gap:6px;background:#FEF2F2;color:#B91C1C;border:1px solid #FECACA;padding:5px 12px;border-radius:7px;font-size:12px;font-weight:600;text-decoration:none">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        {{ $activeSos }} SOS
                    </a>
                @endif
                <div style="display:flex;align-items:center;gap:8px">
                    <div style="width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,var(--brand),#0b6abf);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;color:#fff">
                        {{ strtoupper(substr(session('admin_name','A'),0,1)) }}
                    </div>
                    <span style="font-size:13px;font-weight:500;color:var(--text-1)">{{ session('admin_name','Admin') }}</span>
                </div>
            </div>
        </header>

        <!-- CONTENT -->
        <main style="flex:1;padding:24px;overflow-y:auto">
            <div id="toast-container"></div>

            @if(session('success'))
                <script>document.addEventListener("DOMContentLoaded",()=>showToast("{{ addslashes(session('success')) }}","success"));</script>
            @endif
            @if(session('error'))
                <script>document.addEventListener("DOMContentLoaded",()=>showToast("{{ addslashes(session('error')) }}","error"));</script>
            @endif

            @yield('content')
        </main>

        <footer style="background:var(--surface);border-top:1px solid var(--border);padding:12px 24px;font-size:12px;color:var(--text-3);display:flex;justify-content:space-between;align-items:center">
            <span>© {{ date('Y') }} TopTopGo — Tous droits réservés</span>
            <span>Développé par <strong style="color:var(--text-2);font-weight:600">Basile NGASSAKI</strong></span>
        </footer>
    </div>
</div>

<!-- Mobile overlay -->
<div id="sidebar-overlay" onclick="toggleSidebar()"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebar-overlay');
const toggleBtn = document.getElementById('sidebar-toggle');

function isMobile() { return window.innerWidth < 768; }

function applyLayout() {
    if (isMobile()) {
        toggleBtn.style.display = 'block';
    } else {
        toggleBtn.style.display = 'none';
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    }
}

function toggleSidebar() {
    const open = sidebar.classList.toggle('open');
    overlay.classList.toggle('show', open);
}

window.addEventListener('resize', applyLayout);
applyLayout();

function showToast(msg, type='success') {
    const t = document.createElement('div');
    const isOk = type === 'success';
    t.className = 'toast ' + (isOk ? 'toast-success' : 'toast-error');
    t.innerHTML = `
        <svg class="toast-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            ${isOk
                ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'
                : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'
            }
        </svg>
        <span>${msg}</span>`;
    document.getElementById('toast-container').appendChild(t);
    requestAnimationFrame(() => requestAnimationFrame(() => t.classList.add('show')));
    setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 350); }, 4000);
}

function previewImage(e, id) {
    const r = new FileReader();
    r.onload = () => { const i = document.getElementById(id); i.src = r.result; i.classList.remove('hidden'); };
    r.readAsDataURL(e.target.files[0]);
}
</script>
@stack('scripts')
</body>
</html>
