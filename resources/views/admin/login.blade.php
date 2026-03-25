<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - TopTopGo</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .fade-in {
            animation: fadeIn 0.8s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .typing {
            overflow: hidden;
            border-right: .15em solid #FFC107;
            white-space: nowrap;
            animation: typing 3s steps(40, end), blink-caret .75s step-end infinite;
        }

        @keyframes typing {
            from { width: 0 }
            to { width: 100% }
        }

        @keyframes blink-caret {
            0%, 100% { border-color: transparent; }
            50% { border-color: #FFC107; }
        }
    </style>
</head>

<body class="bg-black min-h-screen flex items-center justify-center p-4">

<div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 fade-in">

    <!-- Logo -->
    <div class="text-center mb-6">
        <h1 class="text-3xl font-bold">
            <span class="text-[#1DA1F2]">TopTop</span>
            <span class="text-[#FFC107]">Go</span>
        </h1>
        <p class="text-gray-500 text-sm mt-2">Espace Administration</p>
    </div>

    <!-- Message dynamique -->
    <p class="text-center text-gray-600 text-lg mb-6">
        <span class="typing">
            Bienvenue sur la plateforme TopTopGo 👋
        </span>
    </p>

    <!-- Errors -->
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 text-sm">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <!-- Form -->
    <form action="{{ route('admin.login.submit') }}" method="POST" id="loginForm">
        @csrf

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-medium mb-2">Email</label>
            <input type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1DA1F2] focus:border-transparent transition"
                   placeholder="admin@toptopgo.com">
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-medium mb-2">Mot de passe</label>
            <input type="password"
                   name="password"
                   required
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1DA1F2] focus:border-transparent transition"
                   placeholder="••••••••">
        </div>

        <!-- BOUTON PREMIUM -->
        <button type="submit"
                id="loginBtn"
                class="w-full bg-[#1DA1F2] text-white py-3 rounded-lg font-semibold
                       transition-all duration-300 ease-in-out
                       hover:bg-[#FFC107] hover:text-black
                       hover:-translate-y-1
                       hover:shadow-[0_0_20px_#FFC107]
                       active:scale-95
                       flex justify-center items-center gap-2">

            <span id="btnText" class="transition-all duration-300">
                Se connecter
            </span>

            <svg id="loader"
                 class="hidden animate-spin h-5 w-5 text-current"
                 xmlns="http://www.w3.org/2000/svg"
                 fill="none"
                 viewBox="0 0 24 24">
                <circle class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4">
                </circle>
                <path class="opacity-75"
                      fill="currentColor"
                      d="M4 12a8 8 0 018-8v8H4z">
                </path>
            </svg>

        </button>
    </form>

    <!-- Footer -->
    <p class="text-center text-gray-500 text-sm mt-6">
        © {{ date('Y') }} TopTopGo.
        Développé avec ❤️ par
        <span class="font-bold text-black">Basile NGASSAKI</span>
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