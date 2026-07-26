<?php

use App\Models\Cliente;
use App\Models\Taller;
use Illuminate\Database\QueryException;

it('aisla clientes por taller: un taller no ve clientes de otro', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    Cliente::factory()->create(['taller_id' => $tallerA->id]);
    Cliente::factory()->create(['taller_id' => $tallerB->id]);

    session(['taller_activo_id' => $tallerA->id]);

    expect(Cliente::all())->toHaveCount(1);
    expect(Cliente::first()->taller_id)->toBe($tallerA->id);
});

it('auto-rellena taller_id desde la sesion al crear sin especificarlo', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);

    $cliente = Cliente::factory()->make(['taller_id' => null]);
    $cliente->save();

    expect($cliente->fresh()->taller_id)->toBe($taller->id);
});

it('rechaza en BD dos clientes con el mismo codigo en el mismo taller', function () {
    $taller = Taller::factory()->create();
    Cliente::factory()->create(['taller_id' => $taller->id, 'codigo' => 'CLI-001']);

    expect(fn () => Cliente::factory()->create(['taller_id' => $taller->id, 'codigo' => 'CLI-001']))
        ->toThrow(QueryException::class);
});

it('permite el mismo codigo de cliente en talleres distintos', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    Cliente::factory()->create(['taller_id' => $tallerA->id, 'codigo' => 'CLI-001']);

    $clienteB = Cliente::factory()->create(['taller_id' => $tallerB->id, 'codigo' => 'CLI-001']);

    expect($clienteB->exists)->toBeTrue();
});

it('rechaza en BD dos clientes con el mismo nit_ci en el mismo taller', function () {
    $taller = Taller::factory()->create();
    Cliente::factory()->create(['taller_id' => $taller->id, 'nit_ci' => '1234567']);

    expect(fn () => Cliente::factory()->create(['taller_id' => $taller->id, 'nit_ci' => '1234567']))
        ->toThrow(QueryException::class);
});

it('permite multiples clientes sin nit_ci en el mismo taller', function () {
    $taller = Taller::factory()->create();
    Cliente::factory()->create(['taller_id' => $taller->id, 'nit_ci' => null]);
    $segundo = Cliente::factory()->create(['taller_id' => $taller->id, 'nit_ci' => null]);

    expect($segundo->exists)->toBeTrue();
});

it('normaliza nit_ci en blanco a null para no romper la unicidad parcial', function () {
    $taller = Taller::factory()->create();

    $cliente = Cliente::factory()->create(['taller_id' => $taller->id, 'nit_ci' => '']);

    expect($cliente->nit_ci)->toBeNull();
});

it('rechaza en BD un tipo_persona fuera del catalogo', function () {
    $taller = Taller::factory()->create();

    expect(fn () => Cliente::factory()->create(['taller_id' => $taller->id, 'tipo_persona' => 'OTRO']))
        ->toThrow(QueryException::class);
});

it('soft-deletea un cliente en vez de borrarlo fisicamente', function () {
    $taller = Taller::factory()->create();
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);

    $cliente->delete();

    expect(Cliente::withTrashed()->find($cliente->id))->not->toBeNull();
    expect(Cliente::find($cliente->id))->toBeNull();
});

it('scopeActivos excluye clientes inactivos', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    Cliente::factory()->create(['taller_id' => $taller->id]);
    Cliente::factory()->inactivo()->create(['taller_id' => $taller->id]);

    expect(Cliente::activos()->count())->toBe(1);
});
