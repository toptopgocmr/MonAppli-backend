@extends('admin.layouts.app')
@section('title','Profil Agent')

@section('content')
<div class="max-w-3xl mx-auto">

    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.company-agents.index') }}" class="btn-back">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
            Retour
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Profil Agent</h1>
            <p class="text-gray-500 text-sm mt-1">{{ $agent->name }} — {{ $agent->company->name ?? 'Société inconnue' }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-md p-8 mb-6">
        <div class="flex items-center gap-6 mb-6">
            <div class="w-20 h-20 rounded-full bg-indigo-500 flex items-center justify-center text-3xl font-bold text-white">
                {{ strtoupper(substr($agent->name, 0, 1)) }}
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-800">{{ $agent->name }}</h2>
                <p class="text-gray-500">{{ $agent->email }}</p>
                <div class="flex gap-2 mt-2">
                    @if($agent->status === 'active')
                        <span class="bg-green-100 text-green-700 text-xs font-semibold px-3 py-1 rounded-full">Actif</span>
                    @else
                        <span class="bg-red-100 text-red-700 text-xs font-semibold px-3 py-1 rounded-full">Suspendu</span>
                    @endif
                    <span class="bg-indigo-100 text-indigo-700 text-xs font-semibold px-3 py-1 rounded-full">{{ $agent->role_label }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <div class="bg-gray-50 rounded-xl p-4">
                <div class="text-xs text-gray-400 uppercase font-semibold mb-1">Société</div>
                <div class="font-medium text-gray-800">{{ $agent->company->name ?? '—' }}</div>
            </div>
            <div class="bg-gray-50 rounded-xl p-4">
                <div class="text-xs text-gray-400 uppercase font-semibold mb-1">Téléphone</div>
                <div class="font-medium text-gray-800">{{ $agent->phone ?? '—' }}</div>
            </div>
            <div class="bg-gray-50 rounded-xl p-4">
                <div class="text-xs text-gray-400 uppercase font-semibold mb-1">Créé le</div>
                <div class="font-medium text-gray-800">{{ $agent->created_at?->format('d/m/Y H:i') }}</div>
            </div>
            <div class="bg-gray-50 rounded-xl p-4">
                <div class="text-xs text-gray-400 uppercase font-semibold mb-1">Dernière connexion</div>
                <div class="font-medium text-gray-800">{{ $agent->last_login_at?->format('d/m/Y H:i') ?? 'Jamais connecté' }}</div>
            </div>
        </div>

        <div class="mt-6">
            <div class="text-xs text-gray-400 uppercase font-semibold mb-2">Permissions</div>
            @if($agent->isDirecteurGeneral())
                <span class="bg-purple-100 text-purple-700 text-xs font-semibold px-3 py-1 rounded-full">Accès complet (Directeur Général)</span>
            @elseif(!empty($agent->permissions))
                <div class="flex flex-wrap gap-2">
                    @foreach($agent->permissions as $perm)
                        <span class="bg-blue-100 text-blue-700 text-xs font-semibold px-3 py-1 rounded-full">
                            {{ \App\Models\CompanyAgent::PERMISSIONS[$perm] ?? $perm }}
                        </span>
                    @endforeach
                </div>
            @else
                <span class="text-gray-400 text-sm">Aucune permission attribuée</span>
            @endif
        </div>

        <div class="flex gap-3 mt-8">
            @if($agent->status === 'active')
                <form method="POST" action="{{ route('admin.company-agents.suspend', $agent->id) }}">
                    @csrf
                    <button type="submit" onclick="return confirm('Suspendre cet agent ?')"
                            class="bg-orange-100 text-orange-700 px-6 py-2.5 rounded-xl font-semibold hover:bg-orange-200 transition">
                        Suspendre l'accès
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.company-agents.activate', $agent->id) }}">
                    @csrf
                    <button type="submit"
                            class="bg-green-100 text-green-700 px-6 py-2.5 rounded-xl font-semibold hover:bg-green-200 transition">
                        Réactiver l'accès
                    </button>
                </form>
            @endif

            @if($agent->company)
                <a href="{{ route('admin.companies.show', $agent->company_id) }}"
                   class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-xl font-semibold hover:bg-gray-200 transition">
                    Voir la société
                </a>
            @endif
        </div>
    </div>

</div>
@endsection
