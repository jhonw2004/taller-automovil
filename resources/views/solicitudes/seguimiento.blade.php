@extends('marketplace.layouts.app')

@php
    $badges = [
        'PENDIENTE' => 'warning',
        'EN_REVISION' => 'warning',
        'APROBADA' => 'info',
        'COMPLETADA' => 'success',
        'RECHAZADA' => 'danger',
        'CANCELADA' => 'neutral',
    ];
    $labels = [
        'PENDIENTE' => 'Pendiente',
        'EN_REVISION' => 'En revisión',
        'APROBADA' => 'Aprobada',
        'COMPLETADA' => 'Completada',
        'RECHAZADA' => 'Rechazada',
        'CANCELADA' => 'Cancelada',
    ];
    $puedeCancelar = in_array($solicitud->estado, ['PENDIENTE', 'EN_REVISION'], true);
@endphp

@section('title', 'Seguimiento de tu solicitud')

@section('content')
    <div class="mx-auto max-w-[800px] px-16 py-48 sm:px-24 md:py-64 lg:px-16">
        <h1 class="text-heading-sm font-semibold text-graphite">Seguimiento de tu solicitud</h1>
        <p class="mt-8 text-body-lg text-fog">Guarda esta página o su enlace para consultar el estado más adelante.</p>

        <x-card class="mt-32">
            <div class="flex items-center justify-between gap-16">
                <h2 class="text-subheading font-semibold text-graphite">{{ $solicitud->taller_nombre }}</h2>
                <x-badge :type="$badges[$solicitud->estado]">{{ $labels[$solicitud->estado] }}</x-badge>
            </div>

            <dl class="mt-24 grid grid-cols-1 gap-x-16 gap-y-12 text-body sm:grid-cols-2">
                <div>
                    <dt class="font-medium text-ash">Solicitante</dt>
                    <dd class="text-graphite">{{ $solicitud->solicitante_nombre }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-ash">Enviada</dt>
                    <dd class="text-graphite">{{ $solicitud->enviada_at?->translatedFormat('d/m/Y H:i') }}</dd>
                </div>
                @if ($solicitud->estado === 'RECHAZADA' && $solicitud->motivo_rechazo)
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-ash">Motivo de rechazo</dt>
                        <dd class="text-graphite">{{ $solicitud->motivo_rechazo }}</dd>
                    </div>
                @endif
            </dl>

            @if ($puedeCancelar)
                <form method="POST" action="{{ route('solicitudes.cancelar', $solicitud->token_publico) }}" class="mt-24"
                    onsubmit="return confirm('¿Seguro que deseas cancelar esta solicitud?');">
                    @csrf
                    <x-button type="submit" variant="ghost" class="!border-ember !text-ember hover:!bg-ember/10">
                        Cancelar solicitud
                    </x-button>
                </form>
            @endif
        </x-card>

        <h3 class="mt-40 text-subheading font-semibold text-graphite">Historial</h3>
        <ol class="mt-16 space-y-12">
            @foreach ($solicitud->historial as $evento)
                <li class="rounded-cards border border-cloud bg-white p-20 text-body">
                    <div class="flex items-center justify-between gap-16">
                        <span class="font-medium text-graphite">{{ $labels[$evento->estado_nuevo] ?? $evento->estado_nuevo }}</span>
                        <span class="text-caption text-ash">{{ $evento->created_at?->translatedFormat('d/m/Y H:i') }}</span>
                    </div>
                    @if ($evento->observacion)
                        <p class="mt-8 text-fog">{{ $evento->observacion }}</p>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
@endsection
