<?php

use App\Exceptions\BusinessException;
use App\Models\NotaVenta;
use App\Models\Repuesto;
use App\Models\ServicioCatalogo;
use App\Models\Taller;
use Illuminate\Database\QueryException;

function crearNotaEnTaller(Taller $taller, array $overrides = []): NotaVenta
{
    return NotaVenta::factory()->create(array_merge([
        'taller_id' => $taller->id,
    ], $overrides));
}

it('aisla notas de venta por taller', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    crearNotaEnTaller($tallerA);
    crearNotaEnTaller($tallerB);

    session(['taller_activo_id' => $tallerA->id]);

    expect(NotaVenta::count())->toBe(1);
});

it('rellena taller_id automaticamente desde la sesion al crear sin especificarlo', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);

    $nota = NotaVenta::factory()->create(['taller_id' => null]);

    expect($nota->taller_id)->toBe($taller->id);
});

it('rechaza un codigo de nota duplicado en el mismo taller a nivel de BD', function () {
    $taller = Taller::factory()->create();
    crearNotaEnTaller($taller, ['codigo' => 'NV-2026-001']);

    expect(fn () => crearNotaEnTaller($taller, ['codigo' => 'NV-2026-001']))
        ->toThrow(QueryException::class);
});

it('permite el mismo codigo de nota en talleres distintos', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();

    crearNotaEnTaller($tallerA, ['codigo' => 'NV-2026-001']);
    $notaB = crearNotaEnTaller($tallerB, ['codigo' => 'NV-2026-001']);

    expect($notaB->codigo)->toBe('NV-2026-001');
});

it('rechaza un estado invalido a nivel de constraint', function () {
    $taller = Taller::factory()->create();

    expect(fn () => crearNotaEnTaller($taller, ['estado' => 'INVENTADO']))
        ->toThrow(QueryException::class);
});

it('rechaza un descuento negativo a nivel de constraint', function () {
    $taller = Taller::factory()->create();

    expect(fn () => crearNotaEnTaller($taller, ['descuento' => -1, 'total' => 1]))
        ->toThrow(QueryException::class);
});

it('rechaza un descuento mayor al subtotal a nivel de constraint', function () {
    $taller = Taller::factory()->create();

    expect(fn () => crearNotaEnTaller($taller, ['subtotal' => 50, 'descuento' => 100, 'total' => -50]))
        ->toThrow(QueryException::class);
});

it('rechaza cuando total no coincide con subtotal menos descuento', function () {
    $taller = Taller::factory()->create();

    expect(fn () => crearNotaEnTaller($taller, ['subtotal' => 100, 'descuento' => 0, 'total' => 50]))
        ->toThrow(QueryException::class);
});

it('rechaza un total negativo a nivel de constraint', function () {
    $taller = Taller::factory()->create();

    expect(fn () => crearNotaEnTaller($taller, ['subtotal' => 0, 'descuento' => 0, 'total' => -10]))
        ->toThrow(QueryException::class);
});

it('rechaza monto_pagado mayor al total a nivel de constraint', function () {
    $taller = Taller::factory()->create();

    expect(fn () => crearNotaEnTaller($taller, [
        'subtotal' => 100, 'descuento' => 0, 'total' => 100, 'monto_pagado' => 150, 'saldo' => -50,
    ]))->toThrow(QueryException::class);
});

it('rechaza cuando saldo no coincide con total menos monto_pagado', function () {
    $taller = Taller::factory()->create();

    expect(fn () => crearNotaEnTaller($taller, [
        'subtotal' => 100, 'descuento' => 0, 'total' => 100, 'monto_pagado' => 40, 'saldo' => 100,
    ]))->toThrow(QueryException::class);
});

it('soft-deletea sin borrado fisico', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $nota = crearNotaEnTaller($taller);

    $nota->delete();

    expect(NotaVenta::count())->toBe(0);
    expect(NotaVenta::withTrashed()->count())->toBe(1);
});

it('una linea no puede referenciar un servicio y un repuesto a la vez a nivel de constraint', function () {
    $taller = Taller::factory()->create();
    $nota = crearNotaEnTaller($taller);
    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $taller->id]);
    $repuesto = Repuesto::factory()->create(['taller_id' => $taller->id]);

    expect(fn () => $nota->lineas()->create([
        'servicio_catalogo_id' => $servicio->id,
        'repuesto_id' => $repuesto->id,
        'descripcion' => 'Línea inválida',
        'cantidad' => 1,
        'precio_unitario' => 10,
        'descuento' => 0,
        'subtotal' => 10,
    ]))->toThrow(QueryException::class);
});

it('permite una linea personalizada sin servicio ni repuesto', function () {
    $taller = Taller::factory()->create();
    $nota = crearNotaEnTaller($taller);

    $linea = $nota->lineas()->create([
        'servicio_catalogo_id' => null,
        'repuesto_id' => null,
        'descripcion' => 'Mano de obra especial',
        'cantidad' => 1,
        'precio_unitario' => 30,
        'descuento' => 0,
        'subtotal' => 30,
    ]);

    expect($linea->id)->not->toBeNull();
});

it('rechaza cuando el subtotal de la linea no coincide con cantidad*precio-descuento', function () {
    $taller = Taller::factory()->create();
    $nota = crearNotaEnTaller($taller);

    expect(fn () => $nota->lineas()->create([
        'servicio_catalogo_id' => null,
        'repuesto_id' => null,
        'descripcion' => 'Línea inconsistente',
        'cantidad' => 2,
        'precio_unitario' => 10,
        'descuento' => 0,
        'subtotal' => 100,
    ]))->toThrow(QueryException::class);
});

it('recalcularTotales suma las lineas y actualiza saldo respetando monto_pagado', function () {
    $taller = Taller::factory()->create();
    $nota = crearNotaEnTaller($taller, ['monto_pagado' => 20, 'total' => 20, 'saldo' => 0, 'subtotal' => 20]);
    $nota->lineas()->create([
        'servicio_catalogo_id' => null,
        'repuesto_id' => null,
        'descripcion' => 'Servicio X',
        'cantidad' => 1,
        'precio_unitario' => 100,
        'descuento' => 0,
        'subtotal' => 100,
    ]);

    $nota->recalcularTotales(10);

    expect((float) $nota->subtotal)->toBe(100.0);
    expect((float) $nota->descuento)->toBe(10.0);
    expect((float) $nota->total)->toBe(90.0);
    expect((float) $nota->saldo)->toBe(70.0);
});

it('recalcularTotales rechaza un descuento mayor al subtotal', function () {
    $taller = Taller::factory()->create();
    $nota = crearNotaEnTaller($taller);
    $nota->lineas()->create([
        'servicio_catalogo_id' => null,
        'repuesto_id' => null,
        'descripcion' => 'Servicio X',
        'cantidad' => 1,
        'precio_unitario' => 100,
        'descuento' => 0,
        'subtotal' => 100,
    ]);

    expect(fn () => $nota->recalcularTotales(200))->toThrow(BusinessException::class);
});
