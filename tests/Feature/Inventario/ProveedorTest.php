<?php

use App\Models\Proveedor;
use App\Models\Repuesto;
use App\Models\Taller;
use Illuminate\Database\QueryException;

it('aisla proveedores por taller: un taller no ve proveedores de otro', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    Proveedor::factory()->create(['taller_id' => $tallerA->id]);
    Proveedor::factory()->create(['taller_id' => $tallerB->id]);

    session(['taller_activo_id' => $tallerA->id]);

    expect(Proveedor::all())->toHaveCount(1);
    expect(Proveedor::first()->taller_id)->toBe($tallerA->id);
});

it('auto-rellena taller_id desde la sesion al crear sin especificarlo', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);

    $proveedor = Proveedor::factory()->make(['taller_id' => null]);
    $proveedor->save();

    expect($proveedor->fresh()->taller_id)->toBe($taller->id);
});

it('rechaza en BD dos proveedores con el mismo nit en el mismo taller', function () {
    $taller = Taller::factory()->create();
    Proveedor::factory()->create(['taller_id' => $taller->id, 'nit' => '123456789']);

    expect(fn () => Proveedor::factory()->create(['taller_id' => $taller->id, 'nit' => '123456789']))
        ->toThrow(QueryException::class);
});

it('permite el mismo nit de proveedor en talleres distintos', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    Proveedor::factory()->create(['taller_id' => $tallerA->id, 'nit' => '123456789']);

    $proveedorB = Proveedor::factory()->create(['taller_id' => $tallerB->id, 'nit' => '123456789']);

    expect($proveedorB->exists)->toBeTrue();
});

it('normaliza nit vacio a null para no romper el indice unico parcial', function () {
    $taller = Taller::factory()->create();
    Proveedor::factory()->create(['taller_id' => $taller->id, 'nit' => '']);

    $segundo = Proveedor::factory()->create(['taller_id' => $taller->id, 'nit' => '']);

    expect($segundo->exists)->toBeTrue();
    expect($segundo->nit)->toBeNull();
});

it('soft-deletea un proveedor en vez de borrarlo fisicamente', function () {
    $taller = Taller::factory()->create();
    $proveedor = Proveedor::factory()->create(['taller_id' => $taller->id]);

    $proveedor->delete();

    expect(Proveedor::withTrashed()->find($proveedor->id))->not->toBeNull();
    expect(Proveedor::find($proveedor->id))->toBeNull();
});

it('scopeActivos excluye proveedores inactivos', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    Proveedor::factory()->create(['taller_id' => $taller->id]);
    Proveedor::factory()->inactivo()->create(['taller_id' => $taller->id]);

    expect(Proveedor::activos()->count())->toBe(1);
});

it('vincula un repuesto a un proveedor con datos de pivot (precio_referencia, es_principal)', function () {
    $taller = Taller::factory()->create();
    $proveedor = Proveedor::factory()->create(['taller_id' => $taller->id]);
    $repuesto = Repuesto::factory()->create(['taller_id' => $taller->id]);

    $proveedor->repuestos()->attach($repuesto->id, [
        'codigo_proveedor' => 'PROV-XYZ',
        'precio_referencia' => 45.50,
        'tiempo_entrega_dias' => 3,
        'es_principal' => true,
    ]);

    $pivot = $proveedor->repuestos()->first()->pivot;
    expect($pivot->codigo_proveedor)->toBe('PROV-XYZ');
    expect((float) $pivot->precio_referencia)->toBe(45.50);
    expect($pivot->es_principal)->toBeTrue();
    expect($repuesto->proveedores()->first()->id)->toBe($proveedor->id);
});
