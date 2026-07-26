@extends('marketplace.layouts.auth')

@section('title', 'Mi cuenta')

@section('content')
    <div class="mx-auto max-w-[1200px] px-16 py-32 sm:px-4">
        <h1 class="text-heading-sm font-semibold text-graphite">Mi cuenta</h1>

        <section class="mt-32">
            <h2 class="text-subheading font-semibold text-graphite">Mis Favoritos</h2>

            @if ($favoritos->isEmpty())
                <div class="mt-12">
                    <x-empty-state
                        title="Sin favoritos todavía"
                        description="Marca talleres como favoritos desde su perfil para verlos aquí."
                    />
                </div>
            @else
                <div class="mt-16 grid grid-cols-1 gap-16 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($favoritos as $favorito)
                        <div class="relative">
                            <x-marketplace.workshop-card :taller="$favorito->taller" />
                            <div class="absolute right-16 top-16">
                                <x-marketplace.favorito-button :taller-id="$favorito->taller_id" :es-favorito="true" />
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="mt-48 border-t border-cloud pt-32">
            <h2 class="text-subheading font-semibold text-graphite">Mis Reseñas</h2>

            @if ($resenas->isEmpty())
                <div class="mt-12">
                    <x-empty-state
                        title="Aún no has escrito reseñas"
                        description="Visita el perfil de un taller para compartir tu experiencia."
                    />
                </div>
            @else
                <div class="mt-16 flex flex-col gap-24">
                    @foreach ($resenas as $resena)
                        <div>
                            <a
                                href="{{ route('talleres.show', $resena->taller->slug) }}"
                                class="text-body font-medium text-graphite hover:text-obsidian"
                            >
                                {{ $resena->taller->nombre }}
                            </a>
                            <div class="mt-8">
                                <x-marketplace.resena-form :taller-id="$resena->taller_id" :resena="$resena" />
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
