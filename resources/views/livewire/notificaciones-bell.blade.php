<div>
    <x-filament::dropdown placement="bottom-end">
        <x-slot name="trigger">
            <button type="button" class="fi-icon-btn relative p-2">
                <x-filament::icon icon="heroicon-o-bell" class="h-6 w-6 text-gray-500 dark:text-gray-400" />

                @if ($this->cantidadNoLeidas > 0)
                    <span class="absolute top-0 right-0 flex h-4 min-w-4 items-center justify-center rounded-full bg-danger-500 px-1 text-xs font-medium text-white">
                        {{ $this->cantidadNoLeidas }}
                    </span>
                @endif
            </button>
        </x-slot>

        <x-filament::dropdown.list class="max-h-96 w-80 overflow-y-auto">
            @forelse ($this->notificaciones as $notificacion)
                <div wire:key="notificacion-{{ $notificacion->id }}" class="flex items-start justify-between gap-2 px-3 py-2">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $notificacion->titulo }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $notificacion->mensaje }}</p>
                    </div>

                    <button
                        type="button"
                        wire:click="marcarLeida({{ $notificacion->id }})"
                        class="shrink-0 text-xs text-primary-600 hover:underline dark:text-primary-400"
                    >
                        Marcar leída
                    </button>
                </div>
            @empty
                <div class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                    Sin notificaciones nuevas.
                </div>
            @endforelse
        </x-filament::dropdown.list>
    </x-filament::dropdown>
</div>
