<?php

use App\Actions\Ordenes\AgregarLineaServicioAction;
use App\Actions\Ordenes\CambiarEstadoLineaServicioAction;
use App\Exceptions\BusinessException;
use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use App\Models\ServicioCatalogo;
use App\Models\Taller;

function ordenConTaller(string $estado = 'PENDIENTE'): OrdenTrabajo
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

it('agrega una linea de servicio con precio_unitario como snapshot del catalogo', function () {
    $orden = ordenConTaller();
    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $orden->taller_id, 'precio_base' => 80]);

    $linea = app(AgregarLineaServicioAction::class)->execute($orden, $servicio->id, 2);

    expect((float) $linea->precio_unitario)->toBe(80.0);
    expect((float) $linea->subtotal)->toBe(160.0);
    expect((float) $orden->fresh()->subtotal_servicios)->toBe(160.0);
    expect((float) $orden->fresh()->total)->toBe(160.0);
});

it('el snapshot no cambia si el precio_base del catalogo cambia despues', function () {
    $orden = ordenConTaller();
    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $orden->taller_id, 'precio_base' => 80]);

    $linea = app(AgregarLineaServicioAction::class)->execute($orden, $servicio->id, 1);

    $servicio->update(['precio_base' => 999]);

    expect((float) $linea->fresh()->precio_unitario)->toBe(80.0);
});

it('rechaza una cantidad cero o negativa', function () {
    $orden = ordenConTaller();
    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $orden->taller_id]);

    expect(fn () => app(AgregarLineaServicioAction::class)->execute($orden, $servicio->id, 0))
        ->toThrow(BusinessException::class);
});

it('rechaza un descuento mayor al bruto de la linea', function () {
    $orden = ordenConTaller();
    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $orden->taller_id, 'precio_base' => 50]);

    expect(fn () => app(AgregarLineaServicioAction::class)->execute($orden, $servicio->id, 1, 51))
        ->toThrow(BusinessException::class);
});

it('rechaza un servicio de otro taller', function () {
    $orden = ordenConTaller();
    $servicioDeOtroTaller = ServicioCatalogo::factory()->create();

    expect(fn () => app(AgregarLineaServicioAction::class)->execute($orden, $servicioDeOtroTaller->id, 1))
        ->toThrow(BusinessException::class);
});

it('rechaza agregar lineas a una orden anulada', function () {
    $orden = ordenConTaller('ANULADA');
    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $orden->taller_id]);

    expect(fn () => app(AgregarLineaServicioAction::class)->execute($orden, $servicio->id, 1))
        ->toThrow(BusinessException::class);
});

it('marca una linea como REALIZADO y no afecta el total', function () {
    $orden = ordenConTaller();
    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $orden->taller_id, 'precio_base' => 100]);
    $linea = app(AgregarLineaServicioAction::class)->execute($orden, $servicio->id, 1);

    $actualizada = app(CambiarEstadoLineaServicioAction::class)->execute($linea, 'REALIZADO');

    expect($actualizada->estado)->toBe('REALIZADO');
    expect((float) $orden->fresh()->total)->toBe(100.0);
});

it('anular una linea la excluye del total de la orden', function () {
    $orden = ordenConTaller();
    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $orden->taller_id, 'precio_base' => 100]);
    $linea = app(AgregarLineaServicioAction::class)->execute($orden, $servicio->id, 1);

    app(CambiarEstadoLineaServicioAction::class)->execute($linea, 'ANULADO');

    expect((float) $orden->fresh()->total)->toBe(0.0);
    expect((float) $orden->fresh()->subtotal_servicios)->toBe(0.0);
});

it('rechaza una transicion de linea no listada', function () {
    $orden = ordenConTaller();
    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $orden->taller_id]);
    $linea = app(AgregarLineaServicioAction::class)->execute($orden, $servicio->id, 1);
    app(CambiarEstadoLineaServicioAction::class)->execute($linea, 'ANULADO');

    expect(fn () => app(CambiarEstadoLineaServicioAction::class)->execute($linea->fresh(), 'REALIZADO'))
        ->toThrow(BusinessException::class);
});
