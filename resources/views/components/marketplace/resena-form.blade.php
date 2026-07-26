{{--
    "Escribir reseña" / "Mi reseña" en el perfil público de un taller (006-resenas-favoritos).
    Solo se renderiza si hay sesión (`@auth('web')` en el llamador) — un visitante nunca ve
    `name="calificacion"` (ver `tests/Feature/Marketplace/PerfilPublicoTest.php`).
--}}
@props(['tallerId', 'resena' => null])

<div
    x-data="resenaForm({{ $tallerId }}, {{ \Illuminate\Support\Js::from($resena ? [
        'id' => $resena->id,
        'calificacion' => $resena->calificacion,
        'comentario' => $resena->comentario,
    ] : null) }})"
    @star-rating-changed="calificacion = $event.detail"
>
    <x-card padding="md" class="flex flex-col gap-16">
        <h3 class="text-subheading font-semibold text-graphite" x-text="resenaId ? 'Mi reseña' : 'Escribir reseña'"></h3>

        <template x-if="!editing">
            <div class="flex flex-col gap-12">
                {{-- Resumen reactivo: no reusa `<x-marketplace.star-rating>` porque ese componente
                     renderiza las estrellas en el servidor a partir de un valor fijo, no reactivo
                     a `calificacion` (estado de Alpine que cambia tras guardar sin recargar). --}}
                <div class="flex items-center gap-4" role="img" x-bind:aria-label="`Calificación: ${calificacion} de 5`">
                    <template x-for="i in 5" :key="i">
                        <svg
                            class="size-16"
                            x-bind:class="i <= calificacion ? 'text-ember' : 'text-cloud'"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.8L10 14.9l-5.2 2.62.99-5.8-4.21-4.1 5.82-.85L10 1.5z" />
                        </svg>
                    </template>
                </div>
                <p class="text-body text-graphite" x-show="comentario" x-text="comentario"></p>
                <div class="flex gap-12">
                    <x-button type="button" variant="ghost" x-on:click="editar()">Editar</x-button>
                    <x-button type="button" variant="ghost" x-on:click="eliminar()" x-bind:loading="loading">Eliminar</x-button>
                </div>
            </div>
        </template>

        <template x-if="editing">
            <form x-on:submit.prevent="guardar()" class="flex flex-col gap-16">
                <div>
                    <label class="mb-8 block text-body font-medium text-graphite">Calificación <span class="text-ember">*</span></label>
                    <x-marketplace.star-rating :readonly="false" :value="$resena?->calificacion ?? 0" name="calificacion" />
                </div>

                <div>
                    <label for="comentario" class="mb-8 block text-body font-medium text-graphite">Comentario (opcional)</label>
                    <textarea
                        id="comentario"
                        x-model="comentario"
                        rows="3"
                        maxlength="2000"
                        class="w-full rounded-inputs border border-cloud px-16 py-12 text-body text-graphite placeholder:text-ash focus:outline-none focus:ring-2 focus:ring-obsidian/20"
                        placeholder="Cuéntanos tu experiencia en este taller"
                    ></textarea>
                </div>

                <p x-show="error" x-text="error" class="text-caption text-ember"></p>

                <div class="flex gap-12">
                    <x-button type="submit" x-bind:loading="loading">Guardar reseña</x-button>
                    <x-button type="button" variant="ghost" x-show="resenaId" x-on:click="editing = false">Cancelar</x-button>
                </div>
            </form>
        </template>
    </x-card>
</div>
