@extends('marketplace.layouts.app')

@section('title', 'Inicio')

@section('content')
    <section class="bg-obsidian text-white">
        <div class="mx-auto max-w-[1200px] px-16 py-64 sm:px-4 sm:py-80">
            <h1 class="max-w-2xl text-heading-lg font-semibold sm:text-display">
                Encuentra el taller mecánico ideal en Santa Cruz
            </h1>
            <p class="mt-16 max-w-xl text-body-lg text-mist">
                Compara talleres cercanos por categoría, calificación y horario. Sin registrarte.
            </p>

            <form action="{{ route('talleres.buscar') }}" method="GET" class="mt-32 flex max-w-xl flex-col gap-12 sm:flex-row">
                <input
                    type="text"
                    name="q"
                    placeholder="Busca por nombre de taller"
                    class="w-full rounded-inputs border border-white/20 bg-white/10 px-16 py-12 text-body text-white placeholder:text-mist focus:outline-none focus:ring-2 focus:ring-white/40"
                >
                <x-button type="submit" variant="primary" class="!bg-white !text-obsidian hover:!bg-paper">
                    Buscar talleres
                </x-button>
            </form>

            <div class="mt-16">
                <x-button :href="route('solicitudes.create')" variant="ghost" class="!border-white/30 !bg-transparent !text-white hover:!bg-white/10">
                    Registra tu taller
                </x-button>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-[1200px] px-16 sm:px-4">
        @if ($categorias->isNotEmpty())
            <section class="py-48">
                <h2 class="text-heading-sm font-semibold text-graphite">Categorías</h2>
                <div class="mt-24 grid grid-cols-2 gap-16 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($categorias as $categoria)
                        <a
                            href="{{ route('talleres.buscar', ['categoria' => $categoria->slug]) }}"
                            class="rounded-cards border border-cloud bg-white p-20 text-center text-body font-medium text-graphite hover:border-obsidian/40"
                        >
                            {{ $categoria->nombre }}
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="border-t border-cloud py-48">
            <div class="grid grid-cols-1 gap-24 sm:grid-cols-3">
                <x-marketplace.stats-block :number="$stats['talleres']" label="Talleres publicados" />
                <x-marketplace.stats-block :number="$stats['categorias']" label="Categorías" />
                <x-marketplace.stats-block :number="number_format($stats['calificacionPromedio'], 1)" label="Calificación promedio" />
            </div>
        </section>

    </div>

    {{-- Breakthrough image (016-ui-design-system/plan.md): separador visual full-bleed. --}}
    <section class="bg-graphite py-64 text-center text-white">
        <div class="mx-auto max-w-2xl px-16 sm:px-4">
            <p class="text-heading-sm font-semibold">Talleres verificados, cerca de donde estás</p>
            <p class="mt-12 text-body-lg text-mist">
                Cada taller pasa por una revisión antes de aparecer en el mapa público.
            </p>
        </div>
    </section>

    <div class="mx-auto max-w-[1200px] px-16 sm:px-4">
        <section class="border-t border-cloud py-48">
            <h2 class="text-heading-sm font-semibold text-graphite">Talleres cerca de ti</h2>
            <p class="mt-8 text-body text-fog">Activa tu ubicación o filtra por categoría para encontrar el taller más cercano.</p>

            <div class="mt-24">
                @include('marketplace.partials.search-experience', ['categorias' => $categorias])
            </div>
        </section>
    </div>
@endsection
