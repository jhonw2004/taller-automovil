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
@php($layoutVariant = trim($__env->yieldContent('layout_variant')))
<body class="bg-paper font-cosmica text-graphite antialiased @if ($layoutVariant === 'app-shell') flex h-dvh flex-col overflow-hidden @endif">
    <x-marketplace.toast-container />
    <x-marketplace.nav />

    @unless ($layoutVariant === 'app-shell')
        @if (session('status'))
            <div class="mx-auto max-w-[1200px] px-16 pt-16 sm:px-24 lg:px-16">
                <x-alert type="success">{{ session('status') }}</x-alert>
            </div>
        @endif

        @if (session('error'))
            <div class="mx-auto max-w-[1200px] px-16 pt-16 sm:px-24 lg:px-16">
                <x-alert type="error">{{ session('error') }}</x-alert>
            </div>
        @endif
    @endunless

    <main class="@if ($layoutVariant === 'app-shell') min-h-0 flex-1 @endif">
        @yield('content')
    </main>

    @unless ($layoutVariant === 'app-shell')
        <x-marketplace.footer />
    @endunless
</body>
</html>
