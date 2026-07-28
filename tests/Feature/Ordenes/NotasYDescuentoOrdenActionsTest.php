<?php

use App\Actions\Ordenes\ActualizarDescuentoOrdenAction;
use App\Actions\Ordenes\AgregarLineaServicioAction;
use App\Actions\Ordenes\AgregarNotaOrdenAction;
use App\Exceptions\BusinessException;
use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use App\Models\ServicioCatalogo;
use App\Models\Taller;

function ordenParaNotas(string $estado = 'PENDIENTE'): OrdenTrabajo
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

it('agrega una nota interna y una publica', function () {
    $orden = ordenParaNotas();

    $interna = app(AgregarNotaOrdenAction::class)->execute($orden, 'INTERNA', 'Revisar frenos.');
    $publica = app(AgregarNotaOrdenAction::class)->execute($orden, 'PUBLICA', 'Su vehículo está en revisión.');

    expect($interna->tipo)->toBe('INTERNA');
    expect($publica->tipo)->toBe('PUBLICA');
});

it('rechaza un tipo de nota invalido', function () {
    $orden = ordenParaNotas();

    expect(fn () => app(AgregarNotaOrdenAction::class)->execute($orden, 'SECRETA', 'x'))
        ->toThrow(BusinessException::class);
});

it('rechaza agregar notas a una orden anulada', function () {
    $orden = ordenParaNotas('ANULADA');

    expect(fn () => app(AgregarNotaOrdenAction::class)->execute($orden, 'INTERNA', 'x'))
        ->toThrow(BusinessException::class);
});

it('actualiza el descuento de orden validando contra la suma de subtotales', function () {
    $orden = ordenParaNotas();
    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $orden->taller_id, 'precio_base' => 100]);
    app(AgregarLineaServicioAction::class)->execute($orden, $servicio->id, 1);

    $actualizada = app(ActualizarDescuentoOrdenAction::class)->execute($orden->fresh(), 20);

    expect((float) $actualizada->descuento)->toBe(20.0);
    expect((float) $actualizada->total)->toBe(80.0);
});

it('rechaza un descuento de orden mayor a la suma de subtotales', function () {
    $orden = ordenParaNotas();
    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $orden->taller_id, 'precio_base' => 100]);
    app(AgregarLineaServicioAction::class)->execute($orden, $servicio->id, 1);

    expect(fn () => app(ActualizarDescuentoOrdenAction::class)->execute($orden->fresh(), 200))
        ->toThrow(BusinessException::class);
});

it('rechaza actualizar el descuento de una orden anulada', function () {
    $orden = ordenParaNotas('ANULADA');

    expect(fn () => app(ActualizarDescuentoOrdenAction::class)->execute($orden, 0))
        ->toThrow(BusinessException::class);
});
