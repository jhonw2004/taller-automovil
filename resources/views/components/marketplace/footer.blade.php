<footer class="mt-64 border-t border-cloud bg-paper">
    <div class="mx-auto max-w-[1200px] px-16 py-32 sm:px-4">
        <div class="flex flex-col gap-16 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-subheading font-semibold text-obsidian">TallerAutomóviles</p>

            <nav class="flex flex-wrap items-center gap-16 text-body text-fog">
                <a href="{{ route('home') }}" class="hover:text-obsidian">Inicio</a>
                <a href="{{ route('talleres.buscar') }}" class="hover:text-obsidian">Buscar talleres</a>
                <a href="{{ route('solicitudes.create') }}" class="hover:text-obsidian">Registra tu taller</a>
            </nav>
        </div>

        <p class="mt-24 text-caption text-ash">
            &copy; {{ now()->year }} TallerAutomóviles — Santa Cruz, Bolivia.
        </p>
    </div>
</footer>
