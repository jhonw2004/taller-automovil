<?php

use App\Models\Repuesto;
use App\Models\Taller;
use Illuminate\Database\QueryException;

it('aisla repuestos por taller: un taller no ve repuestos de otro', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    Repuesto::factory()->create(['taller_id' => $tallerA->id]);
    Repuesto::factory()->create(['taller_id' => $tallerB->id]);

    session(['taller_activo_id' => $tallerA->id]);

    expect(Repuesto::all())->toHaveCount(1);
    expect(Repuesto::first()->taller_id)->toBe($tallerA->id);
});

it('auto-rellena taller_id desde la sesion al crear sin especificarlo', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);

    $repuesto = Repuesto::factory()->make(['taller_id' => null]);
    $repuesto->save();

    expect($repuesto->fresh()->taller_id)->toBe($taller->id);
});

it('rechaza en BD dos repuestos con el mismo codigo en el mismo taller', function () {
    $taller = Taller::factory()->create();
    Repuesto::factory()->create(['taller_id' => $taller->id, 'codigo' => 'REP-001']);

    expect(fn () => Repuesto::factory()->create(['taller_id' => $taller->id, 'codigo' => 'REP-001']))
        ->toThrow(QueryException::class);
});

it('permite el mismo codigo de repuesto en talleres distintos', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    Repuesto::factory()->create(['taller_id' => $tallerA->id, 'codigo' => 'REP-001']);

    $repuestoB = Repuesto::factory()->create(['taller_id' => $tallerB->id, 'codigo' => 'REP-001']);

    expect($repuestoB->exists)->toBeTrue();
});

it('rechaza en BD dos repuestos con el mismo codigo_barras en el mismo taller', function () {
    $taller = Taller::factory()->create();
    Repuesto::factory()->create(['taller_id' => $taller->id, 'codigo_barras' => '7501234567890']);

    expect(fn () => Repuesto::factory()->create(['taller_id' => $taller->id, 'codigo_barras' => '7501234567890']))
        ->toThrow(QueryException::class);
});

it('permite varios repuestos sin codigo_barras en el mismo taller', function () {
    $taller = Taller::factory()->create();
    Repuesto::factory()->create(['taller_id' => $taller->id, 'codigo_barras' => null]);

    $segundo = Repuesto::factory()->create(['taller_id' => $taller->id, 'codigo_barras' => null]);

    expect($segundo->exists)->toBeTrue();
});

it('normaliza codigo_barras vacio a null para no romper el indice unico parcial', function () {
    $taller = Taller::factory()->create();
    Repuesto::factory()->create(['taller_id' => $taller->id, 'codigo_barras' => '']);

    $segundo = Repuesto::factory()->create(['taller_id' => $taller->id, 'codigo_barras' => '']);

    expect($segundo->exists)->toBeTrue();
    expect($segundo->codigo_barras)->toBeNull();
});

it('rechaza en BD un stock_actual negativo', function () {
    $taller = Taller::factory()->create();

    expect(fn () => Repuesto::factory()->create(['taller_id' => $taller->id, 'stock_actual' => -1]))
        ->toThrow(QueryException::class);
});

it('rechaza en BD un precio_costo o precio_venta negativo', function () {
    $taller = Taller::factory()->create();

    expect(fn () => Repuesto::factory()->create(['taller_id' => $taller->id, 'precio_costo' => -1]))
        ->toThrow(QueryException::class);
    expect(fn () => Repuesto::factory()->create(['taller_id' => $taller->id, 'precio_venta' => -1]))
        ->toThrow(QueryException::class);
});

it('soft-deletea un repuesto en vez de borrarlo fisicamente', function () {
    $taller = Taller::factory()->create();
    $repuesto = Repuesto::factory()->create(['taller_id' => $taller->id]);

    $repuesto->delete();

    expect(Repuesto::withTrashed()->find($repuesto->id))->not->toBeNull();
    expect(Repuesto::find($repuesto->id))->toBeNull();
});

it('scopeActivos excluye repuestos inactivos', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    Repuesto::factory()->create(['taller_id' => $taller->id]);
    Repuesto::factory()->inactivo()->create(['taller_id' => $taller->id]);

    expect(Repuesto::activos()->count())->toBe(1);
});
