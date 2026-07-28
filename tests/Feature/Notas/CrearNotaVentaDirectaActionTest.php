<?php

use App\Actions\Notas\CrearNotaVentaDirectaAction;
use App\Exceptions\BusinessException;
use App\Models\Cliente;
use App\Models\NotaVenta;
use App\Models\Repuesto;
use App\Models\ServicioCatalogo;
use App\Models\Taller;

function tallerActivo(): Taller
{
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);

    return $taller;
}

it('rechaza una nota sin lineas', function () {
    tallerActivo();

    expect(fn () => app(CrearNotaVentaDirectaAction::class)->execute(lineas: []))
        ->toThrow(BusinessException::class);

    expect(NotaVenta::count())->toBe(0);
});

it('crea una nota directa con linea de servicio, repuesto y personalizada, descontando stock solo del repuesto', function () {
    $taller = tallerActivo();
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $taller->id, 'nombre' => 'Lavado', 'precio_base' => 30]);
    $repuesto = Repuesto::factory()->create(['taller_id' => $taller->id, 'nombre' => 'Aceite 20W50', 'precio_venta' => 60, 'stock_actual' => 10]);

    $nota = app(CrearNotaVentaDirectaAction::class)->execute(
        clienteId: $cliente->id,
        lineas: [
            ['servicio_catalogo_id' => $servicio->id, 'cantidad' => 1],
            ['repuesto_id' => $repuesto->id, 'cantidad' => 2],
            ['descripcion' => 'Cargo especial', 'precio_unitario' => 15, 'cantidad' => 1],
        ],
    );

    expect($nota->orden_trabajo_id)->toBeNull();
    expect($nota->cliente_id)->toBe($cliente->id);
    expect($nota->lineas)->toHaveCount(3);
    expect((float) $nota->subtotal)->toBe(30.0 + 120.0 + 15.0);
    expect((float) $nota->total)->toBe((float) $nota->subtotal);

    $lineaServicio = $nota->lineas()->whereNotNull('servicio_catalogo_id')->first();
    expect($lineaServicio->descripcion)->toBe('Lavado');
    expect((float) $lineaServicio->precio_unitario)->toBe(30.0);

    $lineaRepuesto = $nota->lineas()->whereNotNull('repuesto_id')->first();
    expect($lineaRepuesto->descripcion)->toBe('Aceite 20W50');
    expect((float) $lineaRepuesto->subtotal)->toBe(120.0);

    expect((float) $repuesto->fresh()->stock_actual)->toBe(8.0);
});

it('rechaza una linea que referencia servicio y repuesto a la vez', function () {
    $taller = tallerActivo();
    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $taller->id]);
    $repuesto = Repuesto::factory()->create(['taller_id' => $taller->id]);

    expect(fn () => app(CrearNotaVentaDirectaAction::class)->execute(lineas: [
        ['servicio_catalogo_id' => $servicio->id, 'repuesto_id' => $repuesto->id, 'cantidad' => 1],
    ]))->toThrow(BusinessException::class);

    expect(NotaVenta::count())->toBe(0);
});

it('rechaza una linea personalizada sin descripcion', function () {
    tallerActivo();

    expect(fn () => app(CrearNotaVentaDirectaAction::class)->execute(lineas: [
        ['precio_unitario' => 10, 'cantidad' => 1],
    ]))->toThrow(BusinessException::class);
});

it('rechaza un descuento de linea mayor al bruto', function () {
    $taller = tallerActivo();
    $repuesto = Repuesto::factory()->create(['taller_id' => $taller->id, 'precio_venta' => 20, 'stock_actual' => 10]);

    expect(fn () => app(CrearNotaVentaDirectaAction::class)->execute(lineas: [
        ['repuesto_id' => $repuesto->id, 'cantidad' => 1, 'descuento' => 100],
    ]))->toThrow(BusinessException::class);
});

it('no deja nota ni movimiento huerfano si el stock es insuficiente para una linea de repuesto', function () {
    $taller = tallerActivo();
    $repuestoOk = Repuesto::factory()->create(['taller_id' => $taller->id, 'precio_venta' => 10, 'stock_actual' => 100]);
    $repuestoSinStock = Repuesto::factory()->create(['taller_id' => $taller->id, 'precio_venta' => 10, 'stock_actual' => 1]);

    expect(fn () => app(CrearNotaVentaDirectaAction::class)->execute(lineas: [
        ['repuesto_id' => $repuestoOk->id, 'cantidad' => 1],
        ['repuesto_id' => $repuestoSinStock->id, 'cantidad' => 5],
    ]))->toThrow(BusinessException::class);

    expect(NotaVenta::count())->toBe(0);
    expect((float) $repuestoOk->fresh()->stock_actual)->toBe(100.0);
    expect((float) $repuestoSinStock->fresh()->stock_actual)->toBe(1.0);
});

it('genera codigos secuenciales unicos por taller', function () {
    $taller = tallerActivo();
    $repuesto = Repuesto::factory()->create(['taller_id' => $taller->id, 'precio_venta' => 10, 'stock_actual' => 100]);

    $nota1 = app(CrearNotaVentaDirectaAction::class)->execute(lineas: [['repuesto_id' => $repuesto->id, 'cantidad' => 1]]);
    $nota2 = app(CrearNotaVentaDirectaAction::class)->execute(lineas: [['repuesto_id' => $repuesto->id, 'cantidad' => 1]]);

    expect($nota1->codigo)->not->toBe($nota2->codigo);
});
