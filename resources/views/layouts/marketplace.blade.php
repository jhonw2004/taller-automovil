<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Talleres Automotrices - Santa Cruz')</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased">
    <div class="mx-auto max-w-2xl px-4 py-8 sm:py-12">
        @yield('content')
    </div>
</body>
</html>
