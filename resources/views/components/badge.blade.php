@props([
    'type' => 'neutral',
    'size' => 'md',
])

@php
    $typeClass = match ($type) {
        'success' => 'badge-success',
        'warning' => 'badge-warning',
        'danger' => 'badge-danger',
        'info' => 'badge-info',
        'accent' => 'badge-accent',
        default => 'badge-neutral',
    };

    $sizeClass = $size === 'sm' ? 'text-caption px-8 py-4' : 'text-body px-12 py-8 text-[13px]';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-4 rounded-badges border font-medium leading-none $typeClass $sizeClass"]) }}>
    {{ $slot }}
</span>
