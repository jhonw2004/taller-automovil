<?php

use App\Actions\Ordenes\CambiarEstadoOrdenAction;
use App\Exceptions\BusinessException;
use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use App\Models\OrdenTrabajoHistorialEstado;
use App\Models\Taller;

function ordenEnEstado(string $estado): OrdenTrabajo
{
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);

    return OrdenTrabajo::factory()->create([
        'taller_id' => $taller->id,
        'cliente_id' => $cliente->id,
        'estado' => $estado,
    ]);
}

it('permite una transicion listada en la tabla de estados y registra historial', function () {
    $orden = ordenEnEstado('PENDIENTE');

    $actualizada = app(CambiarEstadoOrdenAction::class)->execute($orden, 'EN_DIAGNOSTICO', null, 'Revisión inicial');

    expect($actualizada->estado)->toBe('EN_DIAGNOSTICO');

    $historial = OrdenTrabajoHistorialEstado::where('orden_trabajo_id', $orden->id)->latest('id')->first();
    expect($historial->estado_anterior)->toBe('PENDIENTE');
    expect($historial->estado_nuevo)->toBe('EN_DIAGNOSTICO');
    expect($historial->observacion)->toBe('Revisión inicial');
});

it('rechaza una transicion no listada', function () {
    $orden = ordenEnEstado('PENDIENTE');

    expect(fn () => app(CambiarEstadoOrdenAction::class)->execute($orden, 'COMPLETADA'))
        ->toThrow(BusinessException::class);

    expect($orden->fresh()->estado)->toBe('PENDIENTE');
});

it('rechaza cualquier transicion desde un estado final (ENTREGADA)', function () {
    $orden = ordenEnEstado('ENTREGADA');

    expect(fn () => app(CambiarEstadoOrdenAction::class)->execute($orden, 'EN_PROGRESO'))
        ->toThrow(BusinessException::class);
});

it('rechaza cualquier transicion desde ANULADA', function () {
    $orden = ordenEnEstado('ANULADA');

    expect(fn () => app(CambiarEstadoOrdenAction::class)->execute($orden, 'EN_PROGRESO'))
        ->toThrow(BusinessException::class);
});

it('no permite usar esta accion para anular: exige AnularOrdenTrabajoAction', function () {
    $orden = ordenEnEstado('PENDIENTE');

    expect(fn () => app(CambiarEstadoOrdenAction::class)->execute($orden, 'ANULADA'))
        ->toThrow(BusinessException::class);

    expect($orden->fresh()->estado)->toBe('PENDIENTE');
});

it('marca fecha_entrega_real al pasar a ENTREGADA', function () {
    $orden = ordenEnEstado('COMPLETADA');

    $actualizada = app(CambiarEstadoOrdenAction::class)->execute($orden, 'ENTREGADA');

    expect($actualizada->fecha_entrega_real)->not->toBeNull();
});
