<?php

use App\Models\Empleado;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use Illuminate\Database\QueryException;

it('aisla empleados por taller: un taller no ve empleados de otro', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    Empleado::factory()->create(['taller_id' => $tallerA->id]);
    Empleado::factory()->create(['taller_id' => $tallerB->id]);

    session(['taller_activo_id' => $tallerA->id]);

    expect(Empleado::all())->toHaveCount(1);
    expect(Empleado::first()->taller_id)->toBe($tallerA->id);
});

it('auto-rellena taller_id desde la sesion al crear sin especificarlo', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);

    $empleado = Empleado::factory()->make(['taller_id' => null]);
    $empleado->save();

    expect($empleado->fresh()->taller_id)->toBe($taller->id);
});

it('rechaza en BD dos empleados con el mismo codigo en el mismo taller', function () {
    $taller = Taller::factory()->create();
    Empleado::factory()->create(['taller_id' => $taller->id, 'codigo' => 'EMP-001']);

    expect(fn () => Empleado::factory()->create(['taller_id' => $taller->id, 'codigo' => 'EMP-001']))
        ->toThrow(QueryException::class);
});

it('permite el mismo codigo de empleado en talleres distintos', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    Empleado::factory()->create(['taller_id' => $tallerA->id, 'codigo' => 'EMP-001']);

    $empleadoB = Empleado::factory()->create(['taller_id' => $tallerB->id, 'codigo' => 'EMP-001']);

    expect($empleadoB->exists)->toBeTrue();
});

it('un usuario sistema no puede ser empleado dos veces en el mismo taller', function () {
    $taller = Taller::factory()->create();
    $usuario = UsuarioSistema::factory()->create();
    Empleado::factory()->create(['taller_id' => $taller->id, 'usuario_sistema_id' => $usuario->id, 'codigo' => 'EMP-001']);

    expect(fn () => Empleado::factory()->create(['taller_id' => $taller->id, 'usuario_sistema_id' => $usuario->id, 'codigo' => 'EMP-002']))
        ->toThrow(QueryException::class);
});

it('permite multiples empleados sin usuario vinculado en el mismo taller', function () {
    $taller = Taller::factory()->create();
    Empleado::factory()->create(['taller_id' => $taller->id, 'usuario_sistema_id' => null]);
    $segundo = Empleado::factory()->create(['taller_id' => $taller->id, 'usuario_sistema_id' => null]);

    expect($segundo->exists)->toBeTrue();
});

it('soft-deletea un empleado en vez de borrarlo fisicamente', function () {
    $taller = Taller::factory()->create();
    $empleado = Empleado::factory()->create(['taller_id' => $taller->id]);

    $empleado->delete();

    expect(Empleado::withTrashed()->find($empleado->id))->not->toBeNull();
    expect(Empleado::find($empleado->id))->toBeNull();
});

it('scopeActivos excluye empleados inactivos', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    Empleado::factory()->create(['taller_id' => $taller->id]);
    Empleado::factory()->inactivo()->create(['taller_id' => $taller->id]);

    expect(Empleado::activos()->count())->toBe(1);
});

it('tieneAcceso() refleja si el empleado tiene usuario sistema vinculado', function () {
    $taller = Taller::factory()->create();
    $usuario = UsuarioSistema::factory()->create();
    $conAcceso = Empleado::factory()->create(['taller_id' => $taller->id, 'usuario_sistema_id' => $usuario->id]);
    $sinAcceso = Empleado::factory()->create(['taller_id' => $taller->id, 'usuario_sistema_id' => null]);

    expect($conAcceso->tieneAcceso())->toBeTrue();
    expect($sinAcceso->tieneAcceso())->toBeFalse();
});
