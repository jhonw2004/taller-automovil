{{--
    Bloque de búsqueda compartido entre el home (`/`) y `/talleres/buscar` (005-marketplace-busqueda-perfil/plan.md
    + 016-ui-design-system/plan.md: "panel izquierdo 35% filtros+lista, panel derecho 65% mapa").
    No recibe resultados del servidor — `searchForm()` dispara la primera búsqueda al montar.
--}}
@php($categorias = $categorias ?? [])

<div x-data="searchForm()" class="flex flex-col gap-24 lg:flex-row">
    <div class="flex flex-col gap-24 lg:w-[35%]">
        <x-card padding="md">
            <x-marketplace.search-filters :categorias="$categorias" />
        </x-card>

        <div class="flex flex-col gap-16">
            <template x-if="loading">
                <div class="flex flex-col gap-16">
                    <x-skeleton height="h-96" />
                    <x-skeleton height="h-96" />
                    <x-skeleton height="h-96" />
                </div>
            </template>

            <template x-if="!loading && $store.search.error">
                <div x-text="$store.search.error" class="rounded-inputs border border-ember bg-ember/10 px-16 py-12 text-body text-ember"></div>
            </template>

            <template x-if="!loading && !$store.search.error && $store.search.hasSearched && $store.search.results.length === 0">
                <x-empty-state
                    title="No encontramos talleres con esos filtros"
                    description="Amplía el radio de búsqueda o quita algún filtro."
                />
            </template>

            <p x-show="!loading && $store.search.results.length > 0" class="text-caption text-fog">
                <span x-text="$store.search.total"></span> talleres encontrados
            </p>

            <template x-for="taller in $store.search.results" :key="taller.id">
                <a :href="`/talleres/${taller.slug}`" class="block rounded-cards border border-cloud bg-white p-28 hover:border-obsidian/40">
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
                </a>
            </template>
        </div>
    </div>

    <div class="lg:w-[65%]">
        <x-marketplace.map height="h-[420px] lg:h-full lg:min-h-[560px]" />
    </div>
</div>
