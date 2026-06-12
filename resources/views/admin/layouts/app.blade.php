<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Admin') — TopTopGo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('images/logo3.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{brand:'#1DA1F2','brand-d':'#0b8fd6',accent:'#FFC107'}}}}</script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    *,*::before,*::after{box-sizing:border-box}
    :root{
        --brand:#1DA1F2;--brand-d:#0b8fd6;--accent:#FFC107;
        --bg:#F2F3F3;--surface:#FFFFFF;
        --border:#D5DBDB;--border-2:#AAB7B8;
        --text-1:#16191F;--text-2:#545B64;--text-3:#879596;
        --success:#067D49;--warning:#B45309;--danger:#D13212;--info:#0972D3;
        --nav-h:56px;--sidebar-w:220px;--radius:4px;
        --shadow:0 1px 4px rgba(0,0,0,.12),0 0 0 1px rgba(0,0,0,.06);
        --shadow-md:0 2px 8px rgba(0,0,0,.15);
    }
    html{-webkit-font-smoothing:antialiased}
    body{font-family:'Inter','Amazon Ember',Arial,sans-serif;background:var(--bg);color:var(--text-1);margin:0;font-size:13px;line-height:1.5}

    /* TOP NAV */
    #topnav{height:var(--nav-h);background:#0F1923;display:flex;align-items:center;padding:0 20px;gap:12px;position:sticky;top:0;z-index:300;border-bottom:1px solid rgba(255,255,255,.08)}
    .tn-logo{display:flex;align-items:center;gap:10px;padding-right:18px;border-right:1px solid rgba(255,255,255,.12);flex-shrink:0}
    .tn-logo img{height:28px}
    .tn-logo span{font-size:14px;font-weight:700;color:#fff;letter-spacing:-.01em}
    .tn-search{flex:1;max-width:360px;position:relative}
    .tn-search input{width:100%;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:var(--radius);padding:6px 12px 6px 34px;font-size:13px;color:#fff;outline:none;font-family:inherit}
    .tn-search input::placeholder{color:rgba(255,255,255,.4)}
    .tn-right{margin-left:auto;display:flex;align-items:center;gap:4px}
    .tn-btn{display:flex;align-items:center;gap:6px;padding:6px 12px;border-radius:var(--radius);font-size:13px;color:rgba(255,255,255,.85);background:none;border:none;cursor:pointer;font-family:inherit;transition:background .15s;text-decoration:none}
    .tn-btn:hover{background:rgba(255,255,255,.1);color:#fff}
    .tn-sos{background:rgba(209,50,18,.2);border:1px solid rgba(209,50,18,.4);color:#FF6B6B;border-radius:var(--radius);padding:5px 12px;font-size:12px;font-weight:700;display:flex;align-items:center;gap:6px;cursor:pointer;text-decoration:none}
    .tn-sos:hover{background:rgba(209,50,18,.35)}
    .tn-divider{width:1px;height:24px;background:rgba(255,255,255,.12);margin:0 4px}

    /* LAYOUT */
    #layout{display:flex;min-height:calc(100vh - var(--nav-h))}

    /* SIDEBAR */
    #sidebar{width:var(--sidebar-w);background:var(--surface);border-right:1px solid var(--border);flex-shrink:0;overflow-y:auto;display:flex;flex-direction:column;transition:width .22s ease,transform .22s ease}
    #sidebar::-webkit-scrollbar{width:0}
    #sidebar.collapsed{width:0;overflow:hidden;border-right:none}
    .nav-section{font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text-3);padding:16px 16px 6px}
    .nav-item{display:flex;align-items:center;gap:9px;padding:7px 16px;font-size:13px;font-weight:500;color:var(--text-2);text-decoration:none;transition:all .1s;white-space:nowrap;overflow:hidden;border-left:3px solid transparent}
    .nav-item svg{flex-shrink:0;color:var(--text-3)}
    .nav-item .nav-label{flex:1}
    .nav-item .nav-badge{background:var(--danger);color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:10px;flex-shrink:0}
    .nav-item:hover{background:#EBF5FB;color:var(--text-1);border-left-color:var(--brand)}
    .nav-item:hover svg{color:var(--brand)}
    .nav-item.active{background:#EBF5FB;color:var(--brand);border-left-color:var(--brand);font-weight:600}
    .nav-item.active svg{color:var(--brand)}
    .sb-footer{padding:14px 16px;border-top:1px solid var(--border);display:flex;align-items:center;gap:10px;margin-top:auto}
    /* Toggle button desktop */
    #sidebar-toggle{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:var(--radius);background:none;border:1px solid rgba(255,255,255,.18);cursor:pointer;color:rgba(255,255,255,.8);flex-shrink:0;transition:background .15s}
    #sidebar-toggle:hover{background:rgba(255,255,255,.12);color:#fff}
    .sb-av{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--brand),var(--brand-d));display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0}

    /* CONTENT */
    #content{flex:1;min-width:0;display:flex;flex-direction:column}
    #breadcrumb{background:var(--surface);border-bottom:1px solid var(--border);padding:8px 24px;display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-3)}
    #breadcrumb a{color:var(--info);text-decoration:none}
    #breadcrumb a:hover{text-decoration:underline}
    main{flex:1;padding:20px 24px;overflow-y:auto}
    #pg-footer{padding:12px 24px;border-top:1px solid var(--border);background:var(--surface);font-size:11px;color:var(--text-3);display:flex;justify-content:space-between}

    /* PAGE HEADER */
    .page-header{margin-bottom:20px}
    .page-title{font-size:22px;font-weight:700;color:var(--text-1);letter-spacing:-.02em;margin:0 0 4px}
    .page-sub{font-size:13px;color:var(--text-3);margin:0}

    /* PANELS */
    .card,.panel{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow)}
    .card-header,.panel-header{padding:12px 20px;border-bottom:1px solid var(--border);font-size:14px;font-weight:600;color:var(--text-1);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
    .card-body{padding:20px}

    /* STAT CARDS */
    .stat-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:18px 20px 14px;box-shadow:0 2px 8px rgba(0,0,0,.06),0 0 0 1px rgba(0,0,0,.04);transition:box-shadow .18s,transform .18s}
    .stat-card:hover{box-shadow:0 6px 24px rgba(0,0,0,.11),0 0 0 1px rgba(0,0,0,.05);transform:translateY(-2px)}
    .stat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:14px}
    .stat-val,.val{font-size:30px;font-weight:800;color:var(--text-1);line-height:1;letter-spacing:-.04em}
    .stat-lbl,.lbl{font-size:11px;font-weight:700;color:var(--text-3);margin-top:6px;text-transform:uppercase;letter-spacing:.07em}
    .stat-sub,.sub{font-size:12px;color:var(--text-3);margin-top:5px}
    .stat-trend{margin-top:10px;padding-top:10px;border-top:1px solid #f1f5f9;font-size:11px}

    /* TABLE */
    .ttg-table{width:100%;border-collapse:collapse;font-size:13px}
    .ttg-table thead th{padding:8px 16px;font-size:11px;font-weight:700;color:var(--text-2);text-transform:uppercase;letter-spacing:.07em;background:#FAFAFA;border-bottom:2px solid var(--border);text-align:left}
    .ttg-table tbody tr{border-bottom:1px solid #F4F6F6;transition:background .1s}
    .ttg-table tbody tr:last-child{border-bottom:none}
    .ttg-table tbody tr:hover{background:#EBF5FB}
    .ttg-table tbody td{padding:10px 16px;color:var(--text-2);vertical-align:middle}

    /* BADGES */
    .badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:2px;font-size:11px;font-weight:700;letter-spacing:.02em;border:1px solid}
    .badge::before{content:'';width:6px;height:6px;border-radius:50%;margin-right:5px;background:currentColor;opacity:.8}
    .badge-success,.badge-green{background:#F2FCF6;color:#067D49;border-color:#ABE4BE}
    .badge-danger,.badge-red{background:#FDF3F0;color:#D13212;border-color:#F5BFB0}
    .badge-warning,.badge-yellow{background:#FFFAEF;color:#B45309;border-color:#FAECC2}
    .badge-info,.badge-blue{background:#EEF6FC;color:#0972D3;border-color:#B0CFEF}
    .badge-gray,.badge-secondary{background:#F7F8F8;color:#545B64;border-color:#D5DBDB}
    .badge-primary{background:#EBF5FB;color:#1DA1F2;border-color:#B3D7F5}
    .badge-indigo{background:#F2F0FC;color:#5A4AD1;border-color:#C5BCEE}
    .badge-orange{background:#FFF5EB;color:#C45226;border-color:#F5C99A}

    /* BUTTONS */
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:6px 16px;border-radius:var(--radius);font-size:13px;font-weight:500;cursor:pointer;transition:all .15s;border:1px solid transparent;text-decoration:none;white-space:nowrap;font-family:inherit}
    .btn-primary{background:var(--brand);color:#fff;border-color:var(--brand-d)}
    .btn-primary:hover{background:var(--brand-d)}
    .btn-success{background:#1E8449;color:#fff;border-color:#196F3D}
    .btn-success:hover{background:#196F3D}
    .btn-danger{background:var(--danger);color:#fff;border-color:#B03A2E}
    .btn-danger:hover{background:#B03A2E}
    .btn-secondary,.btn-gray{background:var(--surface);color:var(--text-2);border-color:var(--border-2)}
    .btn-secondary:hover,.btn-gray:hover{background:#F4F6F6;border-color:#879596}
    .btn-ghost{background:transparent;color:var(--text-2);border-color:transparent}
    .btn-ghost:hover{background:#F4F6F6}
    .btn-sm{padding:4px 12px;font-size:12px}
    .btn-link{background:none;border:none;color:var(--info);padding:0;font-size:13px;cursor:pointer}
    .btn-link:hover{text-decoration:underline}

    /* INPUTS */
    .ttg-input{border:1px solid var(--border-2);border-radius:var(--radius);padding:7px 12px;font-size:13px;font-family:inherit;outline:none;background:var(--surface);color:var(--text-1);width:100%;transition:border-color .15s,box-shadow .15s}
    .ttg-input:focus{border-color:var(--brand);box-shadow:0 0 0 3px rgba(29,161,242,.12)}
    .ttg-select{border:1px solid var(--border-2);border-radius:var(--radius);padding:7px 12px;font-size:13px;font-family:inherit;outline:none;background:var(--surface);color:var(--text-1);width:100%}
    .ttg-select:focus{border-color:var(--brand)}
    .ttg-label{display:block;font-size:12px;font-weight:600;color:var(--text-2);margin-bottom:4px}

    /* FILTER BAR */
    .filter-bar{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:12px 16px;display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;box-shadow:var(--shadow)}

    /* AVATAR */
    .avatar{width:32px;height:32px;border-radius:50%;background:#EBF5FB;color:var(--brand);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0}

    /* FLASH */
    .flash{display:flex;align-items:flex-start;gap:12px;padding:12px 16px;border-radius:var(--radius);border:1px solid;margin-bottom:16px}
    .flash-success{background:#F2FCF6;border-color:#ABE4BE;color:#0A3622}
    .flash-error{background:#FDF3F0;border-color:#F5BFB0;color:#4A0F0A}

    /* TOAST */
    #toast-container{position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;pointer-events:none}
    .toast{padding:12px 16px;border-radius:var(--radius);font-size:13px;font-weight:500;box-shadow:var(--shadow-md);transform:translateX(110%);opacity:0;transition:all .25s ease;max-width:340px;display:flex;align-items:center;gap:10px;border-left:4px solid;background:var(--surface)}
    .toast.show{transform:translateX(0);opacity:1}
    .toast-success{border-left-color:#067D49}
    .toast-error{border-left-color:var(--danger)}

    ::-webkit-scrollbar{width:5px;height:5px}
    ::-webkit-scrollbar-track{background:transparent}
    ::-webkit-scrollbar-thumb{background:var(--border-2);border-radius:99px}

    @media(max-width:767px){
        #sidebar{position:fixed;top:var(--nav-h);left:0;height:calc(100vh - var(--nav-h));z-index:200;transform:translateX(-100%);transition:transform .25s}
        #sidebar.open{transform:translateX(0)}
        #sb-overlay{position:fixed;inset:0;top:var(--nav-h);background:rgba(0,0,0,.5);z-index:199;display:none}
        #sb-overlay.show{display:block}
        main{padding:16px}
    }
    </style>
</head>
<body>

<!-- TOP NAV -->
<nav id="topnav">
    <div class="tn-logo">
        <span style="font-size:18px;font-weight:900;letter-spacing:-.5px;color:#fff"><span style="color:#1DA1F2">TopTop</span><span style="color:#ec7211">Go</span></span>
    </div>

    <button id="sidebar-toggle" onclick="toggleSidebarDesktop()" title="Réduire/Ouvrir le menu">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>

    <div class="tn-search">
        <svg width="14" height="14" fill="none" stroke="rgba(255,255,255,.5)" stroke-width="2" viewBox="0 0 24 24" style="position:absolute;left:10px;top:50%;transform:translateY(-50%)"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" placeholder="Rechercher...">
    </div>

    <div class="tn-right">
        @php
            try { $activeSos = \App\Models\SosAlert::where('status','active')->count(); }
            catch(\Exception $e) { $activeSos = 0; }
        @endphp

        @if($activeSos > 0)
            <a href="{{ route('admin.sos.index') }}" class="tn-sos">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                {{ $activeSos }} SOS actif{{ $activeSos > 1 ? 's' : '' }}
            </a>
        @endif

        <button class="tn-btn" id="menu-toggle" onclick="toggleSidebar()" style="display:none">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>

        <div class="tn-divider"></div>

        <span class="tn-btn" style="cursor:default">
            <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--brand),var(--brand-d));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff">
                {{ strtoupper(substr(session('admin_name','A'),0,1)) }}
            </div>
            {{ session('admin_name','Admin') }}
        </span>

        <form method="POST" action="{{ route('admin.logout') }}" style="margin:0">
            @csrf
            <button type="submit" class="tn-btn" title="Déconnexion">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </button>
        </form>
    </div>
</nav>

<!-- LAYOUT -->
<div id="layout">

    <!-- SIDEBAR -->
    <aside id="sidebar">
        <nav style="padding:8px 0;flex:1">

            <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                <span class="nav-label">Dashboard</span>
            </a>

            <div class="nav-section">Messagerie</div>

            <a href="{{ route('admin.messages.index') }}" class="nav-item {{ request()->routeIs('admin.messages.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span class="nav-label">Users ↔ Chauffeurs</span>
            </a>

            <a href="{{ route('admin.support.drivers.index') }}" class="nav-item {{ request()->routeIs('admin.support.drivers.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span class="nav-label">Admin ↔ Chauffeurs</span>
            </a>

            <a href="{{ route('admin.support.users.index') }}" class="nav-item {{ request()->routeIs('admin.support.users.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                <span class="nav-label">Admin ↔ Clients</span>
            </a>

            <a href="{{ route('admin.sos.index') }}" class="nav-item {{ request()->routeIs('admin.sos.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span class="nav-label">Alertes SOS</span>
                @if($activeSos > 0)
                    <span class="nav-badge">{{ $activeSos }}</span>
                @endif
            </a>

            <div class="nav-section">Gestion</div>

            @php
                try { $pendingKyc = \App\Models\Driver\Driver::where('status','pending')->count(); }
                catch(\Exception $e) { $pendingKyc = 0; }
            @endphp

            <a href="{{ route('admin.drivers.index') }}" class="nav-item {{ request()->routeIs('admin.drivers.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 12l2 2 4-4"/></svg>
                <span class="nav-label">Chauffeurs</span>
            </a>

            <a href="{{ route('admin.users.index') }}" class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                <span class="nav-label">Clients</span>
            </a>

            <a href="{{ route('admin.profiles.index') }}" class="nav-item {{ request()->routeIs('admin.profiles.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span class="nav-label">Administrateurs</span>
            </a>

            <a href="{{ route('admin.kyc.index', ['status' => 'pending']) }}" class="nav-item {{ request()->routeIs('admin.kyc.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                <span class="nav-label">Vérification KYC</span>
                @if($pendingKyc > 0)
                    <span class="nav-badge" style="background:#F59E0B">{{ $pendingKyc }}</span>
                @endif
            </a>

            <div class="nav-section">Sociétés</div>

            <a href="{{ route('admin.companies.index') }}" class="nav-item {{ request()->routeIs('admin.companies.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <span class="nav-label">Sociétés</span>
            </a>

            <div class="nav-section">Finances</div>

            <a href="{{ route('admin.revenus.index') }}" class="nav-item {{ request()->routeIs('admin.revenus.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                <span class="nav-label">Revenus</span>
            </a>

            <a href="{{ route('admin.commission-rates.index') }}" class="nav-item {{ request()->routeIs('admin.commission-rates.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                <span class="nav-label">Commissions</span>
            </a>

            <a href="{{ route('admin.payments.index') }}" class="nav-item {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                <span class="nav-label">Paiements</span>
            </a>

            <div class="nav-section">Trajets</div>

            <a href="{{ route('admin.trips.index') }}" class="nav-item {{ request()->routeIs('admin.trips.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="10" r="3"/><path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 1 0-16 0c0 3 2.7 6.9 8 11.7z"/></svg>
                <span class="nav-label">Trajets & Courses</span>
            </a>

            <a href="{{ route('admin.geolocation.index') }}" class="nav-item {{ request()->routeIs('admin.geolocation*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
                <span class="nav-label">Géolocalisation</span>
            </a>

        </nav>

        <div class="sb-footer">
            <div class="sb-av">{{ strtoupper(substr(session('admin_name','A'),0,1)) }}</div>
            <div>
                <div style="font-size:13px;font-weight:600;color:var(--text-1)">{{ session('admin_name','Admin') }}</div>
                <div style="font-size:11px;color:var(--text-3)">Super Admin</div>
            </div>
        </div>
    </aside>

    <!-- CONTENT -->
    <div id="content">
        <div id="breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Administration</a>
            <span style="opacity:.4">/</span>
            <span>@yield('title','Dashboard')</span>
        </div>

        <main>
            <div id="toast-container"></div>

            @if(session('success'))
                <div class="flash flash-success">
                    <svg width="16" height="16" fill="none" stroke="#067D49" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="flash flash-error">
                    <svg width="16" height="16" fill="none" stroke="#D13212" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @yield('content')
        </main>

        <footer id="pg-footer">
            <span>© {{ date('Y') }} TopTopGo — Administration</span>
            <span>Développé avec ❤ par <strong style="color:var(--text-2)">Basile Marius NGASSAKI ZONI</strong></span>
        </footer>
    </div>
</div>

<div id="sb-overlay" onclick="toggleSidebar()"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
function isMobile(){return window.innerWidth<768}
function applyLayout(){document.getElementById('menu-toggle').style.display=isMobile()?'flex':'none'}
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sb-overlay').classList.toggle('show')}
function toggleSidebarDesktop(){
    var sb=document.getElementById('sidebar');
    sb.classList.toggle('collapsed');
    localStorage.setItem('sidebarCollapsed', sb.classList.contains('collapsed')?'1':'0');
}
// Restore sidebar state
(function(){
    if(localStorage.getItem('sidebarCollapsed')==='1' && !isMobile()){
        document.getElementById('sidebar').classList.add('collapsed');
    }
})();
window.addEventListener('resize',applyLayout);applyLayout();

function showToast(msg,type){
    type=type||'success';
    var t=document.createElement('div');
    t.className='toast toast-'+type;
    var icon=type==='success'
        ?'<svg width="16" height="16" fill="none" stroke="#067D49" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
        :'<svg width="16" height="16" fill="none" stroke="#D13212" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
    t.innerHTML=icon+'<span>'+msg+'</span>';
    document.getElementById('toast-container').appendChild(t);
    requestAnimationFrame(function(){requestAnimationFrame(function(){t.classList.add('show')})});
    setTimeout(function(){t.classList.remove('show');setTimeout(function(){t.remove()},300)},4000);
}

function previewImage(e,id){
    var r=new FileReader();
    r.onload=function(){var i=document.getElementById(id);i.src=r.result;i.classList.remove('hidden')};
    r.readAsDataURL(e.target.files[0]);
}
</script>
@stack('scripts')
</body>
</html>