<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'TopTopGo') — Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/logo3.ico') }}" />

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#1DA1F2',
                        'brand-dark': '#0d8fd9',
                        accent: '#FFC107',
                    }
                }
            }
        }
    </script>

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>

    <style>
        * { -webkit-font-smoothing: antialiased; }
        body { background: #f1f5f9; }

        /* Sidebar */
        #sidebar { transition: transform 0.3s cubic-bezier(.4,0,.2,1); }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 14px; border-radius: 10px; font-size: 13.5px;
            font-weight: 500; color: #cbd5e1;
            transition: all 0.2s;
            text-decoration: none;
        }
        .nav-item:hover  { background: rgba(29,161,242,.18); color: #fff; padding-left: 18px; }
        .nav-item.active { background: #1DA1F2; color: #fff; }
        .nav-section {
            font-size: 10px; letter-spacing: .08em; font-weight: 700;
            color: #475569; text-transform: uppercase;
            margin: 20px 0 6px 4px;
        }

        /* Cards */
        .stat-card { background:#fff; border-radius:16px; padding:22px 24px; border:1px solid #e2e8f0; }
        .stat-card .val { font-size:28px; font-weight:800; line-height:1; }
        .stat-card .lbl { font-size:12px; color:#64748b; margin-bottom:6px; }
        .stat-card .sub { font-size:11px; color:#94a3b8; margin-top:5px; }

        /* Table */
        .ttg-table { width:100%; border-collapse:collapse; font-size:13px; }
        .ttg-table thead tr { background:#f8fafc; }
        .ttg-table thead th { padding:11px 14px; font-size:10.5px; font-weight:700; letter-spacing:.05em; color:#64748b; text-transform:uppercase; text-align:left; border-bottom:1px solid #e2e8f0; }
        .ttg-table tbody tr { border-bottom:1px solid #f1f5f9; transition:background .15s; }
        .ttg-table tbody tr:hover { background:#f8fafc; }
        .ttg-table tbody td { padding:12px 14px; color:#374151; vertical-align:middle; }

        /* Badge */
        .badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:99px; font-size:11px; font-weight:600; }
        .badge-green  { background:#d1fae5; color:#065f46; }
        .badge-red    { background:#fee2e2; color:#991b1b; }
        .badge-yellow { background:#fef9c3; color:#713f12; }
        .badge-blue   { background:#dbeafe; color:#1e40af; }
        .badge-gray   { background:#f1f5f9; color:#475569; }
        .badge-indigo { background:#e0e7ff; color:#3730a3; }
        .badge-orange { background:#ffedd5; color:#9a3412; }

        /* Btn */
        .btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; transition:all .2s; border:none; text-decoration:none; }
        .btn-primary { background:#1DA1F2; color:#fff; }
        .btn-primary:hover { background:#0d8fd9; }
        .btn-success { background:#22c55e; color:#fff; }
        .btn-success:hover { background:#16a34a; }
        .btn-danger  { background:#ef4444; color:#fff; }
        .btn-danger:hover  { background:#dc2626; }
        .btn-gray    { background:#f1f5f9; color:#374151; border:1px solid #e2e8f0; }
        .btn-gray:hover    { background:#e2e8f0; }
        .btn-sm { padding:5px 12px; font-size:12px; }

        /* Input */
        .ttg-input { width:100%; border:1.5px solid #e2e8f0; border-radius:9px; padding:9px 13px; font-size:13px; outline:none; background:#fff; transition:border .2s; }
        .ttg-input:focus { border-color:#1DA1F2; box-shadow:0 0 0 3px rgba(29,161,242,.12); }
        .ttg-select { width:100%; border:1.5px solid #e2e8f0; border-radius:9px; padding:9px 13px; font-size:13px; outline:none; background:#fff; }
        .ttg-select:focus { border-color:#1DA1F2; }
        .ttg-label { display:block; font-size:11.5px; font-weight:600; color:#64748b; margin-bottom:5px; }

        /* Card panel */
        .panel { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; }
        .panel-header { padding:16px 20px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
        .panel-header h2 { font-size:14px; font-weight:700; color:#1e293b; }
        .panel-body { padding:20px; }

        /* Toast */
        #toast-container { position:fixed; top:20px; right:20px; z-index:9999; display:flex; flex-direction:column; gap:10px; }
        .toast { padding:13px 18px; border-radius:12px; color:#fff; font-size:13.5px; font-weight:500;
                 box-shadow:0 8px 24px rgba(0,0,0,.15); transform:translateX(120%); opacity:0;
                 transition:all .3s cubic-bezier(.34,1.56,.64,1); max-width:340px; }
        .toast.show { transform:translateX(0); opacity:1; }

        /* Avatar */
        .avatar { width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0; }

        /* Scrollbar */
        ::-webkit-scrollbar { width:5px; height:5px; }
        ::-webkit-scrollbar-track { background:transparent; }
        ::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:99px; }

        /* Page title */
        .page-title { font-size:22px; font-weight:800; color:#0f172a; }
        .page-sub   { font-size:13px; color:#94a3b8; margin-top:2px; }

        /* Filter bar */
        .filter-bar { background:#fff; border-radius:14px; border:1px solid #e2e8f0; padding:16px 20px; display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; }
    </style>
</head>

<body>

<div id="sidebar-overlay"
     class="fixed inset-0 bg-black/50 z-40 hidden backdrop-blur-sm"
     onclick="toggleSidebar()"></div>

<div class="flex min-h-screen">

    <!-- ═══════════════ SIDEBAR ═══════════════ -->
    <aside id="sidebar"
           class="fixed md:static top-0 left-0 h-full z-50 w-64 flex flex-col shadow-2xl
                  -translate-x-full md:translate-x-0"
           style="background:linear-gradient(180deg,#0a0f1e 0%,#0d1526 100%);">

        <!-- Logo -->
        <div class="flex justify-center items-center py-5 px-4" style="border-bottom:1px solid rgba(255,255,255,.07)">
            <img src="{{ asset('images/logo4.png') }}" class="h-10 object-contain">
        </div>

        <!-- Nav -->
        <nav class="flex-1 px-3 py-4 overflow-y-auto space-y-0.5">

            <a href="{{ route('admin.dashboard') }}"
               class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <span style="font-size:15px">📊</span> Dashboard
            </a>

            <div class="nav-section">Messagerie</div>

            <a href="{{ route('admin.messages.index') }}"
               class="nav-item {{ request()->routeIs('admin.messages.*') ? 'active' : '' }}">
                <span style="font-size:15px">💬</span> Users ↔ Chauffeurs
            </a>

            <a href="{{ route('admin.support.drivers.index') }}"
               class="nav-item {{ request()->routeIs('admin.support.drivers.*') ? 'active' : '' }}">
                <span style="font-size:15px">🛡️</span> Admin ↔ Chauffeurs
            </a>

            <a href="{{ route('admin.support.users.index') }}"
               class="nav-item {{ request()->routeIs('admin.support.users.*') ? 'active' : '' }}">
                <span style="font-size:15px">🛡️</span> Admin ↔ Utilisateurs
            </a>

            @php
                try { $activeSos = \App\Models\SosAlert::where('status','active')->count(); }
                catch(\Exception $e) { $activeSos = 0; }
            @endphp
            <a href="{{ route('admin.sos.index') }}"
               class="nav-item {{ request()->routeIs('admin.sos.*') ? 'active' : '' }}"
               style="justify-content:space-between">
                <span style="display:flex;align-items:center;gap:10px">
                    <span style="font-size:15px">🆘</span> SOS
                </span>
                @if($activeSos > 0)
                    <span style="background:#ef4444;color:#fff;font-size:10px;padding:2px 7px;border-radius:99px;font-weight:700;animation:pulse 1.5s infinite">
                        {{ $activeSos }}
                    </span>
                @endif
            </a>

            <div class="nav-section">Gestion</div>

            @php
                try { $pendingKyc = \App\Models\Driver\Driver::where('status','pending')->count(); }
                catch(\Exception $e) { $pendingKyc = 0; }
            @endphp
            <a href="{{ route('admin.drivers.index') }}"
               class="nav-item {{ request()->routeIs('admin.drivers.*') ? 'active' : '' }}"
               style="justify-content:space-between">
                <span style="display:flex;align-items:center;gap:10px">
                    <span style="font-size:15px">🚗</span> Chauffeurs
                </span>
                @if($pendingKyc > 0)
                    <span style="background:#ef4444;color:#fff;font-size:10px;padding:2px 7px;border-radius:99px;font-weight:700">
                        {{ $pendingKyc }}
                    </span>
                @endif
            </a>

            <a href="{{ route('admin.users.index') }}"
               class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <span style="font-size:15px">👤</span> Clients
            </a>

            <a href="{{ route('admin.profiles.index') }}"
               class="nav-item {{ request()->routeIs('admin.profiles.*') ? 'active' : '' }}">
                <span style="font-size:15px">🧑‍💼</span> Administrateurs
            </a>

            <div class="nav-section">Finances</div>

            <a href="{{ route('admin.revenus.index') }}"
               class="nav-item {{ request()->routeIs('admin.revenus.*') ? 'active' : '' }}">
                <span style="font-size:15px">💰</span> Revenus
            </a>

            <a href="{{ route('admin.commission-rates.index') }}"
               class="nav-item {{ request()->routeIs('admin.commission-rates.*') ? 'active' : '' }}">
                <span style="font-size:15px">📈</span> Commissions
            </a>

            <a href="{{ route('admin.payments.index') }}"
               class="nav-item {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                <span style="font-size:15px">💳</span> Partenaires Payeurs
            </a>

            <div class="nav-section">Localisation</div>

            <a href="{{ route('admin.trips.index') }}"
               class="nav-item {{ request()->routeIs('admin.trips.*') ? 'active' : '' }}">
                <span style="font-size:15px">📍</span> Trajets & Courses
            </a>

            <a href="#" class="nav-item">
                <span style="font-size:15px">🌍</span> Objectif Commercial
            </a>

            <a href="#" class="nav-item">
                <span style="font-size:15px">🔑</span> Mots de Passe
            </a>
        </nav>

        <!-- Profil / Déconnexion -->
        <div class="px-3 py-4" style="border-top:1px solid rgba(255,255,255,.07)">
            <div class="flex items-center gap-3 mb-3 px-2">
                <div class="avatar" style="background:#FFC107;color:#0a0f1e;font-size:13px">
                    {{ strtoupper(substr(session('admin_name','A'),0,1)) }}
                </div>
                <div>
                    <p style="font-size:13px;font-weight:600;color:#f1f5f9">{{ session('admin_name','Admin') }}</p>
                    <p style="font-size:11px;color:#64748b">Super Admin</p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="btn btn-gray w-full justify-center" style="background:rgba(255,255,255,.07);color:#cbd5e1;border-color:rgba(255,255,255,.1)">
                    Déconnexion
                </button>
            </form>
        </div>
    </aside>

    <!-- ═══════════════ CONTENT ═══════════════ -->
    <div class="flex-1 flex flex-col min-w-0">

        <!-- Mobile topbar -->
        <div class="md:hidden flex items-center justify-between px-4 py-3 sticky top-0 z-30 shadow-sm"
             style="background:#0a0f1e">
            <button onclick="toggleSidebar()" style="color:#fff;font-size:22px;line-height:1;background:none;border:none;cursor:pointer">☰</button>
            <img src="{{ asset('images/logo4.png') }}" class="h-8 object-contain">
            <div style="width:28px"></div>
        </div>

        <!-- Desktop topbar -->
        <header class="hidden md:flex items-center justify-between px-8 py-3 bg-white"
                style="border-bottom:1px solid #e2e8f0;position:sticky;top:0;z-index:20">
            <div>
                <p style="font-size:13px;color:#94a3b8">Bienvenue,</p>
                <p style="font-size:15px;font-weight:700;color:#0f172a">{{ session('admin_name','Admin') }} 👋</p>
            </div>
            <div style="display:flex;align-items:center;gap:12px">
                <span style="font-size:12px;color:#94a3b8">{{ now()->format('d M Y') }}</span>
                @if($activeSos > 0)
                    <a href="{{ route('admin.sos.index') }}"
                       style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;padding:5px 12px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:5px">
                        🆘 {{ $activeSos }} SOS actif{{ $activeSos > 1 ? 's' : '' }}
                    </a>
                @endif
            </div>
        </header>

        <main class="flex-1 p-4 md:p-7">
            <div id="toast-container"></div>

            @if(session('success'))
                <script>document.addEventListener("DOMContentLoaded",()=>showToast("{{ addslashes(session('success')) }}","success"));</script>
            @endif
            @if(session('error'))
                <script>document.addEventListener("DOMContentLoaded",()=>showToast("{{ addslashes(session('error')) }}","error"));</script>
            @endif

            @yield('content')
        </main>

        <footer style="background:#fff;border-top:1px solid #e2e8f0;padding:14px;text-align:center;font-size:12px;color:#94a3b8">
            © {{ date('Y') }} TopTopGo — Développé par <strong style="color:#374151">Basile NGASSAKI</strong>
        </footer>
    </div>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
function toggleSidebar(){
    const s=document.getElementById('sidebar'),o=document.getElementById('sidebar-overlay');
    const open=!s.classList.contains('-translate-x-full');
    if(open){ s.classList.add('-translate-x-full'); o.classList.add('hidden'); }
    else    { s.classList.remove('-translate-x-full'); o.classList.remove('hidden'); }
}
window.addEventListener('resize',()=>{
    if(window.innerWidth>=768){
        document.getElementById('sidebar').classList.remove('-translate-x-full');
        document.getElementById('sidebar-overlay').classList.add('hidden');
    }
});
function showToast(msg,type='success'){
    const t=document.createElement('div');
    t.className='toast';
    t.style.background=type==='success'?'#22c55e':type==='warning'?'#f59e0b':'#ef4444';
    t.innerHTML=`<span style="margin-right:8px">${type==='success'?'✓':type==='warning'?'⚠':'✕'}</span>${msg}`;
    document.getElementById('toast-container').appendChild(t);
    requestAnimationFrame(()=>{ requestAnimationFrame(()=>t.classList.add('show')); });
    setTimeout(()=>{ t.classList.remove('show'); setTimeout(()=>t.remove(),400); },4500);
}
function previewImage(e,id){
    const r=new FileReader();
    r.onload=function(){ const i=document.getElementById(id); i.src=r.result; i.classList.remove('hidden'); };
    r.readAsDataURL(e.target.files[0]);
}
</script>
@stack('scripts')
</body>
</html>
