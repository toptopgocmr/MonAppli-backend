{{-- Variante simple (Previous/Next uniquement) — mêmes raisons qu'aws.blade.php --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" style="display:flex;align-items:center;gap:4px">
        @if ($paginator->onFirstPage())
            <span class="aws-btn aws-btn-normal" style="padding:6px 12px;font-size:12px;opacity:.45;cursor:default">‹ Précédent</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="aws-btn aws-btn-normal" style="padding:6px 12px;font-size:12px;text-decoration:none">‹ Précédent</a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="aws-btn aws-btn-normal" style="padding:6px 12px;font-size:12px;text-decoration:none">Suivant ›</a>
        @else
            <span class="aws-btn aws-btn-normal" style="padding:6px 12px;font-size:12px;opacity:.45;cursor:default">Suivant ›</span>
        @endif
    </nav>
@endif
