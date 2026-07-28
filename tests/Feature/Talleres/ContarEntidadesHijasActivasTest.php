<?php

use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\OrdenTrabajo;
use App\Models\Taller;
use App\Models\Vehiculo;

/**
 * `Taller::contarEntidadesHijasActivas()` cierra la brecha documentada en
 * `003-gestion-talleres/tasks.md` ("texto estático hasta que existan modelos con
 * BelongsToTaller"): ya existen (007+), así que el conteo real ya es posible.
 */
it('cuenta entidades hijas activas reales de un taller', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);

    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    Cliente::factory()->create(['taller_id' => $taller->id]);
    $clienteInactivo = Cliente::factory()->create(['taller_id' => $taller->id]);
    $clienteInactivo->delete();
    $vehiculo = Vehiculo::factory()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id]);
    Empleado::factory()->create(['taller_id' => $taller->id]);
    OrdenTrabajo::factory()->create([
        'taller_id' => $taller->id,
        'cliente_id' => $cliente->id,
        'vehiculo_id' => $vehiculo->id,
        'estado' => 'ANULADA',
    ]);
    OrdenTrabajo::factory()->create([
        'taller_id' => $taller->id,
        'cliente_id' => $cliente->id,
        'vehiculo_id' => $vehiculo->id,
        'estado' => 'PENDIENTE',
    ]);

    $conteos = $taller->contarEntidadesHijasActivas();

    expect($conteos['Clientes'])->toBe(2)
        ->and($conteos['Vehículos'])->toBe(1)
        ->and($conteos['Empleados'])->toBe(1)
        ->and($conteos['Órdenes de trabajo activas'])->toBe(1);
});

it('no ve datos de otro taller', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    Cliente::factory()->create(['taller_id' => $tallerA->id]);
    Cliente::factory()->create(['taller_id' => $tallerB->id]);

    $conteos = $tallerA->contarEntidadesHijasActivas();

    expect($conteos['Clientes'])->toBe(1);
});

it('devuelve todo en cero para un taller sin datos', function () {
    $taller = Taller::factory()->create();

    $conteos = $taller->contarEntidadesHijasActivas();

    expect(array_sum($conteos))->toBe(0);
});
