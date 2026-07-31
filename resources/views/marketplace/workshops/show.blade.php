@extends('marketplace.layouts.app')

@section('title', $taller->nombre)

@php
    $dias = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
    $hoyIso = now()->dayOfWeekIso;
    $abiertoAhora = $taller->estaAbiertoAhora();
@endphp

@section('content')
    {{-- Cabecera: identidad, estado, calificación, dirección y acciones rápidas --}}
    <div class="border-b border-cloud bg-white">
        <div class="mx-auto max-w-[1200px] px-16 py-32 sm:px-24 lg:px-16">
            <a href="{{ route('talleres.buscar') }}" class="inline-flex items-center gap-4 text-caption font-medium text-fog transition hover:text-obsidian">
                <svg class="size-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6"></path>
                </svg>
                Volver a la búsqueda
            </a>

            <div class="mt-20 flex flex-col gap-20 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex flex-col gap-16 sm:flex-row sm:items-center">
                    <div class="flex size-80 shrink-0 items-center justify-center overflow-hidden rounded-cards border border-cloud bg-paper">
                        @if ($taller->logo_url)
                            <img src="{{ $taller->logo_url }}" alt="Logo de {{ $taller->nombre }}" class="size-full object-cover">
                        @else
                            <span class="text-heading font-semibold text-ash">{{ mb_substr($taller->nombre, 0, 1) }}</span>
                        @endif
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-12">
                            <h1 class="text-heading-sm font-semibold text-graphite">{{ $taller->nombre }}</h1>
                            <x-badge :type="$abiertoAhora ? 'success' : 'neutral'" size="sm">
                                {{ $abiertoAhora ? 'Abierto ahora' : 'Cerrado' }}
                            </x-badge>
                        </div>

                        <div class="mt-8 flex flex-wrap items-center gap-8">
                            <x-marketplace.star-rating :value="(float) $taller->calificacion_promedio" />
                            <span class="text-caption text-fog">
                                {{ number_format((float) $taller->calificacion_promedio, 1) }} ({{ $taller->cantidad_resenas }} reseñas)
                            </span>
                        </div>

                        @if ($taller->direccion)
                            <div class="mt-8 flex items-center gap-8 text-body text-fog">
                                <svg class="size-16 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-6.5 7-11.5A7 7 0 0 0 5 9.5C5 14.5 12 21 12 21z"></path>
                                    <circle cx="12" cy="9.5" r="2.5"></circle>
                                </svg>
                                <span>{{ $taller->direccion }}</span>
                            </div>
                        @endif

                        @if ($taller->categorias->isNotEmpty())
                            <div class="mt-12 flex flex-wrap gap-8">
                                @foreach ($taller->categorias as $categoria)
                                    <x-badge type="neutral" size="sm">{{ $categoria->nombre }}</x-badge>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                @auth('web')
                    <x-marketplace.favorito-button :taller-id="$taller->id" :es-favorito="$esFavorito" class="self-start" />
                @endauth
            </div>

            <div class="mt-24 flex flex-wrap gap-12">
                @if ($taller->telefono)
                    <x-button href="tel:{{ $taller->telefono }}" variant="primary">
                        <svg class="size-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5c0-1.1.9-2 2-2h2.28a1 1 0 0 1 .97.76l1 4a1 1 0 0 1-.5 1.11L7 10c1 2.5 3.5 5 6 6l1.13-1.75a1 1 0 0 1 1.11-.5l4 1a1 1 0 0 1 .76.97V19c0 1.1-.9 2-2 2h-1C9.16 21 3 14.84 3 7V5z"></path>
                        </svg>
                        Llamar
                    </x-button>
                @endif

                @if ($taller->email)
                    <x-button href="mailto:{{ $taller->email }}" variant="ghost">
                        <svg class="size-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18v12H3z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="m3 7 9 6 9-6"></path>
                        </svg>
                        Enviar email
                    </x-button>
                @endif

                @if ($taller->lat && $taller->lon)
                    <x-button
                        href="https://www.google.com/maps/dir/?api=1&destination={{ $taller->lat }},{{ $taller->lon }}"
                        variant="ghost"
                        target="_blank"
                        rel="noopener"
                    >
                        <svg class="size-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 11l18-8-8 18-2-8-8-2z"></path>
                        </svg>
                        Cómo llegar
                    </x-button>
                @endif
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-[1200px] px-16 py-32 sm:px-24 lg:px-16">
        <div class="flex flex-col gap-24 lg:flex-row lg:items-start">
            <div class="flex flex-1 flex-col gap-24">
                @if ($taller->descripcion)
                    <x-card padding="lg" class="flex flex-col gap-12">
                        <h2 class="text-subheading font-semibold text-graphite">Sobre este taller</h2>
                        <p class="text-body text-graphite">{{ $taller->descripcion }}</p>
                    </x-card>
                @endif

                @if ($taller->horarios->isNotEmpty())
                    <x-card padding="lg">
                        <div class="flex items-center gap-8">
                            <svg class="size-20 text-fog" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"></circle>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3"></path>
                            </svg>
                            <h2 class="text-subheading font-semibold text-graphite">Horarios</h2>
                        </div>

                        <dl class="mt-12 divide-y divide-cloud">
                            @foreach ($taller->horarios as $horario)
                                @php($esHoy = $horario->dia_semana === $hoyIso)
                                <div class="flex items-center justify-between gap-16 py-12">
                                    <dt class="flex items-center gap-8 text-body {{ $esHoy ? 'font-semibold text-obsidian' : 'text-graphite' }}">
                                        {{ $dias[$horario->dia_semana] }}
                                        @if ($esHoy)
                                            <x-badge type="accent" size="sm">Hoy</x-badge>
                                        @endif
                                    </dt>
                                    <dd class="text-body {{ $horario->cerrado ? 'text-fog' : ($esHoy ? 'font-semibold text-obsidian' : 'text-graphite') }}">
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
                    </x-card>
                @endif

                <div>
                    <h2 class="text-subheading font-semibold text-graphite">Reseñas</h2>

                    @auth('web')
                        <div class="mt-12">
                            <x-marketplace.resena-form :taller-id="$taller->id" :resena="$miResena" />
                        </div>
                    @endauth

                    <div class="mt-16 flex flex-col gap-12">
                        @forelse ($resenas as $resena)
                            <x-marketplace.review-card :resena="$resena" />
                        @empty
                            @unless ($miResena)
                                <x-empty-state
                                    title="Aún no hay reseñas"
                                    description="Sé el primero en compartir tu experiencia en este taller."
                                />
                            @endunless
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-24 lg:w-[380px] lg:shrink-0">
                <div class="relative h-[280px] w-full overflow-hidden rounded-cards border border-cloud lg:h-[320px]">
                    <div
                        x-data="singleMap({ lat: {{ $taller->lat }}, lon: {{ $taller->lon }} }, {{ \Illuminate\Support\Js::from($taller->nombre) }})"
                        x-init="init()"
                        class="relative z-0 size-full"
                    >
                        <div x-ref="container" class="size-full" role="application" aria-label="Ubicación de {{ $taller->nombre }}"></div>

                        <div
                            x-show="loadingTiles"
                            x-transition
                            class="absolute inset-0 z-[1000] flex items-center justify-center bg-white/70"
                        >
                            <svg class="size-32 animate-spin text-obsidian" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                @if ($taller->telefono || $taller->email || $taller->direccion)
                    <x-card padding="lg" class="flex flex-col gap-16">
                        <h2 class="text-subheading font-semibold text-graphite">Contacto</h2>

                        <div class="flex flex-col gap-12 text-body text-graphite">
                            @if ($taller->direccion)
                                <div class="flex items-start gap-12">
                                    <svg class="mt-4 size-16 shrink-0 text-fog" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-6.5 7-11.5A7 7 0 0 0 5 9.5C5 14.5 12 21 12 21z"></path>
                                        <circle cx="12" cy="9.5" r="2.5"></circle>
                                    </svg>
                                    <span>{{ $taller->direccion }}</span>
                                </div>
                            @endif

                            @if ($taller->telefono)
                                <a href="tel:{{ $taller->telefono }}" class="flex items-center gap-12 transition hover:text-obsidian">
                                    <svg class="size-16 shrink-0 text-fog" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5c0-1.1.9-2 2-2h2.28a1 1 0 0 1 .97.76l1 4a1 1 0 0 1-.5 1.11L7 10c1 2.5 3.5 5 6 6l1.13-1.75a1 1 0 0 1 1.11-.5l4 1a1 1 0 0 1 .76.97V19c0 1.1-.9 2-2 2h-1C9.16 21 3 14.84 3 7V5z"></path>
                                    </svg>
                                    <span>{{ $taller->telefono }}</span>
                                </a>
                            @endif

                            @if ($taller->email)
                                <a href="mailto:{{ $taller->email }}" class="flex items-center gap-12 transition hover:text-obsidian">
                                    <svg class="size-16 shrink-0 text-fog" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18v12H3z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 7 9 6 9-6"></path>
                                    </svg>
                                    <span class="truncate">{{ $taller->email }}</span>
                                </a>
                            @endif
                        </div>
                    </x-card>
                @endif
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
