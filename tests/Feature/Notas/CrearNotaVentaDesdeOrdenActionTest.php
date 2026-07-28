<?php

use App\Actions\Notas\CrearNotaVentaDesdeOrdenAction;
use App\Exceptions\BusinessException;
use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use App\Models\Repuesto;
use App\Models\ServicioCatalogo;
use App\Models\Taller;

function ordenCompletadaConLineas(string $estadoOrden = 'COMPLETADA'): OrdenTrabajo
{
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $orden = OrdenTrabajo::factory()->create([
        'taller_id' => $taller->id,
        'cliente_id' => $cliente->id,
        'estado' => $estadoOrden,
    ]);

    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $taller->id, 'nombre' => 'Cambio de aceite']);
    $orden->lineasServicios()->create([
        'servicio_catalogo_id' => $servicio->id,
        'cantidad' => 1,
        'precio_unitario' => 80,
        'descuento' => 0,
        'subtotal' => 80,
        'estado' => 'REALIZADO',
    ]);

    $repuesto = Repuesto::factory()->create(['taller_id' => $taller->id, 'nombre' => 'Filtro de aceite', 'stock_actual' => 10]);
    $orden->lineasRepuestos()->create([
        'repuesto_id' => $repuesto->id,
        'cantidad' => 2,
        'precio_unitario' => 25,
        'descuento' => 5,
        'subtotal' => 45,
        'estado' => 'ENTREGADO',
    ]);

    $orden->lineasServicios()->create([
        'servicio_catalogo_id' => ServicioCatalogo::factory()->create(['taller_id' => $taller->id])->id,
        'cantidad' => 1,
        'precio_unitario' => 999,
        'descuento' => 0,
        'subtotal' => 999,
        'estado' => 'ANULADO',
    ]);

    return $orden;
}

it('copia las lineas activas de la orden y calcula los totales', function () {
    $orden = ordenCompletadaConLineas();

    $nota = app(CrearNotaVentaDesdeOrdenAction::class)->execute($orden);

    expect($nota->orden_trabajo_id)->toBe($orden->id);
    expect($nota->cliente_id)->toBe($orden->cliente_id);
    expect($nota->estado)->toBe('EMITIDA');
    expect($nota->codigo)->toStartWith('NV-'.date('Y').'-');
    expect($nota->lineas)->toHaveCount(2);
    expect((float) $nota->subtotal)->toBe(125.0);
    expect((float) $nota->total)->toBe(125.0);

    $lineaRepuesto = $nota->lineas()->whereNotNull('repuesto_id')->first();
    expect($lineaRepuesto->descripcion)->toBe('Filtro de aceite');
    expect((float) $lineaRepuesto->subtotal)->toBe(45.0);
});

it('no descuenta inventario: el stock ya se afecto al entregar la linea en la orden', function () {
    $orden = ordenCompletadaConLineas();
    $repuesto = $orden->lineasRepuestos()->first()->repuesto;
    $stockAntes = (float) $repuesto->stock_actual;

    app(CrearNotaVentaDesdeOrdenAction::class)->execute($orden);

    expect((float) $repuesto->fresh()->stock_actual)->toBe($stockAntes);
});

it('rechaza generar una nota desde una orden que no esta completada ni entregada', function () {
    $orden = ordenCompletadaConLineas('EN_PROGRESO');

    expect(fn () => app(CrearNotaVentaDesdeOrdenAction::class)->execute($orden))
        ->toThrow(BusinessException::class);
});

it('acepta una orden ENTREGADA', function () {
    $orden = ordenCompletadaConLineas('ENTREGADA');

    $nota = app(CrearNotaVentaDesdeOrdenAction::class)->execute($orden);

    expect($nota->id)->not->toBeNull();
});

it('rechaza una orden completada sin lineas activas para facturar', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $orden = OrdenTrabajo::factory()->create([
        'taller_id' => $taller->id,
        'cliente_id' => $cliente->id,
        'estado' => 'COMPLETADA',
    ]);

    expect(fn () => app(CrearNotaVentaDesdeOrdenAction::class)->execute($orden))
        ->toThrow(BusinessException::class);
});

it('permite generar multiples notas activas para la misma orden', function () {
    $orden = ordenCompletadaConLineas();

    $nota1 = app(CrearNotaVentaDesdeOrdenAction::class)->execute($orden);
    $nota2 = app(CrearNotaVentaDesdeOrdenAction::class)->execute($orden);

    expect($nota1->codigo)->not->toBe($nota2->codigo);
    expect($orden->notasVenta()->count())->toBe(2);
});
