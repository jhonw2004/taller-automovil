<?php

use App\Actions\Notas\AnularNotaVentaAction;
use App\Exceptions\BusinessException;
use App\Models\Cliente;
use App\Models\NotaVenta;
use App\Models\OrdenTrabajo;
use App\Models\Repuesto;
use App\Models\Taller;

function notaParaAnular(array $overrides = []): NotaVenta
{
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);

    return NotaVenta::factory()->create(array_merge(['taller_id' => $taller->id], $overrides));
}

it('exige un motivo obligatorio', function () {
    $nota = notaParaAnular();

    expect(fn () => app(AnularNotaVentaAction::class)->execute($nota, ''))
        ->toThrow(BusinessException::class);

    expect($nota->fresh()->estado)->not->toBe('ANULADA');
});

it('rechaza anular una nota ya ANULADA', function () {
    $nota = notaParaAnular(['estado' => 'ANULADA']);

    expect(fn () => app(AnularNotaVentaAction::class)->execute($nota, 'Motivo'))
        ->toThrow(BusinessException::class);
});

it('rechaza anular una nota PAGADA (estado final sin transiciones permitidas)', function () {
    $nota = notaParaAnular(['estado' => 'PAGADA']);

    expect(fn () => app(AnularNotaVentaAction::class)->execute($nota, 'Motivo'))
        ->toThrow(BusinessException::class);
});

it('bloquea la anulacion si la nota tiene pagos confirmados', function () {
    $nota = notaParaAnular(['estado' => 'PENDIENTE', 'subtotal' => 100, 'total' => 100, 'monto_pagado' => 40, 'saldo' => 60]);

    expect(fn () => app(AnularNotaVentaAction::class)->execute($nota, 'Motivo'))
        ->toThrow(BusinessException::class, 'Debe reversar los pagos primero.');

    expect($nota->fresh()->estado)->toBe('PENDIENTE');
});

it('anula una nota EMITIDA sin pagos', function () {
    $nota = notaParaAnular(['estado' => 'EMITIDA']);

    $anulada = app(AnularNotaVentaAction::class)->execute($nota, 'Cliente desistió');

    expect($anulada->estado)->toBe('ANULADA');
});

it('repone stock por cada linea de repuesto de una nota directa al anular', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $repuesto = Repuesto::factory()->create(['taller_id' => $taller->id, 'stock_actual' => 5]);
    $nota = NotaVenta::factory()->create(['taller_id' => $taller->id, 'orden_trabajo_id' => null, 'estado' => 'EMITIDA']);
    $nota->lineas()->create([
        'servicio_catalogo_id' => null,
        'repuesto_id' => $repuesto->id,
        'descripcion' => $repuesto->nombre,
        'cantidad' => 3,
        'precio_unitario' => 20,
        'descuento' => 0,
        'subtotal' => 60,
    ]);

    app(AnularNotaVentaAction::class)->execute($nota, 'Error de cobro');

    expect((float) $repuesto->fresh()->stock_actual)->toBe(8.0);
});

it('no repone stock de una nota que proviene de una orden (el descuento ya ocurrio ahi)', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $orden = OrdenTrabajo::factory()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id, 'estado' => 'COMPLETADA']);
    $repuesto = Repuesto::factory()->create(['taller_id' => $taller->id, 'stock_actual' => 5]);
    $nota = NotaVenta::factory()->create(['taller_id' => $taller->id, 'orden_trabajo_id' => $orden->id, 'estado' => 'EMITIDA']);
    $nota->lineas()->create([
        'servicio_catalogo_id' => null,
        'repuesto_id' => $repuesto->id,
        'descripcion' => $repuesto->nombre,
        'cantidad' => 3,
        'precio_unitario' => 20,
        'descuento' => 0,
        'subtotal' => 60,
    ]);

    app(AnularNotaVentaAction::class)->execute($nota, 'Error de cobro');

    expect((float) $repuesto->fresh()->stock_actual)->toBe(5.0);
});
