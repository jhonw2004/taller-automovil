{{--
    Icono de corazón lleno/vacío (006-resenas-favoritos). Solo se renderiza si hay sesión
    (`@auth('web')` en el llamador) — un visitante no puede marcar favoritos.
--}}
@props(['tallerId', 'esFavorito' => false])

<button
    type="button"
    x-data="favoritoToggle({{ $tallerId }}, {{ $esFavorito ? 'true' : 'false' }})"
    x-on:click="toggle()"
    x-bind:disabled="loading"
    x-bind:aria-pressed="esFavorito"
    aria-label="Marcar como favorito"
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-full border border-cloud bg-white p-8 transition hover:border-ember disabled:opacity-60']) }}
>
    <svg
        class="size-20"
        x-bind:class="esFavorito ? 'text-ember' : 'text-ash'"
        x-bind:fill="esFavorito ? 'currentColor' : 'none'"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2"
        aria-hidden="true"
    >
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
    </svg>
</button>
