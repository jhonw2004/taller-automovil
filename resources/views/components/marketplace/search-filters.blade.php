@props(['categorias' => []])

{{--
    Buscador de /talleres/buscar, estilo Apple Maps (rediseño): un campo de búsqueda prominente
    siempre visible + un panel de filtros secundarios (categoría, calificación, abierto ahora,
    usar mi ubicación) que se abre/cierra con un botón de filtro, en vez de mostrar todos los
    controles apilados de una vez. Usa los tokens normales del design system (`rounded-inputs`,
    `rounded-buttons`, `rounded-cards`) — la decisión anterior de esta vista de no redondear nada
    quedó superada por el pedido explícito de que la experiencia se sienta como Apple Maps.

    El filtro de "radio de búsqueda" (slider en km) se eliminó de la UI: no aportaba valor al
    usuario y complicaba el panel. `Alpine.store('search').radius` sigue existiendo con un valor
    fijo razonable (ver `resources/js/alpine/store.js`) para no romper el contrato del endpoint
    `GET /api/talleres/search`, que solo aplica radio cuando hay `lat`/`lon` (ubicación activa).
--}}
<div x-data="{ filtersOpen: false }" class="flex flex-col gap-12">
    <div class="flex items-stretch gap-8">
        <div class="relative flex-1">
            <svg class="pointer-events-none absolute left-12 top-1/2 size-20 -translate-y-1/2 text-fog" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <circle cx="11" cy="11" r="7"></circle>
                <path stroke-linecap="round" d="M21 21l-4.35-4.35"></path>
            </svg>
            <label for="filter-q" class="sr-only">Buscar por nombre</label>
            <input
                id="filter-q"
                type="text"
                x-model.debounce.400ms="$store.search.query"
                x-on:input="$store.search.search()"
                placeholder="Buscar talleres"
                class="w-full rounded-inputs border border-cloud bg-white py-12 pl-40 pr-40 text-body text-graphite transition placeholder:text-ash hover:border-fog focus:outline-none focus:ring-2 focus:ring-obsidian/20"
            >
            <button
                type="button"
                x-show="$store.search.query"
                x-on:click="$store.search.query = ''; $store.search.search()"
                aria-label="Limpiar búsqueda"
                class="absolute right-8 top-1/2 flex size-28 -translate-y-1/2 items-center justify-center rounded-pills text-fog hover:bg-paper hover:text-obsidian"
            >
                <svg class="size-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"></path>
                </svg>
            </button>
        </div>

        <button
            type="button"
            x-on:click="filtersOpen = !filtersOpen"
            :aria-expanded="filtersOpen"
            :class="filtersOpen ? 'border-obsidian bg-obsidian text-white' : 'border-cloud bg-white text-obsidian hover:bg-paper'"
            aria-label="Mostrar u ocultar filtros"
            class="flex shrink-0 items-center justify-center rounded-buttons border px-16 transition"
        >
            <svg class="size-20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"></path>
                <circle cx="9" cy="7" r="1.6" fill="currentColor" stroke="none"></circle>
                <circle cx="15" cy="12" r="1.6" fill="currentColor" stroke="none"></circle>
                <circle cx="9" cy="17" r="1.6" fill="currentColor" stroke="none"></circle>
            </svg>
        </button>
    </div>

    <div
        x-show="filtersOpen"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="flex flex-col gap-16 rounded-cards border border-cloud bg-paper p-16"
    >
        <div>
            <label for="filter-categoria" class="mb-8 block text-body font-medium text-graphite">Categoría</label>
            <select
                id="filter-categoria"
                x-model="$store.search.category"
                x-on:change="$store.search.search()"
                class="w-full rounded-inputs border border-cloud bg-white px-16 py-12 text-body text-graphite transition hover:border-fog focus:outline-none focus:ring-2 focus:ring-obsidian/20"
            >
                <option value="">Todas las categorías</option>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->slug }}">{{ $categoria->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="filter-calificacion" class="mb-8 block text-body font-medium text-graphite">Calificación mínima</label>
            <select
                id="filter-calificacion"
                x-model.number="$store.search.minRating"
                x-on:change="$store.search.search()"
                class="w-full rounded-inputs border border-cloud bg-white px-16 py-12 text-body text-graphite transition hover:border-fog focus:outline-none focus:ring-2 focus:ring-obsidian/20"
            >
                <option value="0">Cualquiera</option>
                <option value="3">3+ estrellas</option>
                <option value="4">4+ estrellas</option>
                <option value="4.5">4.5+ estrellas</option>
            </select>
        </div>

        <label class="flex items-center gap-8 text-body text-graphite">
            <input
                type="checkbox"
                x-model="$store.search.openNow"
                x-on:change="$store.search.search()"
                class="size-16 rounded-badges border-cloud accent-obsidian"
            >
            Abierto ahora
        </label>

        <button
            type="button"
            x-on:click="$store.search.useMyLocation()"
            class="inline-flex items-center justify-center gap-8 rounded-buttons border border-cloud bg-white px-16 py-12 text-body font-medium text-obsidian transition hover:bg-paper"
        >
            <svg class="size-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <circle cx="12" cy="12" r="3"></circle>
                <path stroke-linecap="round" d="M12 2v3M12 19v3M2 12h3M19 12h3"></path>
            </svg>
            Usar mi ubicación
        </button>
    </div>
</div>
