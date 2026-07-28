<?php

use App\Actions\Ordenes\AgregarLineaRepuestoAction;
use App\Actions\Ordenes\CambiarEstadoLineaRepuestoAction;
use App\Exceptions\BusinessException;
use App\Models\Cliente;
use App\Models\InventarioMovimiento;
use App\Models\OrdenTrabajo;
use App\Models\Repuesto;
use App\Models\Taller;

function ordenConRepuestos(string $estado = 'PENDIENTE'): OrdenTrabajo
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

it('agrega una linea de repuesto con precio_unitario como snapshot y sin tocar stock', function () {
    $orden = ordenConRepuestos();
    $repuesto = Repuesto::factory()->create(['taller_id' => $orden->taller_id, 'precio_venta' => 40, 'stock_actual' => 10]);

    $linea = app(AgregarLineaRepuestoAction::class)->execute($orden, $repuesto->id, 2);

    expect((float) $linea->precio_unitario)->toBe(40.0);
    expect((float) $linea->subtotal)->toBe(80.0);
    expect($linea->estado)->toBe('PENDIENTE');
    expect((float) $repuesto->fresh()->stock_actual)->toBe(10.0);
});

it('rechaza un descuento mayor al bruto de la linea', function () {
    $orden = ordenConRepuestos();
    $repuesto = Repuesto::factory()->create(['taller_id' => $orden->taller_id, 'precio_venta' => 40]);

    expect(fn () => app(AgregarLineaRepuestoAction::class)->execute($orden, $repuesto->id, 1, 41))
        ->toThrow(BusinessException::class);
});

it('entregar una linea descuenta stock real via RegistrarMovimientoInventarioAction', function () {
    $orden = ordenConRepuestos();
    $repuesto = Repuesto::factory()->create(['taller_id' => $orden->taller_id, 'stock_actual' => 10]);
    $linea = app(AgregarLineaRepuestoAction::class)->execute($orden, $repuesto->id, 3);

    $actualizada = app(CambiarEstadoLineaRepuestoAction::class)->execute($linea, 'ENTREGADO');

    expect($actualizada->estado)->toBe('ENTREGADO');
    expect((float) $repuesto->fresh()->stock_actual)->toBe(7.0);
    expect(InventarioMovimiento::where('orden_trabajo_repuesto_id', $linea->id)->where('tipo_movimiento', 'SALIDA')->count())->toBe(1);
});

it('si el stock es insuficiente la linea NO cambia de estado (rollback completo)', function () {
    $orden = ordenConRepuestos();
    $repuesto = Repuesto::factory()->create(['taller_id' => $orden->taller_id, 'stock_actual' => 2]);
    $linea = app(AgregarLineaRepuestoAction::class)->execute($orden, $repuesto->id, 5);

    expect(fn () => app(CambiarEstadoLineaRepuestoAction::class)->execute($linea, 'ENTREGADO'))
        ->toThrow(BusinessException::class);

    expect($linea->fresh()->estado)->toBe('PENDIENTE');
    expect((float) $repuesto->fresh()->stock_actual)->toBe(2.0);
    expect(InventarioMovimiento::where('orden_trabajo_repuesto_id', $linea->id)->count())->toBe(0);
});

it('nunca genera doble SALIDA para la misma linea (protegido por 010-inventario-repuestos)', function () {
    $orden = ordenConRepuestos();
    $repuesto = Repuesto::factory()->create(['taller_id' => $orden->taller_id, 'stock_actual' => 10]);
    $linea = app(AgregarLineaRepuestoAction::class)->execute($orden, $repuesto->id, 1);

    app(CambiarEstadoLineaRepuestoAction::class)->execute($linea, 'ENTREGADO');

    // Forzar el estado de vuelta a PENDIENTE para intentar una segunda entrega de la misma línea.
    $linea->fresh()->update(['estado' => 'PENDIENTE']);

    expect(fn () => app(CambiarEstadoLineaRepuestoAction::class)->execute($linea->fresh(), 'ENTREGADO'))
        ->toThrow(BusinessException::class);
});

it('anular una linea ENTREGADA repone el stock (AJUSTE_POSITIVO)', function () {
    $orden = ordenConRepuestos();
    $repuesto = Repuesto::factory()->create(['taller_id' => $orden->taller_id, 'stock_actual' => 10]);
    $linea = app(AgregarLineaRepuestoAction::class)->execute($orden, $repuesto->id, 3);
    app(CambiarEstadoLineaRepuestoAction::class)->execute($linea, 'ENTREGADO');

    app(CambiarEstadoLineaRepuestoAction::class)->execute($linea->fresh(), 'ANULADO');

    expect((float) $repuesto->fresh()->stock_actual)->toBe(10.0);
});

it('anular una linea PENDIENTE (nunca entregada) no toca el stock', function () {
    $orden = ordenConRepuestos();
    $repuesto = Repuesto::factory()->create(['taller_id' => $orden->taller_id, 'stock_actual' => 10]);
    $linea = app(AgregarLineaRepuestoAction::class)->execute($orden, $repuesto->id, 3);

    app(CambiarEstadoLineaRepuestoAction::class)->execute($linea, 'ANULADO');

    expect((float) $repuesto->fresh()->stock_actual)->toBe(10.0);
});

it('rechaza cambiar el estado de una linea si la orden esta anulada', function () {
    $orden = ordenConRepuestos();
    $repuesto = Repuesto::factory()->create(['taller_id' => $orden->taller_id, 'stock_actual' => 10]);
    $linea = app(AgregarLineaRepuestoAction::class)->execute($orden, $repuesto->id, 1);

    $orden->update(['estado' => 'ANULADA']);

    expect(fn () => app(CambiarEstadoLineaRepuestoAction::class)->execute($linea->fresh(), 'ENTREGADO'))
        ->toThrow(BusinessException::class);
});
