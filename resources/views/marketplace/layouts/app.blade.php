<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TallerAutomóviles') — Encuentra tu taller</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-paper font-cosmica text-graphite antialiased">
    <x-marketplace.toast-container />
    <x-marketplace.nav />

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
