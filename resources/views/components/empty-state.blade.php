@props([
    'icon' => null,
    'title',
    'description' => null,
    'actionLabel' => null,
    'actionUrl' => null,
])

<div class="flex flex-col items-center gap-8 rounded-cards border border-dashed border-cloud px-24 py-48 text-center">
    <div class="flex size-40 items-center justify-center rounded-full bg-paper text-fog">
        {{ $icon ?? '' }}
        @if (! $icon)
            <svg class="size-24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        @endif
    </div>

    <p class="text-subheading font-semibold text-graphite">{{ $title }}</p>

    @if ($description)
        <p class="max-w-sm text-body text-fog">{{ $description }}</p>
    @endif

    @if ($actionLabel && $actionUrl)
        <x-button :href="$actionUrl" variant="primary" class="mt-8">
            {{ $actionLabel }}
        </x-button>
    @endif
</div>
