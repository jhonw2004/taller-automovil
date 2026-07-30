@props([
    'label' => null,
    'name',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'placeholder' => null,
])

<div>
    @if ($label)
        <label for="{{ $name }}" class="mb-8 block text-body font-medium text-graphite">
            {{ $label }}
            @if ($required)
                <span class="text-ember">*</span>
            @endif
        </label>
    @endif

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        @if ($required) required @endif
        @if ($errors->has($name)) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
        {{ $attributes->merge([
            'class' => 'w-full rounded-inputs border px-16 py-12 text-body text-graphite transition placeholder:text-ash focus:outline-none focus:ring-2 focus:ring-obsidian/20 disabled:cursor-not-allowed disabled:bg-paper disabled:text-fog ' .
                ($errors->has($name) ? 'border-ember' : 'border-cloud hover:border-fog'),
        ]) }}
    />

    @error($name)
        <p id="{{ $name }}-error" class="mt-4 text-caption text-ember">{{ $message }}</p>
    @enderror
</div>
