{{--
    Experiencia "mapa primero" de /talleres/buscar (019-mapa-busqueda-ux, rediseñada estilo Apple
    Maps — ver `buscartallerui.md`). El mapa ocupa el 100% del contenedor (provisto por el layout
    `app-shell` de `marketplace/layouts/app.blade.php`); panel, hoja inferior y card de detalle
    flotan sobre él.

    El panel de escritorio es una tarjeta flotante con margen respecto a los bordes del mapa
    (`rounded-cards`, `shadow-lg`), igual que el sidebar de Apple Maps web — reemplaza la decisión
    anterior de esta vista (sidebar acoplado sin bordes redondeados), superada por el pedido
    explícito de que la experiencia se sienta como Apple Maps manteniendo la paleta del proyecto.
--}}
@php($categorias = $categorias ?? [])

<div x-data="searchForm()" class="relative h-full w-full overflow-hidden">
    {{-- Capa 1: mapa a pantalla completa --}}
    <div class="absolute inset-0">
        <x-marketplace.map height="h-full" :controls="true" controls-bottom-class="bottom-[104px] md:bottom-24" />
    </div>

    {{-- Capa 2: panel de filtros/resultados (desktop/tablet), tarjeta flotante con margen --}}
    <div
        x-data="{ expanded: true }"
        x-on:keydown.escape.window="$store.search.selected !== null ? ($store.search.selected = null) : (expanded = false)"
        class="absolute left-16 top-16 bottom-16 z-20 hidden md:block"
    >
        <div
            x-show="expanded"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 -translate-x-4"
            class="flex h-full w-[380px] max-w-full flex-col overflow-hidden rounded-cards border border-cloud bg-white shadow-lg"
        >
            <div class="flex shrink-0 items-start justify-between gap-8 p-16 pb-0">
                <h1 class="pt-8 text-subheading font-semibold text-graphite">Buscar talleres</h1>
                <button
                    type="button"
                    x-on:click="expanded = false"
                    :aria-expanded="expanded"
                    aria-label="Ocultar panel de filtros"
                    class="flex size-32 shrink-0 items-center justify-center rounded-buttons text-fog hover:bg-paper hover:text-obsidian"
                >
                    <svg class="size-20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6"></path>
                    </svg>
                </button>
            </div>

            <div class="shrink-0 p-16">
                <x-marketplace.search-filters :categorias="$categorias" />
            </div>

            {{-- `min-h-0` es necesario para que este panel respete `flex-1` y sea ESTE el que
                 scrollea (con muchos resultados) en vez de crecer y desbordar toda la tarjeta. --}}
            <div class="min-h-0 flex-1 overflow-y-auto border-t border-cloud px-16 pb-16 pt-12">
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
            :aria-expanded="expanded"
            aria-label="Mostrar panel de filtros"
            class="flex size-48 items-center justify-center rounded-buttons border border-cloud bg-white text-obsidian shadow-lg hover:bg-paper"
        >
            <svg class="size-20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"></path>
            </svg>
        </button>
    </div>

    {{-- Capa 3: hoja inferior de filtros/resultados (móvil), esquinas superiores redondeadas --}}
    <div
        x-data="{ sheet: 'peek' }"
        x-show="!$store.search.selected"
        x-on:keydown.escape.window="$store.search.selected !== null ? ($store.search.selected = null) : (sheet = 'peek')"
        class="absolute inset-x-0 bottom-0 z-20 md:hidden"
    >
        <div
            class="map-sheet flex flex-col overflow-hidden rounded-t-cards border-t border-cloud bg-white shadow-lg"
            :class="{ 'h-[104px]': sheet === 'peek', 'h-1/2': sheet === 'half', 'h-[calc(100%-56px)]': sheet === 'full' }"
        >
            <button
                type="button"
                x-on:click="sheet = sheet === 'peek' ? 'half' : (sheet === 'half' ? 'full' : 'peek')"
                :aria-expanded="sheet !== 'peek'"
                class="flex shrink-0 flex-col items-center gap-8 px-20 pb-8 pt-12"
                aria-label="Expandir o colapsar la lista de talleres"
            >
                <span class="h-4 w-40 rounded-pills bg-cloud"></span>
                <span class="text-caption text-fog">
                    <span x-text="$store.search.total"></span> talleres encontrados
                </span>
            </button>

            {{-- `min-h-0` por la misma razón que en el panel de escritorio: el scroll debe vivir
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
        <div class="rounded-t-cards border-t border-cloud bg-white shadow-lg">
            <x-marketplace.taller-popover-content />
        </div>
    </div>
</div>
