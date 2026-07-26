{{--
    Renderiza una reseña de 006-resenas-favoritos. Ese modelo (`Resena`) no existe todavía en esta
    sesión (016 se implementa junto a 005, antes de 006) — el prop `resena` acepta cualquier
    estructura con estos campos (Eloquent model o array/stdClass) para que 006 lo consuma sin
    cambios cuando exista el modelo real.
--}}
@props(['resena'])

@php
    $nombre = data_get($resena, 'usuario.nombre', data_get($resena, 'nombre'));
    $avatarUrl = data_get($resena, 'usuario.avatar_url', data_get($resena, 'avatar_url'));
    $rating = (float) data_get($resena, 'calificacion', data_get($resena, 'rating', 0));
    $fecha = data_get($resena, 'created_at');
    $comentario = data_get($resena, 'comentario');
@endphp

<x-card padding="md" class="flex flex-col gap-12">
    <div class="flex items-center gap-12">
        <div class="flex size-40 shrink-0 items-center justify-center overflow-hidden rounded-full bg-paper text-body font-semibold text-ash">
            @if ($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="Avatar de {{ $nombre }}" class="size-full object-cover">
            @else
                {{ mb_substr($nombre ?? '?', 0, 1) }}
            @endif
        </div>

        <div class="flex-1">
            <p class="text-body font-medium text-graphite">{{ $nombre ?? 'Usuario' }}</p>
            @if ($fecha)
                <p class="text-caption text-fog">{{ \Illuminate\Support\Carbon::parse($fecha)->translatedFormat('d M Y') }}</p>
            @endif
        </div>

        <x-marketplace.star-rating :value="$rating" />
    </div>

    @if ($comentario)
        <p class="text-body text-graphite">{{ $comentario }}</p>
    @endif
</x-card>
