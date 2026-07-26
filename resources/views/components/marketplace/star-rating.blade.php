@props([
    'value' => 0,
    'max' => 5,
    'readonly' => true,
    'name' => null,
])

@php
    $value = (float) $value;
@endphp

@if ($readonly)
    <div class="flex items-center gap-4" role="img" aria-label="Calificación: {{ $value }} de {{ $max }}">
        @for ($i = 1; $i <= $max; $i++)
            <svg
                class="size-16 {{ $i <= round($value) ? 'text-ember' : 'text-cloud' }}"
                viewBox="0 0 20 20"
                fill="currentColor"
                aria-hidden="true"
            >
                <path d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.8L10 14.9l-5.2 2.62.99-5.8-4.21-4.1 5.82-.85L10 1.5z" />
            </svg>
        @endfor
    </div>
@else
    <div x-data="starRating({{ $value ?: 0 }})" class="flex items-center gap-4">
        @for ($i = 1; $i <= $max; $i++)
            <button
                type="button"
                x-on:click="set({{ $i }}); $dispatch('star-rating-changed', {{ $i }})"
                x-on:mouseenter="hover({{ $i }})"
                x-on:mouseleave="hover(0)"
                :aria-pressed="rating === {{ $i }}"
                aria-label="{{ $i }} estrellas"
                class="focus:outline-none"
            >
                <svg
                    class="size-24 transition-colors"
                    :class="(hovered || rating) >= {{ $i }} ? 'text-ember' : 'text-cloud'"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    aria-hidden="true"
                >
                    <path d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.8L10 14.9l-5.2 2.62.99-5.8-4.21-4.1 5.82-.85L10 1.5z" />
                </svg>
            </button>
        @endfor

        @if ($name)
            <input type="hidden" name="{{ $name }}" :value="rating">
        @endif
    </div>
@endif
