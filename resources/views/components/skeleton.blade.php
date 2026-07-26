{{--
    Bloque gris animado de altura variable, constitution.md §5: se muestra en vez de resultados
    mientras Alpine.store('search').loading === true.
--}}
@props(['height' => 'h-24'])

<div {{ $attributes->merge(['class' => "animate-pulse rounded-inputs bg-cloud $height"]) }}></div>
