@props(['categorias' => []])

{{--
    Formulario de filtros del sidebar de /talleres/buscar: sin "chips"/tags (feedback explícito del
    usuario), controles etiquetados de toda la vida (input, select, checkbox, slider). Sin
    bordes redondeados (tampoco pedido) — simplemente no se aplica ninguna clase `rounded-*` aquí,
    a diferencia del resto del sitio que sí usa los tokens de `016-ui-design-system`.
--}}
<div class="flex flex-col gap-16">
    <div>
        <label for="filter-q" class="mb-8 block text-body font-medium text-graphite">Buscar por nombre</label>
        <input
            id="filter-q"
            type="text"
            x-model.debounce.400ms="$store.search.query"
            x-on:input="$store.search.search()"
            placeholder="Ej. Taller El Rápido"
            class="w-full border border-cloud px-16 py-12 text-body text-graphite placeholder:text-ash focus:outline-none focus:ring-2 focus:ring-obsidian/20"
        >
    </div>

    <div>
        <label for="filter-categoria" class="mb-8 block text-body font-medium text-graphite">Categoría</label>
        <select
            id="filter-categoria"
            x-model="$store.search.category"
            x-on:change="$store.search.search()"
            class="w-full border border-cloud bg-white px-16 py-12 text-body text-graphite focus:outline-none focus:ring-2 focus:ring-obsidian/20"
        >
            <option value="">Todas las categorías</option>
            @foreach ($categorias as $categoria)
                <option value="{{ $categoria->slug }}">{{ $categoria->nombre }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="filter-radio" class="mb-8 block text-body font-medium text-graphite">
            Radio de búsqueda: <span x-text="$store.search.radius"></span> km
        </label>
        <input
            id="filter-radio"
            type="range"
            min="1"
            max="50"
            x-model.number="$store.search.radius"
            x-on:change="$store.search.search()"
            class="w-full accent-obsidian"
        >
    </div>

    <div>
        <label for="filter-calificacion" class="mb-8 block text-body font-medium text-graphite">Calificación mínima</label>
        <select
            id="filter-calificacion"
            x-model.number="$store.search.minRating"
            x-on:change="$store.search.search()"
            class="w-full border border-cloud bg-white px-16 py-12 text-body text-graphite focus:outline-none focus:ring-2 focus:ring-obsidian/20"
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
            class="size-16 border-cloud accent-obsidian"
        >
        Abierto ahora
    </label>

    <button
        type="button"
        x-on:click="$store.search.useMyLocation()"
        class="inline-flex items-center justify-center gap-8 border border-cloud bg-white px-16 py-12 text-body font-medium text-obsidian hover:bg-paper"
    >
        Usar mi ubicación
    </button>
</div>
