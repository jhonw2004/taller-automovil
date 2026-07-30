{{--
    Card flotante de detalle en móvil (019-mapa-busqueda-ux/plan.md §5): equivalente móvil del
    popup de Leaflet que se usa en desktop/tablet (HTML generado en `resources/js/alpine/components/map.js`,
    `popoverHtml()` — no se comparte el marcado literal porque Leaflet exige un string, no un
    componente Blade, pero el contenido mostrado es el mismo). El padre controla cuándo se
    muestra este bloque con `x-show`.
--}}
<div x-data="{ get taller() { return $store.search.selectedTaller() } }">
    <template x-if="taller">
        <div class="relative p-20">
            <button
                type="button"
                x-on:click="$store.search.selected = null"
                aria-label="Cerrar"
                class="absolute right-16 top-16 flex size-28 items-center justify-center rounded-full text-fog hover:bg-paper hover:text-obsidian"
            >
                <svg class="size-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"></path>
                </svg>
            </button>

            <p class="pr-24 text-subheading font-semibold text-graphite" x-text="taller.nombre"></p>

            <div class="mt-8 flex flex-wrap gap-6" x-show="taller.categorias?.length">
                <template x-for="categoria in (taller.categorias ?? []).slice(0, 3)" :key="categoria">
                    <span class="rounded-badges border border-cloud bg-paper px-8 py-4 text-caption text-iron" x-text="categoria"></span>
                </template>
            </div>

            <div class="mt-12 flex items-center justify-between gap-8 text-caption text-fog">
                <span>&#9733; <span x-text="Number(taller.calificacion_promedio ?? 0).toFixed(1)"></span> (<span x-text="taller.cantidad_resenas ?? 0"></span>)</span>
                <span
                    class="rounded-badges border px-8 py-4 font-medium"
                    :class="taller.abierto_ahora ? 'border-emerald-500 text-emerald-700 bg-emerald-50' : 'border-cloud text-iron bg-paper'"
                    x-text="taller.abierto_ahora ? 'Abierto ahora' : 'Cerrado'"
                ></span>
            </div>

            <p class="mt-8 text-caption text-fog" x-show="taller.direccion" x-text="taller.direccion"></p>

            <div class="mt-16 flex items-center justify-between gap-8">
                <span class="text-caption text-fog" x-show="taller.distancia_km !== null" x-text="taller.distancia_km + ' km'"></span>
                <a :href="`/talleres/${taller.slug}`" class="text-caption font-semibold text-obsidian hover:text-ember">
                    Ver perfil completo &rarr;
                </a>
            </div>
        </div>
    </template>
</div>
