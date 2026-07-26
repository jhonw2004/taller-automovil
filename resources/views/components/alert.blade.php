@props([
    'type' => 'info',
    'dismissible' => false,
])

@php
    $typeClass = match ($type) {
        'success' => 'border-emerald-500 bg-emerald-50 text-emerald-700',
        'error' => 'border-ember bg-ember/10 text-ember',
        'warning' => 'border-amber-400 bg-amber-50 text-amber-700',
        default => 'border-steel bg-paper text-steel',
    };
@endphp

<div
    @if ($dismissible) x-data="{ show: true }" x-show="show" @endif
    {{ $attributes->merge(['class' => "flex items-start gap-12 rounded-inputs border px-16 py-12 text-body $typeClass"]) }}
>
    <div class="flex-1">
        {{ $slot }}
    </div>

    @if ($dismissible)
        <button type="button" @click="show = false" class="text-current/60 hover:text-current" aria-label="Cerrar">
            <svg class="size-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</div>
