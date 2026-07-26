@props([
    'variant' => 'primary',
    'disabled' => false,
    'loading' => false,
    'href' => null,
    'type' => 'button',
])

@php
    $variantClass = match ($variant) {
        'ghost' => 'bg-white text-obsidian border border-cloud hover:bg-paper',
        'neutral' => 'bg-paper text-obsidian border border-transparent hover:bg-cloud',
        default => 'bg-obsidian text-white border border-transparent hover:bg-graphite',
    };

    $isDisabled = $disabled || $loading;

    $baseClass = "inline-flex items-center justify-center gap-8 rounded-buttons px-16 py-12 text-body font-medium leading-none transition disabled:cursor-not-allowed disabled:opacity-60 $variantClass";
@endphp

@if ($href && ! $isDisabled)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $baseClass]) }}>
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $type }}"
        @if ($isDisabled) disabled @endif
        {{ $attributes->merge(['class' => $baseClass]) }}
    >
        @if ($loading)
            <svg class="size-16 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        @endif
        {{ $slot }}
    </button>
@endif
