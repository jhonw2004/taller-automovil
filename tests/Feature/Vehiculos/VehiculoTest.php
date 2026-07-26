<?php

use App\Models\Cliente;
use App\Models\Taller;
use App\Models\Vehiculo;
use Illuminate\Database\QueryException;

it('aisla vehiculos por taller: un taller no ve vehiculos de otro', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    $clienteA = Cliente::factory()->create(['taller_id' => $tallerA->id]);
    $clienteB = Cliente::factory()->create(['taller_id' => $tallerB->id]);
    Vehiculo::factory()->create(['taller_id' => $tallerA->id, 'cliente_id' => $clienteA->id]);
    Vehiculo::factory()->create(['taller_id' => $tallerB->id, 'cliente_id' => $clienteB->id]);

    session(['taller_activo_id' => $tallerA->id]);

    expect(Vehiculo::all())->toHaveCount(1);
    expect(Vehiculo::first()->taller_id)->toBe($tallerA->id);
});

it('normaliza la placa a mayusculas sin guion sin importar como se escriba', function () {
    $taller = Taller::factory()->create();
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);

    $vehiculo = Vehiculo::factory()->create([
        'taller_id' => $taller->id,
        'cliente_id' => $cliente->id,
        'placa' => 'abc-123',
    ]);

    expect($vehiculo->placa)->toBe('ABC123')
        ->and($vehiculo->placa_formateada)->toBe('ABC-123');
});

it('rechaza en BD una placa que no cumple el formato ABC123', function () {
    $taller = Taller::factory()->create();
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);

    expect(fn () => Vehiculo::factory()->create([
        'taller_id' => $taller->id,
        'cliente_id' => $cliente->id,
        'placa' => 'AB1234',
    ]))->toThrow(QueryException::class);
});

it('rechaza en BD dos vehiculos con la misma placa en el mismo taller', function () {
    $taller = Taller::factory()->create();
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    Vehiculo::factory()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id, 'placa' => 'ABC123']);

    expect(fn () => Vehiculo::factory()->create([
        'taller_id' => $taller->id,
        'cliente_id' => $cliente->id,
        'placa' => 'ABC123',
    ]))->toThrow(QueryException::class);
});

it('permite la misma placa en talleres distintos', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    $clienteA = Cliente::factory()->create(['taller_id' => $tallerA->id]);
    $clienteB = Cliente::factory()->create(['taller_id' => $tallerB->id]);
    Vehiculo::factory()->create(['taller_id' => $tallerA->id, 'cliente_id' => $clienteA->id, 'placa' => 'ABC123']);

    $vehiculoB = Vehiculo::factory()->create(['taller_id' => $tallerB->id, 'cliente_id' => $clienteB->id, 'placa' => 'ABC123']);

    expect($vehiculoB->exists)->toBeTrue();
});

it('rechaza en BD un kilometraje negativo', function () {
    $taller = Taller::factory()->create();
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);

    expect(fn () => Vehiculo::factory()->create([
        'taller_id' => $taller->id,
        'cliente_id' => $cliente->id,
        'kilometraje' => -1,
    ]))->toThrow(QueryException::class);
});

it('rechaza en BD un anio fuera de rango', function () {
    $taller = Taller::factory()->create();
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);

    expect(fn () => Vehiculo::factory()->create([
        'taller_id' => $taller->id,
        'cliente_id' => $cliente->id,
        'anio' => 1899,
    ]))->toThrow(QueryException::class);
});

it('rechaza en BD un tipo_vehiculo fuera del catalogo', function () {
    $taller = Taller::factory()->create();
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);

    expect(fn () => Vehiculo::factory()->create([
        'taller_id' => $taller->id,
        'cliente_id' => $cliente->id,
        'tipo_vehiculo' => 'BICICLETA',
    ]))->toThrow(QueryException::class);
});

it('soft-deletea un vehiculo en vez de borrarlo fisicamente', function () {
    $taller = Taller::factory()->create();
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $vehiculo = Vehiculo::factory()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id]);

    $vehiculo->delete();

    expect(Vehiculo::withTrashed()->find($vehiculo->id))->not->toBeNull();
    expect(Vehiculo::find($vehiculo->id))->toBeNull();
});

it('scopeActivos excluye vehiculos inactivos', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    Vehiculo::factory()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id]);
    Vehiculo::factory()->inactivo()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id]);

    expect(Vehiculo::activos()->count())->toBe(1);
});
