@extends('marketplace.layouts.app')

@section('title', $taller->nombre)

@php
    $dias = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
@endphp

@section('content')
    <div class="mx-auto max-w-[1200px] px-16 py-32 sm:px-4">
        <div class="flex flex-col gap-24 lg:flex-row">
            <div class="flex-1">
                <div class="flex items-center gap-16">
                    <div class="flex size-80 shrink-0 items-center justify-center overflow-hidden rounded-cards bg-paper">
                        @if ($taller->logo_url)
                            <img src="{{ $taller->logo_url }}" alt="Logo de {{ $taller->nombre }}" class="size-full object-cover">
                        @else
                            <span class="text-heading font-semibold text-ash">{{ mb_substr($taller->nombre, 0, 1) }}</span>
                        @endif
                    </div>

                    <div>
                        <h1 class="text-heading-sm font-semibold text-graphite">{{ $taller->nombre }}</h1>
                        @if ($taller->direccion)
                            <p class="text-body text-fog">{{ $taller->direccion }}</p>
                        @endif
                        <div class="mt-8 flex items-center gap-8">
                            <x-marketplace.star-rating :value="(float) $taller->calificacion_promedio" />
                            <span class="text-caption text-fog">
                                {{ number_format((float) $taller->calificacion_promedio, 1) }} ({{ $taller->cantidad_resenas }} reseñas)
                            </span>
                        </div>
                    </div>
                </div>

                @if ($taller->categorias->isNotEmpty())
                    <div class="mt-24 flex flex-wrap gap-8">
                        @foreach ($taller->categorias as $categoria)
                            <x-badge type="neutral">{{ $categoria->nombre }}</x-badge>
                        @endforeach
                    </div>
                @endif

                @if ($taller->descripcion)
                    <p class="mt-24 text-body text-graphite">{{ $taller->descripcion }}</p>
                @endif

                <div class="mt-24 grid grid-cols-1 gap-8 text-body text-graphite sm:grid-cols-2">
                    @if ($taller->telefono)
                        <p><span class="font-medium">Teléfono:</span> {{ $taller->telefono }}</p>
                    @endif
                    @if ($taller->email)
                        <p><span class="font-medium">Email:</span> {{ $taller->email }}</p>
                    @endif
                </div>

                @if ($taller->horarios->isNotEmpty())
                    <div class="mt-32">
                        <h2 class="text-subheading font-semibold text-graphite">Horarios</h2>
                        <dl class="mt-12 grid grid-cols-1 gap-4 text-body sm:grid-cols-2">
                            @foreach ($taller->horarios as $horario)
                                <div class="flex items-center justify-between rounded-inputs border border-cloud px-16 py-8">
                                    <dt class="text-graphite">{{ $dias[$horario->dia_semana] }}</dt>
                                    <dd class="text-fog">
                                        @if ($horario->cerrado)
                                            Cerrado
                                        @else
                                            {{ \Illuminate\Support\Str::substr($horario->hora_apertura, 0, 5) }}
                                            – {{ \Illuminate\Support\Str::substr($horario->hora_cierre, 0, 5) }}
                                        @endif
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif

                <div class="mt-32">
                    <h2 class="text-subheading font-semibold text-graphite">Reseñas</h2>
                    <div class="mt-12">
                        {{-- 006-resenas-favoritos no está implementado todavía en esta sesión (005+016) —
                             el listado real de reseñas se agrega ahí, reutilizando <x-marketplace.review-card>. --}}
                        <x-empty-state
                            title="Aún no hay reseñas"
                            description="Sé el primero en compartir tu experiencia en este taller."
                        />
                    </div>
                </div>
            </div>

            <div class="lg:w-[420px] lg:shrink-0">
                <div
                    x-data="singleMap({ lat: {{ $taller->lat }}, lon: {{ $taller->lon }} }, {{ \Illuminate\Support\Js::from($taller->nombre) }})"
                    x-init="init()"
                    class="relative h-[280px] w-full overflow-hidden rounded-cards border border-cloud lg:h-[360px]"
                >
                    <div x-ref="container" class="size-full" role="application" aria-label="Ubicación de {{ $taller->nombre }}"></div>
                </div>
            </div>
        </div>

        @if ($similares->isNotEmpty())
            <section class="mt-48 border-t border-cloud pt-32">
                <h2 class="text-subheading font-semibold text-graphite">Talleres similares</h2>
                <div class="mt-24 grid grid-cols-1 gap-16 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($similares as $similar)
                        <x-marketplace.workshop-card :taller="$similar" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
