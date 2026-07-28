<?php

use App\Actions\Ordenes\AnularOrdenTrabajoAction;
use App\Exceptions\BusinessException;
use App\Models\Cliente;
use App\Models\NotaVenta;
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

// 012-notas-venta: resuelve la brecha documentada en 011-spec.md (bloqueo/auto-anulación según
// el estado de las notas de venta asociadas), diferida hasta que 012 existiera.

it('bloquea la anulacion de la orden si tiene una nota de venta PENDIENTE', function () {
    $orden = ordenParaAnular('EN_PROGRESO');
    $nota = NotaVenta::factory()->create([
        'taller_id' => $orden->taller_id,
        'orden_trabajo_id' => $orden->id,
        'estado' => 'PENDIENTE',
    ]);

    expect(fn () => app(AnularOrdenTrabajoAction::class)->execute($orden, 'Motivo'))
        ->toThrow(BusinessException::class, "Resuelva la nota {$nota->codigo} primero — tiene pagos registrados.");

    expect($orden->fresh()->estado)->not->toBe('ANULADA');
    expect($nota->fresh()->estado)->toBe('PENDIENTE');
});

it('bloquea la anulacion de la orden si tiene una nota de venta PAGADA', function () {
    $orden = ordenParaAnular('EN_PROGRESO');
    NotaVenta::factory()->create([
        'taller_id' => $orden->taller_id,
        'orden_trabajo_id' => $orden->id,
        'estado' => 'PAGADA',
    ]);

    expect(fn () => app(AnularOrdenTrabajoAction::class)->execute($orden, 'Motivo'))
        ->toThrow(BusinessException::class);

    expect($orden->fresh()->estado)->not->toBe('ANULADA');
});

it('auto-anula una nota de venta EMITIDA de la orden en la misma transaccion', function () {
    $orden = ordenParaAnular('EN_PROGRESO');
    $nota = NotaVenta::factory()->create([
        'taller_id' => $orden->taller_id,
        'orden_trabajo_id' => $orden->id,
        'estado' => 'EMITIDA',
    ]);

    app(AnularOrdenTrabajoAction::class)->execute($orden, 'Motivo');

    expect($nota->fresh()->estado)->toBe('ANULADA');
});

it('auto-anula multiples notas EMITIDA activas (se permiten varias notas por orden)', function () {
    $orden = ordenParaAnular('EN_PROGRESO');
    $nota1 = NotaVenta::factory()->create(['taller_id' => $orden->taller_id, 'orden_trabajo_id' => $orden->id, 'estado' => 'EMITIDA']);
    $nota2 = NotaVenta::factory()->create(['taller_id' => $orden->taller_id, 'orden_trabajo_id' => $orden->id, 'estado' => 'EMITIDA']);
    $notaYaAnulada = NotaVenta::factory()->create(['taller_id' => $orden->taller_id, 'orden_trabajo_id' => $orden->id, 'estado' => 'ANULADA']);

    app(AnularOrdenTrabajoAction::class)->execute($orden, 'Motivo');

    expect($nota1->fresh()->estado)->toBe('ANULADA');
    expect($nota2->fresh()->estado)->toBe('ANULADA');
    expect($notaYaAnulada->fresh()->estado)->toBe('ANULADA');
});

it('no repone inventario por una nota vinculada a la orden al auto-anularla (el descuento ya ocurrio en la orden)', function () {
    $orden = ordenParaAnular('EN_PROGRESO');
    $repuesto = Repuesto::factory()->create(['taller_id' => $orden->taller_id, 'stock_actual' => 10]);
    $nota = NotaVenta::factory()->create(['taller_id' => $orden->taller_id, 'orden_trabajo_id' => $orden->id, 'estado' => 'EMITIDA']);
    $nota->lineas()->create([
        'servicio_catalogo_id' => null,
        'repuesto_id' => $repuesto->id,
        'descripcion' => $repuesto->nombre,
        'cantidad' => 5,
        'precio_unitario' => 20,
        'descuento' => 0,
        'subtotal' => 100,
    ]);

    app(AnularOrdenTrabajoAction::class)->execute($orden, 'Motivo');

    expect((float) $repuesto->fresh()->stock_actual)->toBe(10.0);
    expect($nota->fresh()->estado)->toBe('ANULADA');
});
