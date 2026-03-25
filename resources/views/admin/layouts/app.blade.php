<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>TopTopGo Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo3.ico') }}" />

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
</head>

<body class="bg-gray-100">

<div class="flex min-h-screen">

    <!-- ================= SIDEBAR ================= -->
    <aside class="w-72 bg-black text-white flex flex-col shadow-2xl">

        <!-- LOGO -->
        <div class="flex justify-center items-center py-6 border-b border-gray-800">
            <img src="{{ asset('images/logo4.png') }}" class="w-48 h-auto object-contain">
        </div>

        <!-- MENU -->
        <nav class="flex-1 p-4 space-y-2 text-sm overflow-y-auto">

            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center px-4 py-2 rounded-lg
               hover:bg-[#1DA1F2] hover:pl-6 transition-all duration-300
               {{ request()->routeIs('admin.dashboard') ? 'bg-[#1DA1F2] pl-6' : '' }}">
                📊 DASHBOARD
            </a>

            <!-- MESSAGERIE -->
            <p class="text-xs text-gray-400 mt-6 uppercase tracking-wider">
                Messagerie
            </p>

            <a href="{{ route('admin.messages.index') }}"
               class="block px-4 py-2 rounded-lg hover:bg-[#1DA1F2] hover:pl-6 transition-all duration-300
               {{ request()->routeIs('admin.messages.*') ? 'bg-[#1DA1F2] pl-6' : '' }}">
                💬 Users ↔ Chauffeurs
            </a>

            <a href="{{ route('admin.support.drivers.index') }}"
               class="block px-4 py-2 rounded-lg hover:bg-[#1DA1F2] hover:pl-6 transition-all duration-300
               {{ request()->routeIs('admin.support.drivers.*') ? 'bg-[#1DA1F2] pl-6' : '' }}">
                🛡 Admin ↔ Chauffeurs
            </a>

            <a href="{{ route('admin.support.users.index') }}"
               class="block px-4 py-2 rounded-lg hover:bg-[#1DA1F2] hover:pl-6 transition-all duration-300
               {{ request()->routeIs('admin.support.users.*') ? 'bg-[#1DA1F2] pl-6' : '' }}">
                🛡 Admin ↔ Utilisateurs
            </a>

            {{-- SOS avec badge rouge animé --}}
            @php
                try {
                    $activeSos = \App\Models\SosAlert::where('status', 'active')->count();
                } catch (\Exception $e) {
                    $activeSos = 0;
                }
            @endphp

            <a href="{{ route('admin.sos.index') }}"
               class="flex justify-between items-center px-4 py-2 rounded-lg hover:bg-[#1DA1F2] hover:pl-6 transition-all duration-300
               {{ request()->routeIs('admin.sos.*') ? 'bg-[#1DA1F2] pl-6' : '' }}">
                <span>🆘 SOS</span>
                @if($activeSos > 0)
                    <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full animate-pulse">
                        {{ $activeSos }}
                    </span>
                @endif
            </a>

            <!-- GESTION -->
            <p class="text-xs text-gray-400 mt-6 uppercase tracking-wider">
                Gestion
            </p>

            @php
                try {
                    $pendingKyc = \App\Models\Driver\Driver::where('status', 'pending')->count();
                } catch (\Exception $e) {
                    $pendingKyc = 0;
                }
            @endphp

            <a href="{{ route('admin.drivers.index') }}"
               class="flex justify-between items-center px-4 py-2 rounded-lg hover:bg-[#1DA1F2] hover:pl-6 transition-all duration-300
               {{ request()->routeIs('admin.drivers.*') ? 'bg-[#1DA1F2] pl-6' : '' }}">
                <span>🚗 Chauffeurs</span>
                @if($pendingKyc > 0)
                    <span class="bg-red-600 text-xs px-2 py-1 rounded-full">
                        {{ $pendingKyc }}
                    </span>
                @endif
            </a>

            <a href="{{ route('admin.users.index') }}"
               class="block px-4 py-2 rounded-lg hover:bg-[#1DA1F2] hover:pl-6 transition-all duration-300
               {{ request()->routeIs('admin.users.*') ? 'bg-[#1DA1F2] pl-6' : '' }}">
                👤 Clients
            </a>

            <a href="{{ route('admin.profiles.index') }}"
               class="block px-4 py-2 rounded-lg hover:bg-[#1DA1F2] hover:pl-6 transition-all duration-300
               {{ request()->routeIs('admin.profiles.*') ? 'bg-[#1DA1F2] pl-6' : '' }}">
                👤 Administrateurs
            </a>

            <!-- FINANCES -->
            <p class="text-xs text-gray-400 mt-6 uppercase tracking-wider">
                Finances
            </p>

            <a href="{{ route('admin.revenus.index') }}"
               class="block px-4 py-2 rounded-lg hover:bg-[#1DA1F2] hover:pl-6 transition-all duration-300
               {{ request()->routeIs('admin.revenus.*') ? 'bg-[#1DA1F2] pl-6' : '' }}">
                💰 Revenus
            </a>

            <a href="{{ route('admin.commission-rates.index') }}"
               class="block px-4 py-2 rounded-lg hover:bg-[#1DA1F2] hover:pl-6 transition-all duration-300
               {{ request()->routeIs('admin.commission-rates.*') ? 'bg-[#1DA1F2] pl-6' : '' }}">
                📊 Commissions
            </a>

            <a href="{{ route('admin.payments.index') }}"
               class="block px-4 py-2 rounded-lg hover:bg-[#1DA1F2] hover:pl-6 transition-all duration-300
               {{ request()->routeIs('admin.payments.*') ? 'bg-[#1DA1F2] pl-6' : '' }}">
                💱 Partenaires Payeurs
            </a>

            <!-- LOCALISATION -->
            <p class="text-xs text-gray-400 mt-6 uppercase tracking-wider">
                Localisation
            </p>

            <a href="{{ route('admin.trips.index') }}"
               class="block px-4 py-2 rounded-lg hover:bg-[#1DA1F2] hover:pl-6 transition-all duration-300
               {{ request()->routeIs('admin.trips.index') ? 'bg-[#1DA1F2] pl-6' : '' }}">
                📍 Trajets et Courses
            </a>

            <a href="#" class="block px-4 py-2 rounded-lg hover:bg-[#1DA1F2] hover:pl-6 transition-all duration-300">
                🌍 Objectif Commercial
            </a>

            <a href="#" class="block px-4 py-2 rounded-lg hover:bg-[#1DA1F2] hover:pl-6 transition-all duration-300">
                🏙️ Gestion des Mots de Passes
            </a>

        </nav>

        <!-- PROFIL -->
        <div class="p-4 border-t border-gray-800 bg-gray-900">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-[#FFC107] rounded-full flex items-center justify-center text-black font-bold">
                    {{ strtoupper(substr(session('admin_name', 'A'), 0, 1)) }}
                </div>
                <div>
                    <p class="text-sm font-semibold">
                        {{ session('admin_name', 'Admin') }}
                    </p>
                    <p class="text-xs text-gray-400">Super Admin</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit"
                    class="w-full bg-[#FFC107] text-black py-2 rounded-lg font-semibold
                           hover:bg-[#1DA1F2] hover:text-white
                           transition-all duration-300">
                    Déconnexion
                </button>
            </form>
        </div>

    </aside>

    <!-- ================= CONTENT ================= -->
    <div class="flex-1 flex flex-col">

        <main class="flex-1 p-8">

            <!-- Toast container -->
            <div id="toast-container" class="fixed top-5 right-5 z-50 space-y-3"></div>

            @if(session('success'))
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        showToast("{{ session('success') }}", "success");
                    });
                </script>
            @endif

            @if(session('error'))
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        showToast("{{ session('error') }}", "error");
                    });
                </script>
            @endif

            @yield('content')

        </main>

        <footer class="bg-white border-t py-4">
            <p class="text-center text-gray-500 text-sm">
                © {{ date('Y') }} TopTopGo. Développé avec ❤️ par
                <span class="font-bold text-gray-700">Basile NGASSAKI</span>
            </p>
        </footer>

    </div>

</div>

<!-- Leaflet -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
function showToast(message, type = "success") {
    const toast = document.createElement("div");
    toast.className = `
        px-5 py-3 rounded-lg shadow-lg text-white
        transform transition-all duration-300 translate-x-20 opacity-0
        ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}
    `;
    toast.innerText = message;
    document.getElementById("toast-container").appendChild(toast);
    setTimeout(() => { toast.classList.remove("translate-x-20","opacity-0"); }, 100);
    setTimeout(() => {
        toast.classList.add("opacity-0");
        setTimeout(() => toast.remove(), 500);
    }, 4000);
}

function previewImage(event, previewId) {
    const reader = new FileReader();
    reader.onload = function(){
        const img = document.getElementById(previewId);
        img.src = reader.result;
        img.classList.remove('hidden');
    };
    reader.readAsDataURL(event.target.files[0]);
}
</script>

@stack('scripts')

</body>
</html>