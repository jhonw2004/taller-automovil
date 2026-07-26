@props([
    'taller',
    'showDistance' => false,
    'showRating' => true,
])

@php
    $nombre = data_get($taller, 'nombre');
    $slug = data_get($taller, 'slug');
    $direccion = data_get($taller, 'direccion');
    $logoUrl = data_get($taller, 'logo_url');
    $calificacion = (float) data_get($taller, 'calificacion_promedio', 0);
    $cantidadResenas = (int) data_get($taller, 'cantidad_resenas', 0);
    $distanciaKm = data_get($taller, 'distancia_km');
    $categorias = collect(data_get($taller, 'categorias', []));
@endphp

<x-card padding="lg" class="flex flex-col gap-16">
    <div class="flex items-center gap-16">
        <div class="flex size-64 shrink-0 items-center justify-center overflow-hidden rounded-cards bg-paper">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo de {{ $nombre }}" class="size-full object-cover">
            @else
                <span class="text-heading-sm font-semibold text-ash">{{ mb_substr($nombre ?? '?', 0, 1) }}</span>
            @endif
        </div>

        <div class="min-w-0 flex-1">
            <a href="{{ route('talleres.show', $slug) }}" class="block truncate text-subheading font-semibold text-graphite hover:text-obsidian">
                {{ $nombre }}
            </a>
            @if ($direccion)
                <p class="truncate text-caption text-fog">{{ $direccion }}</p>
            @endif
        </div>
    </div>

    @if ($categorias->isNotEmpty())
        <div class="flex flex-wrap gap-8">
            @foreach ($categorias as $categoria)
                <x-badge type="neutral" size="sm">{{ is_array($categoria) || is_object($categoria) ? data_get($categoria, 'nombre') : $categoria }}</x-badge>
            @endforeach
        </div>
    @endif

    <div class="flex items-center justify-between text-caption text-fog">
        @if ($showRating)
            <div class="flex items-center gap-8">
                <x-marketplace.star-rating :value="$calificacion" />
                <span>{{ number_format($calificacion, 1) }} ({{ $cantidadResenas }})</span>
            </div>
        @else
            <span></span>
        @endif

        @if ($showDistance && $distanciaKm !== null)
            <span>{{ number_format((float) $distanciaKm, 1) }} km</span>
        @endif
    </div>
</x-card>
