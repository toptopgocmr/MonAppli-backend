<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Espace Société - TopTopGo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #1a1a1a; font-family: 'Inter', 'Segoe UI', sans-serif; }

        .geo-bg { position:fixed; inset:0; overflow:hidden; pointer-events:none; z-index:0; }
        .geo-bg span { position:absolute; display:block; border-radius:6px; }
        .geo-bg .s1 { width:180px; height:180px; top:-60px;    left:-60px;   transform:rotate(25deg); background:rgba(255,193,7,0.55);  border:2px solid rgba(255,193,7,0.3); }
        .geo-bg .s2 { width:120px; height:120px; top:20px;     left:90px;    transform:rotate(15deg); background:rgba(29,161,242,0.40); border:2px solid rgba(29,161,242,0.25); }
        .geo-bg .s3 { width:160px; height:160px; bottom:-50px; right:-50px;  transform:rotate(30deg); background:rgba(255,193,7,0.45);  border:2px solid rgba(255,193,7,0.3); }
        .geo-bg .s4 { width:90px;  height:90px;  bottom:60px;  right:110px;  transform:rotate(20deg); background:rgba(29,161,242,0.35); border:2px solid rgba(29,161,242,0.2); }
        .geo-bg .s5 { width:80px;  height:80px;  top:42%;      left:4%;      transform:rotate(12deg); background:rgba(255,193,7,0.38);  border:2px solid rgba(255,193,7,0.25); }
        .geo-bg .s6 { width:100px; height:100px; top:18%;      right:5%;     transform:rotate(35deg); background:rgba(29,161,242,0.30); border:2px solid rgba(29,161,242,0.2); }
        @media (max-width:400px) {
            .geo-bg .s2, .geo-bg .s5 { display:none; }
            .geo-bg .s1 { width:110px; height:110px; }
            .geo-bg .s3 { width:110px; height:110px; }
        }

        .card { animation: slideUp 0.5s cubic-bezier(.22,.68,0,1.2) both; }
        @keyframes slideUp {
            from { opacity:0; transform:translateY(28px); }
            to   { opacity:1; transform:translateY(0); }
        }

        input:focus { outline:none; border-color:#FFC107 !important; box-shadow:0 0 0 3px rgba(255,193,7,.18); }
        input[type="checkbox"] { accent-color:#FFC107; }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 relative">

    <div class="geo-bg">
        <span class="s1"></span><span class="s2"></span><span class="s3"></span>
        <span class="s4"></span><span class="s5"></span><span class="s6"></span>
    </div>

    <div class="relative z-10 w-full max-w-md">

        <!-- Logo -->
        <div class="flex flex-col items-center mb-5">
            <h1 class="font-black tracking-tight" style="font-size:clamp(1.8rem,8vw,2.8rem);line-height:1;">
                <span style="color:#1DA1F2;">TopTop</span><span style="color:#FFC107;">Go</span>
            </h1>
            <span class="mt-1 text-xs font-semibold tracking-widest uppercase" style="color:#9ca3af">Espace Société</span>
        </div>

        <!-- Card -->
        <div class="card bg-white rounded-2xl shadow-lg px-5 py-7 sm:px-8 sm:py-9">

            <h2 class="text-lg sm:text-2xl font-bold text-gray-900 text-center mb-1">
                Connectez-vous à votre espace
            </h2>
            <p class="text-xs sm:text-sm text-gray-400 text-center mb-5 sm:mb-7">
                Location de véhicules &amp; covoiturage privé
            </p>

            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg mb-5 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg mb-5 text-sm">
                    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif

            <form action="{{ route('company.login.post') }}" method="POST" id="loginForm">
                @csrf

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">E-mail</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                           placeholder="contact@societe.com"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 text-sm bg-gray-50 transition-all duration-200 placeholder-gray-400">
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Mot de passe</label>
                    <input type="password" name="password" required autocomplete="current-password"
                           placeholder="••••••••"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 text-sm bg-gray-50 transition-all duration-200 placeholder-gray-400">
                </div>

                <div class="flex items-center gap-2 mb-6">
                    <input type="checkbox" name="remember" id="remember" class="w-4 h-4 rounded border-gray-300 cursor-pointer">
                    <label for="remember" class="text-sm text-gray-600 cursor-pointer select-none">
                        Rester connecté pendant une semaine
                    </label>
                </div>

                <button type="submit" id="loginBtn"
                        class="w-full py-3 rounded-xl font-semibold text-sm text-white flex justify-center items-center gap-2 transition-all duration-200 active:scale-[.98] hover:opacity-90"
                        style="background:linear-gradient(135deg,#FFC107,#e6a800);color:#1a1a1a">
                    <span id="btnText">Se connecter</span>
                    <svg id="loader" class="hidden animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                </button>

                <p class="text-center text-xs text-gray-400 mt-4">
                    Pas encore de compte ? Contactez l'administrateur TopTopGo
                </p>
            </form>
        </div>

        <p class="text-center text-xs mt-5" style="color:#6b7280">
            © {{ date('Y') }} TopTopGo · Plateforme de covoiturage et locations
        </p>

    </div>

<script>
    document.getElementById('loginForm').addEventListener('submit', function() {
        document.getElementById('btnText').innerText = 'Connexion...';
        document.getElementById('loader').classList.remove('hidden');
        document.getElementById('loginBtn').disabled = true;
    });
</script>

</body>
</html>
