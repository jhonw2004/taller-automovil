<?php

use App\Actions\SequentialCodeGenerator;
use App\Models\OrdenTrabajo;
use App\Models\Taller;

/**
 * `RefreshDatabase` envuelve cada test en una transacción propia (ver `tests/Pest.php`),
 * así que un test de concurrencia real con múltiples procesos de PHP no vería las filas
 * de este test (no comprometidas fuera de la transacción). En su lugar, se valida que
 * `lockForUpdate()` produce números estrictamente secuenciales sin huecos ni repeticiones
 * cuando cada código generado se persiste antes de pedir el siguiente (uso real: generar
 * y guardar ocurren en la misma transacción de la Action que crea el registro) — la garantía
 * de exclusión mutua real bajo concurrencia la da el `FOR UPDATE` de Postgres, ya usado por
 * el generador.
 */
it('genera codigos unicos y estrictamente secuenciales a medida que se persisten', function () {
    $taller = Taller::factory()->create();

    $codigos = collect(range(1, 20))->map(function () use ($taller) {
        $codigo = SequentialCodeGenerator::generate($taller->id, 'OT', 'ordenes_trabajo');
        OrdenTrabajo::factory()->create(['taller_id' => $taller->id, 'codigo' => $codigo]);

        return $codigo;
    });

    expect($codigos->unique())->toHaveCount(20);

    $numeros = $codigos->map(fn (string $codigo) => (int) substr($codigo, -3))->values();
    expect($numeros->all())->toBe(range(1, 20));
});

it('no colisiona entre talleres distintos aunque compartan el mismo prefijo y año', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();

    $codigoA = SequentialCodeGenerator::generate($tallerA->id, 'OT', 'ordenes_trabajo');
    $codigoB = SequentialCodeGenerator::generate($tallerB->id, 'OT', 'ordenes_trabajo');

    expect($codigoA)->toBe($codigoB);
});

it('reinicia la numeracion desde 1 para un taller que aun no tiene ordenes', function () {
    $tallerConHistorial = Taller::factory()->create();
    OrdenTrabajo::factory()->create(['taller_id' => $tallerConHistorial->id, 'codigo' => 'OT-'.now()->format('Y').'-001']);

    $tallerNuevo = Taller::factory()->create();

    expect(SequentialCodeGenerator::generate($tallerNuevo->id, 'OT', 'ordenes_trabajo'))
        ->toBe('OT-'.now()->format('Y').'-001');
});
