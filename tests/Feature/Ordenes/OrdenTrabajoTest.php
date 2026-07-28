<?php

use App\Exceptions\BusinessException;
use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use App\Models\ServicioCatalogo;
use App\Models\Taller;
use App\Models\Vehiculo;
use Illuminate\Database\QueryException;

function crearOrdenEnTaller(Taller $taller, array $overrides = []): OrdenTrabajo
{
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);

    return OrdenTrabajo::factory()->create(array_merge([
        'taller_id' => $taller->id,
        'cliente_id' => $cliente->id,
    ], $overrides));
}

it('aisla ordenes de trabajo por taller', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    crearOrdenEnTaller($tallerA);
    crearOrdenEnTaller($tallerB);

    session(['taller_activo_id' => $tallerA->id]);

    expect(OrdenTrabajo::count())->toBe(1);
});

it('rellena taller_id automaticamente desde la sesion al crear sin especificarlo', function () {
    $taller = Taller::factory()->create();
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $vehiculo = Vehiculo::factory()->create(['cliente_id' => $cliente->id]);
    session(['taller_activo_id' => $taller->id]);

    $orden = OrdenTrabajo::factory()->create([
        'taller_id' => null,
        'cliente_id' => $cliente->id,
        'vehiculo_id' => $vehiculo->id,
    ]);

    expect($orden->taller_id)->toBe($taller->id);
});

it('rechaza un codigo de orden duplicado en el mismo taller a nivel de BD', function () {
    $taller = Taller::factory()->create();
    crearOrdenEnTaller($taller, ['codigo' => 'OT-2026-001']);

    expect(fn () => crearOrdenEnTaller($taller, ['codigo' => 'OT-2026-001']))
        ->toThrow(QueryException::class);
});

it('permite el mismo codigo de orden en talleres distintos', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();

    crearOrdenEnTaller($tallerA, ['codigo' => 'OT-2026-001']);
    $ordenB = crearOrdenEnTaller($tallerB, ['codigo' => 'OT-2026-001']);

    expect($ordenB->codigo)->toBe('OT-2026-001');
});

it('rechaza un estado invalido a nivel de constraint', function () {
    $taller = Taller::factory()->create();

    expect(fn () => crearOrdenEnTaller($taller, ['estado' => 'INVENTADO']))
        ->toThrow(QueryException::class);
});

it('rechaza una prioridad invalida a nivel de constraint', function () {
    $taller = Taller::factory()->create();

    expect(fn () => crearOrdenEnTaller($taller, ['prioridad' => 'URGENTISIMA']))
        ->toThrow(QueryException::class);
});

it('rechaza un descuento negativo a nivel de constraint', function () {
    $taller = Taller::factory()->create();

    expect(fn () => crearOrdenEnTaller($taller, ['descuento' => -1, 'total' => 1]))
        ->toThrow(QueryException::class);
});

it('rechaza cuando total no coincide con subtotales menos descuento', function () {
    $taller = Taller::factory()->create();

    expect(fn () => crearOrdenEnTaller($taller, [
        'subtotal_servicios' => 100,
        'subtotal_repuestos' => 0,
        'descuento' => 0,
        'total' => 50,
    ]))->toThrow(QueryException::class);
});

it('rechaza un total negativo a nivel de constraint', function () {
    $taller = Taller::factory()->create();

    expect(fn () => crearOrdenEnTaller($taller, [
        'subtotal_servicios' => 0,
        'subtotal_repuestos' => 0,
        'descuento' => 10,
        'total' => -10,
    ]))->toThrow(QueryException::class);
});

it('soft-deletea sin borrado fisico', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $orden = crearOrdenEnTaller($taller);

    $orden->delete();

    expect(OrdenTrabajo::count())->toBe(0);
    expect(OrdenTrabajo::withTrashed()->count())->toBe(1);
});

it('recalcularTotales suma solo lineas no anuladas y respeta el descuento de orden', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $orden = crearOrdenEnTaller($taller);

    $orden->lineasServicios()->create([
        'servicio_catalogo_id' => ServicioCatalogo::factory()->create(['taller_id' => $taller->id])->id,
        'cantidad' => 1,
        'precio_unitario' => 100,
        'descuento' => 0,
        'subtotal' => 100,
        'estado' => 'PENDIENTE',
    ]);
    $orden->lineasServicios()->create([
        'servicio_catalogo_id' => ServicioCatalogo::factory()->create(['taller_id' => $taller->id])->id,
        'cantidad' => 1,
        'precio_unitario' => 50,
        'descuento' => 0,
        'subtotal' => 50,
        'estado' => 'ANULADO',
    ]);

    $orden->recalcularTotales(10);

    expect((float) $orden->subtotal_servicios)->toBe(100.0);
    expect((float) $orden->descuento)->toBe(10.0);
    expect((float) $orden->total)->toBe(90.0);
});

it('recalcularTotales rechaza un descuento mayor a la suma de subtotales', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $orden = crearOrdenEnTaller($taller);

    $orden->lineasServicios()->create([
        'servicio_catalogo_id' => ServicioCatalogo::factory()->create(['taller_id' => $taller->id])->id,
        'cantidad' => 1,
        'precio_unitario' => 100,
        'descuento' => 0,
        'subtotal' => 100,
        'estado' => 'PENDIENTE',
    ]);

    expect(fn () => $orden->recalcularTotales(200))->toThrow(BusinessException::class);
});
