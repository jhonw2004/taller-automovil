<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Taller activo
        </x-slot>

        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="tallerSeleccionado">
                @foreach ($talleres as $taller)
                    <option value="{{ $taller->id }}">{{ $taller->nombre }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </x-filament::section>
</x-filament-widgets::widget>
