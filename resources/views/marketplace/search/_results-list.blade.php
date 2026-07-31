{{--
    Lista de resultados compartida entre el drawer (desktop/tablet) y la hoja inferior (móvil) de
    /talleres/buscar (019-mapa-busqueda-ux/plan.md §2). Vive dentro del `x-data="searchForm()"`
    del partial padre (`map-experience.blade.php`) — no declara su propio store.

    Click/tap en un resultado ya no navega directo al perfil (comportamiento anterior de
    `search-experience.blade.php`): selecciona el taller (centra el mapa + abre la card flotante),
    igual que hacer click en su marcador (019-mapa-busqueda-ux/spec.md). "Ver perfil completo"
    vive en la card flotante.
--}}
<template x-if="$store.search.loading">
    <div class="flex flex-col gap-16">
        <x-skeleton height="h-96" />
        <x-skeleton height="h-96" />
        <x-skeleton height="h-96" />
    </div>
</template>

<template x-if="!$store.search.loading && $store.search.error">
    <div x-text="$store.search.error" class="rounded-inputs border border-ember bg-ember/10 px-16 py-12 text-body text-ember"></div>
</template>

<template x-if="!$store.search.loading && !$store.search.error && $store.search.hasSearched && $store.search.results.length === 0">
    <x-empty-state
        title="No encontramos talleres con esos filtros"
        description="Prueba con otra categoría, otra calificación o quita algún filtro."
    />
</template>

<div
    class="flex flex-col gap-12"
    x-on:keydown.down.prevent="$el.querySelector('button:focus')?.nextElementSibling?.focus()"
    x-on:keydown.up.prevent="$el.querySelector('button:focus')?.previousElementSibling?.focus()"
>
    <template x-for="taller in $store.search.results" :key="taller.id">
        <button
            type="button"
            x-on:click="$store.search.select(taller.id)"
            x-on:mouseenter="window.dispatchEvent(new CustomEvent('taller:hover', { detail: { id: taller.id } }))"
            x-on:mouseleave="window.dispatchEvent(new CustomEvent('taller:hover', { detail: { id: null } }))"
            class="w-full rounded-cards border p-20 text-left transition duration-300 hover:-translate-y-4 hover:border-obsidian/40 hover:shadow-md"
            :class="$store.search.selected === taller.id ? 'border-obsidian bg-paper' : 'border-cloud bg-white'"
        >
            <div class="flex items-start justify-between gap-16">
                <div class="min-w-0">
                    <p class="truncate text-subheading font-semibold text-graphite" x-text="taller.nombre"></p>
                    <p class="mt-4 truncate text-caption text-fog" x-text="taller.descripcion_corta ?? ''"></p>
                </div>

                <span
                    class="shrink-0 rounded-badges border px-8 py-4 text-caption font-medium"
                    :class="taller.abierto_ahora ? 'border-emerald-500 text-emerald-700 bg-emerald-50' : 'border-cloud text-iron bg-paper'"
                    x-text="taller.abierto_ahora ? 'Abierto ahora' : 'Cerrado'"
                ></span>
            </div>

            <div class="mt-16 flex flex-wrap gap-8" x-show="taller.categorias?.length">
                <template x-for="categoria in taller.categorias" :key="categoria">
                    <span class="rounded-badges border border-cloud bg-paper px-8 py-4 text-caption text-iron" x-text="categoria"></span>
                </template>
            </div>

            <div class="mt-16 flex items-center justify-between text-caption text-fog">
                <span>&#9733; <span x-text="Number(taller.calificacion_promedio).toFixed(1)"></span> (<span x-text="taller.cantidad_resenas"></span>)</span>
                <span x-show="taller.distancia_km !== null" x-text="taller.distancia_km + ' km'"></span>
            </div>
        </button>
    </template>
</div>
