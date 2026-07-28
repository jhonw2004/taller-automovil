<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TallerPro') — Encuentra tu taller</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-paper font-cosmica text-graphite antialiased">
    <x-marketplace.toast-container />
    <x-marketplace.nav />

    {{--
        Diferencia con `app.blade.php` (016-ui-design-system/plan.md): accesos directos del
        usuario autenticado. Primer consumidor real de este layout — hasta 006-resenas-favoritos
        no existía ninguna ruta que lo usara.
    --}}
    @auth('web')
        <div class="border-b border-cloud bg-white">
            <div class="mx-auto flex max-w-[1200px] items-center gap-24 px-16 py-12 text-body text-fog sm:px-4">
                <span class="text-graphite">Hola, {{ auth('web')->user()->nombre }}</span>
                <a href="{{ route('dashboard') }}" class="text-obsidian hover:underline">Mis favoritos y reseñas</a>
            </div>
        </div>
    @endauth

    @if (session('status'))
        <div class="mx-auto max-w-[1200px] px-16 pt-16 sm:px-4">
            <x-alert type="success">{{ session('status') }}</x-alert>
        </div>
    @endif

    @if (session('error'))
        <div class="mx-auto max-w-[1200px] px-16 pt-16 sm:px-4">
            <x-alert type="error">{{ session('error') }}</x-alert>
        </div>
    @endif

    <main>
        @yield('content')
    </main>

    <x-marketplace.footer />
</body>
</html>
