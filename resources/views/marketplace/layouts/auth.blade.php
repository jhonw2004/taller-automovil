<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'TallerAutomóviles') — Encuentra tu taller</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-paper font-cosmica text-graphite antialiased">
    <x-marketplace.nav />

    {{--
        Diferencia con `app.blade.php` (016-ui-design-system/plan.md): accesos directos del
        usuario autenticado. "Mis favoritos"/"Mis reseñas" apuntan al dashboard de 006 —
        no implementado todavía en esta sesión (005+016), las rutas se agregan junto con esa feature.
    --}}
    @auth('web')
        <div class="border-b border-cloud bg-white">
            <div class="mx-auto flex max-w-[1200px] items-center gap-24 px-16 py-12 text-body text-fog sm:px-4">
                <span class="text-graphite">Hola, {{ auth('web')->user()->nombre }}</span>
                <span class="text-ash">Mis favoritos y reseñas — próximamente</span>
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
