@props([
    'id',
    'title' => null,
    'width' => 'md',
])

@php
    $widthClass = match ($width) {
        'sm' => 'max-w-sm',
        'lg' => 'max-w-2xl',
        default => 'max-w-md',
    };
@endphp

<div
    x-data="{ open: false }"
    x-show="open"
    x-on:open-modal.window="if ($event.detail === '{{ $id }}') open = true"
    x-on:close-modal.window="if (!$event.detail || $event.detail === '{{ $id }}') open = false"
    x-on:keydown.escape.window="open = false"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center px-16"
    style="display: none;"
>
    <div class="fixed inset-0 bg-obsidian/50" x-on:click="open = false" aria-hidden="true"></div>

    <div
        x-show="open"
        x-transition
        class="relative w-full {{ $widthClass }} rounded-cards border border-cloud bg-white p-28"
        role="dialog"
        aria-modal="true"
        @if ($title) aria-labelledby="{{ $id }}-title" @endif
    >
        @if ($title)
            <h2 id="{{ $id }}-title" class="mb-16 text-subheading font-semibold text-graphite">{{ $title }}</h2>
        @endif

        {{ $slot }}
    </div>
</div>
