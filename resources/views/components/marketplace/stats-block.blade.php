@props(['number', 'label'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-4 text-center sm:items-start sm:text-left']) }}>
    <p class="text-heading font-semibold text-obsidian">{{ $number }}</p>
    <p class="text-body text-fog">{{ $label }}</p>
</div>
