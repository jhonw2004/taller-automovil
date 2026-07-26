@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-16 text-body text-graphite" aria-label="Paginación">
        <div>
            @if ($paginator->onFirstPage())
                <span class="cursor-not-allowed rounded-buttons px-16 py-8 text-ash">Anterior</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="rounded-buttons px-16 py-8 hover:bg-paper">Anterior</a>
            @endif
        </div>

        <p class="text-caption text-fog">
            Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}
        </p>

        <div>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="rounded-buttons px-16 py-8 hover:bg-paper">Siguiente</a>
            @else
                <span class="cursor-not-allowed rounded-buttons px-16 py-8 text-ash">Siguiente</span>
            @endif
        </div>
    </nav>
@endif
