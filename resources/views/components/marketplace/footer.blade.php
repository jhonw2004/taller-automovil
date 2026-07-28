<footer class="mt-64 border-t border-cloud bg-paper">
    <div class="mx-auto max-w-[1200px] px-16 py-48 sm:px-24 md:py-64 lg:px-16">
        <div class="grid grid-cols-1 gap-32 sm:grid-cols-2 lg:grid-cols-4 lg:gap-40">
            <div class="sm:col-span-2 lg:col-span-2">
                <a href="{{ route('home') }}" class="flex items-center gap-12 text-subheading font-semibold text-obsidian">
                    <img src="{{ asset('logo.png') }}" alt="TallerPro" class="h-32 w-32 rounded-icons object-cover">
                    TallerPro
                </a>
                <p class="mt-16 max-w-sm text-body text-fog">
                    Marketplace de talleres mecánicos en Santa Cruz, Bolivia, con un sistema de gestión para que los talleres administren su operación diaria.
                </p>
            </div>

            <nav>
                <p class="text-caption font-semibold uppercase tracking-wide text-ash">Marketplace</p>
                <ul class="mt-16 space-y-12 text-body text-fog">
                    <li><a href="{{ route('home') }}" class="transition hover:text-obsidian">Inicio</a></li>
                    <li><a href="{{ route('talleres.buscar') }}" class="transition hover:text-obsidian">Buscar talleres</a></li>
                </ul>
            </nav>

            <nav>
                <p class="text-caption font-semibold uppercase tracking-wide text-ash">Para tu taller</p>
                <ul class="mt-16 space-y-12 text-body text-fog">
                    <li><a href="{{ route('solicitudes.create') }}" class="transition hover:text-obsidian">Registra tu taller</a></li>
                    <li><a href="{{ route('solicitudes.create') }}" class="transition hover:text-obsidian">Solicitar cotización</a></li>
                </ul>
            </nav>
        </div>

        <p class="mt-40 border-t border-cloud pt-24 text-caption text-ash">
            &copy; {{ now()->year }} TallerPro — Santa Cruz, Bolivia.
        </p>
    </div>
</footer>
