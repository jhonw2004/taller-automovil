<?php

use App\Actions\Solicitudes\CrearSolicitudTallerAction;
use App\Models\SolicitudTallerHistorial;

it('crea la solicitud en PENDIENTE con token único e historial inicial en una sola operación', function () {
    $solicitud = app(CrearSolicitudTallerAction::class)->execute([
        'solicitante_nombre' => 'Juan Pérez',
        'solicitante_email' => 'juan@example.com',
        'solicitante_telefono' => '77712345',
        'taller_nombre' => 'Taller El Rayo',
    ]);

    expect($solicitud->estado)->toBe('PENDIENTE')
        ->and($solicitud->token_publico)->not->toBeEmpty()
        ->and($solicitud->enviada_at)->not->toBeNull()
        ->and($solicitud->taller_id)->toBeNull();

    $historial = SolicitudTallerHistorial::where('solicitud_taller_id', $solicitud->id)->get();
    expect($historial)->toHaveCount(1)
        ->and($historial->first()->estado_anterior)->toBeNull()
        ->and($historial->first()->estado_nuevo)->toBe('PENDIENTE')
        ->and($historial->first()->usuario_sistema_id)->toBeNull();
});

it('genera tokens públicos distintos en cada solicitud', function () {
    $datos = [
        'solicitante_nombre' => 'Juan Pérez',
        'solicitante_email' => 'juan@example.com',
        'solicitante_telefono' => '77712345',
        'taller_nombre' => 'Taller El Rayo',
    ];

    $a = app(CrearSolicitudTallerAction::class)->execute($datos);
    $b = app(CrearSolicitudTallerAction::class)->execute($datos);

    expect($a->token_publico)->not->toBe($b->token_publico);
});

it('no expone el id interno a través del token', function () {
    $solicitud = app(CrearSolicitudTallerAction::class)->execute([
        'solicitante_nombre' => 'Juan Pérez',
        'solicitante_email' => 'juan@example.com',
        'solicitante_telefono' => '77712345',
        'taller_nombre' => 'Taller El Rayo',
    ]);

    // `Str::uuid()` genera un UUID v4 aleatorio: la aserción de "no contiene el id" como subcadena
    // es una falsa alarma (un UUID puede contener cualquier subcadena numérica por casualidad, ej.
    // id=29 y `...-29e0-...`). Lo relevante es que el token sea un UUID real distinto del id.
    expect($solicitud->token_publico)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/')
        ->and($solicitud->token_publico)->not->toBe((string) $solicitud->id);
});
