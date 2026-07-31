<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'TallerPro')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('logoapp.svg') }}" media="(prefers-color-scheme: light)">
    <link rel="icon" type="image/svg+xml" href="{{ asset('logoappdark.svg') }}" media="(prefers-color-scheme: dark)">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-paper font-cosmica text-graphite antialiased">
    @yield('content')
</body>
</html>
