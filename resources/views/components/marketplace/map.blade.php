@props([
    'center' => ['lat' => -17.7833, 'lon' => -63.1821],
    'zoom' => 12,
    'height' => 'h-full min-h-[420px]',
])

<div
    x-data="map({ lat: {{ $center['lat'] }}, lon: {{ $center['lon'] }} }, {{ $zoom }})"
    x-init="init()"
    class="relative {{ $height }} w-full overflow-hidden rounded-cards border border-cloud"
>
    <div x-ref="container" class="size-full" role="application" aria-label="Mapa de talleres"></div>

    <div
        x-show="loadingTiles"
        x-transition
        class="absolute inset-0 flex items-center justify-center bg-white/70"
    >
        <svg class="size-32 animate-spin text-obsidian" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    </div>
</div>
