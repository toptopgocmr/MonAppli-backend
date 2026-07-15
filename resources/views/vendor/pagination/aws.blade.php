{{--
    Pagination "maison" pour le panel société/admin (thème "aws-*").

    ✅ FIX : la vue de pagination par défaut de Laravel (tailwind.blade.php)
    contient des icônes SVG en chevron dimensionnées uniquement via des
    classes Tailwind ("h-5 w-5", etc.). Ce panel n'a jamais chargé Tailwind
    (il a son propre design "aws-*"), donc ces SVG s'affichaient à leur
    taille intrinsèque — d'énormes flèches bleues/noires plein écran sur
    n'importe quelle page paginée (ex: /company/calls). On remplace ici par
    du texte simple, sans aucun SVG, pour que ça ne puisse plus jamais
    déborder quel que soit le CSS chargé.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
        <div style="font-size:12px;color:var(--aws-sub)">
            {{ __('Affichage de') }} {{ $paginator->firstItem() }} {{ __('à') }} {{ $paginator->lastItem() }}
            {{ __('sur') }} {{ $paginator->total() }} {{ __('résultats') }}
        </div>

        <div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap">
            {{-- Précédent --}}
            @if ($paginator->onFirstPage())
                <span class="aws-btn aws-btn-normal" style="padding:6px 12px;font-size:12px;opacity:.45;cursor:default">‹ Précédent</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="aws-btn aws-btn-normal" style="padding:6px 12px;font-size:12px;text-decoration:none">‹ Précédent</a>
            @endif

            {{-- Numéros de page --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span style="padding:6px 8px;font-size:12px;color:var(--aws-sub)">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="aws-btn aws-btn-primary" style="padding:6px 12px;font-size:12px;cursor:default">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="aws-btn aws-btn-normal" style="padding:6px 12px;font-size:12px;text-decoration:none">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Suivant --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="aws-btn aws-btn-normal" style="padding:6px 12px;font-size:12px;text-decoration:none">Suivant ›</a>
            @else
                <span class="aws-btn aws-btn-normal" style="padding:6px 12px;font-size:12px;opacity:.45;cursor:default">Suivant ›</span>
            @endif
        </div>
    </nav>
@endif
