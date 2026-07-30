{{--
    Experiencia "mapa primero" de /talleres/buscar (019-mapa-busqueda-ux). Reemplaza al layout de
    dos columnas de `marketplace/partials/search-experience.blade.php` (que queda sin
    consumidores y se elimina, ver `git log` de esta sesión). El mapa ocupa el 100% del
    contenedor (provisto por el layout `app-shell` de `marketplace/layouts/app.blade.php`);
    filtros, lista y detalle flotan sobre él.

    El panel de escritorio es un sidebar acoplado al borde izquierdo (no una tarjeta flotante):
    ocupa el 100% del alto disponible, sin esquinas redondeadas, con un único borde derecho que lo
    separa del mapa (feedback del usuario: nada de "chips"/tags de filtro, nada de bordes
    redondeados, debe verse y comportarse como un sidebar real).
--}}
@php($categorias = $categorias ?? [])

<div x-data="searchForm()" class="relative h-full w-full overflow-hidden">
    {{-- Capa 1: mapa a pantalla completa --}}
    <div class="absolute inset-0">
        <x-marketplace.map height="h-full" :controls="true" controls-bottom-class="bottom-[104px] md:bottom-24" />
    </div>

    {{-- Capa 2: sidebar de filtros/resultados (desktop/tablet), acoplado al borde izquierdo --}}
    <div x-data="{ expanded: true }" class="absolute inset-y-0 left-0 z-20 hidden h-full md:block">
        <div
            x-show="expanded"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 -translate-x-4"
            class="flex h-full w-[380px] max-w-full flex-col overflow-hidden border-r border-cloud bg-white"
        >
            <div class="flex shrink-0 items-center justify-between gap-8 border-b border-cloud p-20">
                <h1 class="text-subheading font-semibold text-graphite">Buscar talleres</h1>
                <button
                    type="button"
                    x-on:click="expanded = false"
                    aria-label="Ocultar panel de filtros"
                    class="flex size-32 shrink-0 items-center justify-center text-fog hover:bg-paper hover:text-obsidian"
                >
                    <svg class="size-18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6"></path>
                    </svg>
                </button>
            </div>

            <div class="shrink-0 border-b border-cloud p-20">
                <x-marketplace.search-filters :categorias="$categorias" />
            </div>

            {{-- `min-h-0` es necesario para que este panel respete `flex-1` y sea ESTE el que
                 scrollea (con muchos resultados) en vez de crecer y desbordar todo el sidebar. --}}
            <div class="min-h-0 flex-1 overflow-y-auto p-20">
                <p x-show="!$store.search.loading && $store.search.results.length > 0" class="mb-12 text-caption text-fog">
                    <span x-text="$store.search.total"></span> talleres encontrados
                </p>

                @include('marketplace.search._results-list')
            </div>
        </div>

        <button
            type="button"
            x-show="!expanded"
            x-on:click="expanded = true"
            aria-label="Mostrar panel de filtros"
            class="flex size-48 items-center justify-center border border-cloud bg-white text-obsidian shadow-lg hover:bg-paper"
        >
            <svg class="size-20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"></path>
            </svg>
        </button>
    </div>

    {{-- Capa 3: hoja inferior de filtros/resultados (móvil) --}}
    <div
        x-data="{ sheet: 'peek' }"
        x-show="!$store.search.selected"
        class="absolute inset-x-0 bottom-0 z-20 md:hidden"
    >
        <div
            class="map-sheet flex flex-col overflow-hidden border-t border-cloud bg-white shadow-lg"
            :class="{ 'h-[104px]': sheet === 'peek', 'h-1/2': sheet === 'half', 'h-[calc(100%-56px)]': sheet === 'full' }"
        >
            <button
                type="button"
                x-on:click="sheet = sheet === 'peek' ? 'half' : (sheet === 'half' ? 'full' : 'peek')"
                class="flex shrink-0 flex-col items-center gap-8 px-20 pb-8 pt-12"
                aria-label="Expandir o colapsar la lista de talleres"
            >
                <span class="h-4 w-40 rounded-pills bg-cloud"></span>
                <span class="text-caption text-fog">
                    <span x-text="$store.search.total"></span> talleres encontrados
                </span>
            </button>

            {{-- `min-h-0` por la misma razón que en el sidebar de escritorio: el scroll debe vivir
                 aquí, no en la hoja completa. --}}
            <div class="min-h-0 flex-1 overflow-y-auto px-20 pb-20">
                <div class="mb-16">
                    <x-marketplace.search-filters :categorias="$categorias" />
                </div>

                @include('marketplace.search._results-list')
            </div>
        </div>
    </div>

    {{-- Capa 4: card flotante de detalle (móvil — desktop/tablet usa el popup de Leaflet, ver map.js) --}}
    <div
        x-show="$store.search.selected"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="absolute inset-x-0 bottom-0 z-20 md:hidden"
    >
        <div class="border-t border-cloud bg-white shadow-lg">
            <x-marketplace.taller-popover-content />
        </div>
    </div>
</div>
