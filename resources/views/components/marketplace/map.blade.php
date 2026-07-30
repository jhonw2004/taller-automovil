@props([
    'center' => ['lat' => -17.7833, 'lon' => -63.1821],
    'zoom' => 12,
    'height' => 'h-full min-h-[420px]',
    'controls' => false,
    'controlsBottomClass' => 'bottom-24',
])

{{--
    `z-0`: Leaflet define z-index internos altos para sus panes/controles propios (hasta 1000,
    ver leaflet.css `.leaflet-top`/`.leaflet-bottom`). Sin un z-index explícito aquí, este `relative`
    no crea su propio stacking context, así que esos z-index internos "se escapan" y compiten
    directo con hermanos externos (drawer/chips/hoja de `map-experience.blade.php`, todos z-20),
    renderizando el mapa por encima de la UI de búsqueda. `z-0` contiene todo lo interno del mapa
    en su propio stacking context, para que los z-index externos (>0) siempre ganen.
--}}
<div
    x-data="map({ lat: {{ $center['lat'] }}, lon: {{ $center['lon'] }} }, {{ $zoom }})"
    x-init="init()"
    class="relative z-0 {{ $height }} w-full overflow-hidden {{ $controls ? '' : 'rounded-cards border border-cloud' }}"
>
    <div x-ref="container" class="size-full" role="application" aria-label="Mapa de talleres"></div>

    <div
        x-show="loadingTiles"
        x-transition
        class="absolute inset-0 z-[1000] flex items-center justify-center bg-white/70"
    >
        <svg class="size-32 animate-spin text-obsidian" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    </div>

    @if ($controls)
        <div class="pointer-events-none absolute inset-0 z-[999]">
            <div class="pointer-events-auto absolute right-16 {{ $controlsBottomClass }} flex flex-col items-center gap-8">
                <button
                    type="button"
                    x-on:click="locate()"
                    aria-label="Centrar en mi ubicación"
                    class="flex size-40 items-center justify-center rounded-buttons border border-cloud bg-white text-obsidian shadow-md hover:bg-paper focus:outline-none focus:ring-2 focus:ring-obsidian/30"
                >
                    <svg class="size-20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path stroke-linecap="round" d="M12 2v3M12 19v3M2 12h3M19 12h3"></path>
                    </svg>
                </button>

                <div class="flex flex-col overflow-hidden rounded-buttons border border-cloud bg-white shadow-md">
                    <button
                        type="button"
                        x-on:click="zoomIn()"
                        aria-label="Acercar"
                        class="flex size-40 items-center justify-center text-obsidian hover:bg-paper focus:outline-none focus:ring-2 focus:ring-obsidian/30"
                    >
                        <svg class="size-20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="M12 5v14M5 12h14"></path>
                        </svg>
                    </button>
                    <div class="h-px w-full bg-cloud"></div>
                    <button
                        type="button"
                        x-on:click="zoomOut()"
                        aria-label="Alejar"
                        class="flex size-40 items-center justify-center text-obsidian hover:bg-paper focus:outline-none focus:ring-2 focus:ring-obsidian/30"
                    >
                        <svg class="size-20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="M5 12h14"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
