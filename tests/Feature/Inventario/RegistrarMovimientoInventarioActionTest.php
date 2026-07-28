<?php

use App\Actions\Inventario\RegistrarMovimientoInventarioAction;
use App\Events\StockBajoDetectado;
use App\Exceptions\BusinessException;
use App\Models\InventarioMovimiento;
use App\Models\Repuesto;
use App\Models\Taller;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Event;

function repuestoConStock(float $stockActual, float $stockMinimo = 0): Repuesto
{
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);

    return Repuesto::factory()->create([
        'taller_id' => $taller->id,
        'stock_actual' => $stockActual,
        'stock_minimo' => $stockMinimo,
    ]);
}

it('ENTRADA suma al stock y guarda stock_anterior/stock_resultante correctos', function () {
    $repuesto = repuestoConStock(10);

    $movimiento = app(RegistrarMovimientoInventarioAction::class)->execute($repuesto, 'ENTRADA', 5);

    expect((float) $movimiento->stock_anterior)->toBe(10.0);
    expect((float) $movimiento->stock_resultante)->toBe(15.0);
    expect((float) $repuesto->fresh()->stock_actual)->toBe(15.0);
});

it('AJUSTE_POSITIVO suma al stock igual que ENTRADA', function () {
    $repuesto = repuestoConStock(3);

    app(RegistrarMovimientoInventarioAction::class)->execute($repuesto, 'AJUSTE_POSITIVO', 2);

    expect((float) $repuesto->fresh()->stock_actual)->toBe(5.0);
});

it('SALIDA resta del stock', function () {
    $repuesto = repuestoConStock(10);

    $movimiento = app(RegistrarMovimientoInventarioAction::class)->execute($repuesto, 'SALIDA', 4);

    expect((float) $movimiento->stock_resultante)->toBe(6.0);
    expect((float) $repuesto->fresh()->stock_actual)->toBe(6.0);
});

it('AJUSTE_NEGATIVO resta del stock igual que SALIDA', function () {
    $repuesto = repuestoConStock(10);

    app(RegistrarMovimientoInventarioAction::class)->execute($repuesto, 'AJUSTE_NEGATIVO', 3);

    expect((float) $repuesto->fresh()->stock_actual)->toBe(7.0);
});

it('rechaza un movimiento que dejaria el stock negativo', function () {
    $repuesto = repuestoConStock(5);

    expect(fn () => app(RegistrarMovimientoInventarioAction::class)->execute($repuesto, 'SALIDA', 10))
        ->toThrow(BusinessException::class);

    expect((float) $repuesto->fresh()->stock_actual)->toBe(5.0);
    expect(InventarioMovimiento::where('repuesto_id', $repuesto->id)->count())->toBe(0);
});

it('permite un movimiento que deja el stock exactamente en cero', function () {
    $repuesto = repuestoConStock(5);

    app(RegistrarMovimientoInventarioAction::class)->execute($repuesto, 'SALIDA', 5);

    expect((float) $repuesto->fresh()->stock_actual)->toBe(0.0);
});

it('rechaza una cantidad cero o negativa', function () {
    $repuesto = repuestoConStock(10);

    expect(fn () => app(RegistrarMovimientoInventarioAction::class)->execute($repuesto, 'ENTRADA', 0))
        ->toThrow(BusinessException::class);
    expect(fn () => app(RegistrarMovimientoInventarioAction::class)->execute($repuesto, 'ENTRADA', -1))
        ->toThrow(BusinessException::class);
});

it('rechaza un tipo de movimiento invalido', function () {
    $repuesto = repuestoConStock(10);

    expect(fn () => app(RegistrarMovimientoInventarioAction::class)->execute($repuesto, 'TRANSFERENCIA', 1))
        ->toThrow(BusinessException::class);
});

it('nunca genera doble SALIDA para la misma linea de orden (orden_trabajo_repuesto_id)', function () {
    $repuesto = repuestoConStock(20);

    app(RegistrarMovimientoInventarioAction::class)->execute(
        $repuesto,
        'SALIDA',
        1,
        ordenTrabajoRepuestoId: 999,
    );

    expect(fn () => app(RegistrarMovimientoInventarioAction::class)->execute(
        $repuesto,
        'SALIDA',
        1,
        ordenTrabajoRepuestoId: 999,
    ))->toThrow(BusinessException::class);

    expect(InventarioMovimiento::where('orden_trabajo_repuesto_id', 999)->count())->toBe(1);
});

it('permite SALIDA de distintas lineas de orden para el mismo repuesto', function () {
    $repuesto = repuestoConStock(20);

    app(RegistrarMovimientoInventarioAction::class)->execute($repuesto, 'SALIDA', 1, ordenTrabajoRepuestoId: 1);
    app(RegistrarMovimientoInventarioAction::class)->execute($repuesto, 'SALIDA', 1, ordenTrabajoRepuestoId: 2);

    expect(InventarioMovimiento::where('repuesto_id', $repuesto->id)->count())->toBe(2);
});

it('dispara StockBajoDetectado cuando el stock_resultante queda en o por debajo del minimo', function () {
    Event::fake([StockBajoDetectado::class]);
    $repuesto = repuestoConStock(10, stockMinimo: 5);

    app(RegistrarMovimientoInventarioAction::class)->execute($repuesto, 'SALIDA', 6);

    Event::assertDispatched(StockBajoDetectado::class, fn (StockBajoDetectado $event) => $event->repuesto->id === $repuesto->id);
});

it('no dispara StockBajoDetectado si el stock_resultante queda por encima del minimo', function () {
    Event::fake([StockBajoDetectado::class]);
    $repuesto = repuestoConStock(10, stockMinimo: 2);

    app(RegistrarMovimientoInventarioAction::class)->execute($repuesto, 'SALIDA', 1);

    Event::assertNotDispatched(StockBajoDetectado::class);
});

it('los movimientos son append-only: la tabla no tiene columna updated_at gestionada por el modelo', function () {
    $repuesto = repuestoConStock(10);
    $movimiento = app(RegistrarMovimientoInventarioAction::class)->execute($repuesto, 'ENTRADA', 1);

    expect(InventarioMovimiento::UPDATED_AT)->toBeNull();
    expect($movimiento->created_at)->not->toBeNull();
});

it('rechaza en BD una cantidad no positiva a nivel de constraint', function () {
    $repuesto = repuestoConStock(10);

    expect(fn () => InventarioMovimiento::factory()->create([
        'taller_id' => $repuesto->taller_id,
        'repuesto_id' => $repuesto->id,
        'cantidad' => 0,
        'stock_anterior' => 10,
        'stock_resultante' => 10,
    ]))->toThrow(QueryException::class);
});
