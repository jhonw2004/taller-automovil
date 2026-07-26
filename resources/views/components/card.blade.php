@props([
    'padding' => 'md',
    'border' => true,
    'shadow' => false,
])

@php
    $paddingClass = match ($padding) {
        'sm' => 'p-16',
        'lg' => 'p-32',
        default => 'p-28',
    };
@endphp

<div {{ $attributes->merge([
    'class' => "rounded-cards bg-white $paddingClass " . ($border ? 'border border-cloud' : '') . ' ' . ($shadow ? 'shadow-md' : ''),
]) }}>
    {{ $slot }}
</div>
