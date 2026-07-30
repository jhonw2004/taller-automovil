{{--
    Experiencia "mapa primero" de /talleres/buscar (019-mapa-busqueda-ux). Reemplaza al layout de
    dos columnas de `marketplace/partials/search-experience.blade.php` (que queda sin
    consumidores y se elimina, ver `git log` de esta sesión). El mapa ocupa el 100% del
    contenedor (provisto por el layout `app-shell` de `marketplace/layouts/app.blade.php`);
    filtros, lista y detalle flotan sobre él.
--}}
@php($categorias = $categorias ?? [])

<div x-data="searchForm()" class="relative h-full w-full overflow-hidden">
    {{-- Capa 1: mapa a pantalla completa --}}
    <div class="absolute inset-0">
        <x-marketplace.map height="h-full" :controls="true" controls-bottom-class="bottom-[104px] md:bottom-24" />
    </div>

    {{-- Capa 2: drawer de filtros/resultados (desktop/tablet) --}}
    <div x-data="{ expanded: true }" class="pointer-events-none absolute inset-y-16 left-16 z-20 hidden md:block">
        <div
            x-show="expanded"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 -translate-x-4"
            class="pointer-events-auto flex h-full w-[360px] max-w-[90vw] flex-col overflow-hidden rounded-cards border border-cloud bg-white shadow-lg"
        >
            <div class="shrink-0 border-b border-cloud p-20">
                <div class="flex items-center justify-between gap-8">
                    <h1 class="text-subheading font-semibold text-graphite">Buscar talleres</h1>
                    <button
                        type="button"
                        x-on:click="expanded = false"
                        aria-label="Ocultar panel de filtros"
                        class="flex size-32 shrink-0 items-center justify-center rounded-buttons text-fog hover:bg-paper hover:text-obsidian"
                    >
                        <svg class="size-18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6"></path>
                        </svg>
                    </button>
                </div>

                <div class="mt-16">
                    <x-marketplace.search-filters :categorias="$categorias" />
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-20">
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
            class="pointer-events-auto flex size-48 items-center justify-center rounded-buttons border border-cloud bg-white text-obsidian shadow-lg hover:bg-paper"
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
            class="map-sheet flex flex-col overflow-hidden rounded-t-cards border-t border-cloud bg-white shadow-lg"
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

            <div class="flex-1 overflow-y-auto px-20 pb-20">
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
