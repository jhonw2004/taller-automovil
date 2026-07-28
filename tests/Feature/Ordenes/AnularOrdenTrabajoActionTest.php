<?php

use App\Actions\Ordenes\AnularOrdenTrabajoAction;
use App\Exceptions\BusinessException;
use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use App\Models\OrdenTrabajoHistorialEstado;
use App\Models\Repuesto;
use App\Models\ServicioCatalogo;
use App\Models\Taller;

function ordenParaAnular(string $estado = 'EN_PROGRESO'): OrdenTrabajo
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

it('exige un motivo obligatorio', function () {
    $orden = ordenParaAnular();

    expect(fn () => app(AnularOrdenTrabajoAction::class)->execute($orden, ''))
        ->toThrow(BusinessException::class);

    expect($orden->fresh()->estado)->not->toBe('ANULADA');
});

it('rechaza anular una orden ya ANULADA', function () {
    $orden = ordenParaAnular('ANULADA');

    expect(fn () => app(AnularOrdenTrabajoAction::class)->execute($orden, 'Motivo'))
        ->toThrow(BusinessException::class);
});

it('rechaza anular una orden ENTREGADA (estado final sin transiciones permitidas)', function () {
    $orden = ordenParaAnular('ENTREGADA');

    expect(fn () => app(AnularOrdenTrabajoAction::class)->execute($orden, 'Motivo'))
        ->toThrow(BusinessException::class);
});

it('anula la orden, marca las lineas no anuladas como ANULADO e inserta historial con el motivo', function () {
    $orden = ordenParaAnular('PENDIENTE');
    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $orden->taller_id]);
    $lineaServicio = $orden->lineasServicios()->create([
        'servicio_catalogo_id' => $servicio->id,
        'cantidad' => 1,
        'precio_unitario' => 50,
        'descuento' => 0,
        'subtotal' => 50,
        'estado' => 'PENDIENTE',
    ]);

    $anulada = app(AnularOrdenTrabajoAction::class)->execute($orden, 'Cliente desistió');

    expect($anulada->estado)->toBe('ANULADA');
    expect($lineaServicio->fresh()->estado)->toBe('ANULADO');

    $historial = OrdenTrabajoHistorialEstado::where('orden_trabajo_id', $orden->id)->latest('id')->first();
    expect($historial->estado_nuevo)->toBe('ANULADA');
    expect($historial->observacion)->toBe('Cliente desistió');
});

it('repone stock por cada linea de repuesto en estado ENTREGADO al anular', function () {
    $orden = ordenParaAnular('EN_PROGRESO');
    $repuesto = Repuesto::factory()->create(['taller_id' => $orden->taller_id, 'stock_actual' => 10]);
    $orden->lineasRepuestos()->create([
        'repuesto_id' => $repuesto->id,
        'cantidad' => 3,
        'precio_unitario' => 20,
        'descuento' => 0,
        'subtotal' => 60,
        'estado' => 'ENTREGADO',
    ]);

    app(AnularOrdenTrabajoAction::class)->execute($orden, 'Error de diagnóstico');

    expect((float) $repuesto->fresh()->stock_actual)->toBe(13.0);
});

it('no repone stock por lineas de repuesto en estado PENDIENTE (nunca se descontó)', function () {
    $orden = ordenParaAnular('EN_PROGRESO');
    $repuesto = Repuesto::factory()->create(['taller_id' => $orden->taller_id, 'stock_actual' => 10]);
    $orden->lineasRepuestos()->create([
        'repuesto_id' => $repuesto->id,
        'cantidad' => 3,
        'precio_unitario' => 20,
        'descuento' => 0,
        'subtotal' => 60,
        'estado' => 'PENDIENTE',
    ]);

    app(AnularOrdenTrabajoAction::class)->execute($orden, 'Ya no se necesita');

    expect((float) $repuesto->fresh()->stock_actual)->toBe(10.0);
});

it('congela los totales existentes en vez de recalcularlos a cero al anular', function () {
    $orden = ordenParaAnular('EN_PROGRESO');
    $orden->update(['subtotal_servicios' => 100, 'subtotal_repuestos' => 0, 'descuento' => 10, 'total' => 90]);

    $anulada = app(AnularOrdenTrabajoAction::class)->execute($orden, 'Motivo');

    expect((float) $anulada->total)->toBe(90.0);
});
