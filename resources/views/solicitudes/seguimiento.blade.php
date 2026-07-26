@extends('layouts.marketplace')

@php
    $badges = [
        'PENDIENTE' => 'bg-amber-100 text-amber-800',
        'EN_REVISION' => 'bg-amber-100 text-amber-800',
        'APROBADA' => 'bg-blue-100 text-blue-800',
        'COMPLETADA' => 'bg-green-100 text-green-800',
        'RECHAZADA' => 'bg-red-100 text-red-800',
        'CANCELADA' => 'bg-zinc-200 text-zinc-700',
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

@section('title', 'Seguimiento de tu solicitud - Talleres Automotrices')

@section('content')
    <h1 class="text-2xl font-semibold tracking-tight">Seguimiento de tu solicitud</h1>
    <p class="mt-1 text-sm text-zinc-600">Guarda esta página o su enlace para consultar el estado más adelante.</p>

    @if (session('status'))
        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="mt-6 rounded-xl border border-zinc-200 bg-white p-5">
        <div class="flex items-center justify-between">
            <h2 class="font-medium">{{ $solicitud->taller_nombre }}</h2>
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $badges[$solicitud->estado] }}">
                {{ $labels[$solicitud->estado] }}
            </span>
        </div>

        <dl class="mt-4 grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-2">
            <div>
                <dt class="font-medium text-zinc-500">Solicitante</dt>
                <dd>{{ $solicitud->solicitante_nombre }}</dd>
            </div>
            <div>
                <dt class="font-medium text-zinc-500">Enviada</dt>
                <dd>{{ $solicitud->enviada_at?->translatedFormat('d/m/Y H:i') }}</dd>
            </div>
            @if ($solicitud->estado === 'RECHAZADA' && $solicitud->motivo_rechazo)
                <div class="sm:col-span-2">
                    <dt class="font-medium text-zinc-500">Motivo de rechazo</dt>
                    <dd>{{ $solicitud->motivo_rechazo }}</dd>
                </div>
            @endif
        </dl>

        @if ($puedeCancelar)
            <form method="POST" action="{{ route('solicitudes.cancelar', $solicitud->token_publico) }}" class="mt-4"
                onsubmit="return confirm('¿Seguro que deseas cancelar esta solicitud?');">
                @csrf
                <button type="submit"
                    class="rounded-md border border-red-300 bg-white px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50">
                    Cancelar solicitud
                </button>
            </form>
        @endif
    </div>

    <h3 class="mt-8 text-sm font-medium text-zinc-700">Historial</h3>
    <ol class="mt-3 space-y-3">
        @foreach ($solicitud->historial as $evento)
            <li class="rounded-lg border border-zinc-200 bg-white p-3 text-sm">
                <div class="flex items-center justify-between">
                    <span class="font-medium">{{ $labels[$evento->estado_nuevo] ?? $evento->estado_nuevo }}</span>
                    <span class="text-xs text-zinc-500">{{ $evento->created_at?->translatedFormat('d/m/Y H:i') }}</span>
                </div>
                @if ($evento->observacion)
                    <p class="mt-1 text-zinc-600">{{ $evento->observacion }}</p>
                @endif
            </li>
        @endforeach
    </ol>
@endsection
