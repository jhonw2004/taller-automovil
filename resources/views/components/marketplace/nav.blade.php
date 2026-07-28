@php
    $usuario = auth('web')->user();
@endphp

<header class="sticky top-0 z-40 border-b border-cloud bg-white/90 backdrop-blur">
    <div class="mx-auto flex max-w-[1200px] items-center justify-between gap-16 px-16 py-16 sm:px-4">
        <a href="{{ route('home') }}" class="flex items-center gap-12 text-subheading font-semibold text-obsidian">
            <img src="{{ asset('logo.png') }}" alt="TallerPro" class="h-40 w-40 rounded-icons object-cover">
            TallerPro
        </a>

        <nav class="hidden items-center gap-24 text-body text-graphite sm:flex">
            <a href="{{ route('talleres.buscar') }}" class="hover:text-obsidian">Buscar talleres</a>
            <a href="{{ route('solicitudes.create') }}" class="hover:text-obsidian">Registra tu taller</a>
        </nav>

        <div class="flex items-center gap-12">
            @if ($usuario)
                <a href="{{ route('dashboard') }}" class="hidden text-body text-graphite hover:text-obsidian sm:inline">
                    {{ $usuario->nombre }}
                </a>
                <form method="POST" action="{{ route('auth.logout') }}">
                    @csrf
                    <x-button type="submit" variant="ghost">Cerrar sesión</x-button>
                </form>
            @else
                <x-button :href="route('auth.google.redirect')" variant="ghost">Registrarse</x-button>
                <x-button :href="route('auth.google.redirect')" variant="primary">Iniciar sesión</x-button>
            @endif
        </div>
    </div>
</header>
