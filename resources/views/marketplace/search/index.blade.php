@extends('marketplace.layouts.app')

@section('title', 'Buscar talleres')

@section('content')
    <div class="mx-auto max-w-[1200px] px-16 py-32 sm:px-4">
        <h1 class="text-heading-sm font-semibold text-graphite">Buscar talleres</h1>
        <p class="mt-8 text-body text-fog">Filtra por categoría, calificación, radio de búsqueda u horario.</p>

        <div class="mt-24">
            @include('marketplace.partials.search-experience', ['categorias' => $categorias])
        </div>
    </div>
@endsection
