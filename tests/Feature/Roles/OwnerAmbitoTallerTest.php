<?php

use App\Actions\Roles\AsignarRolAction;
use App\Exceptions\BusinessException;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;

function crearOwnerDeTaller(Taller $taller): UsuarioSistema
{
    $rolOwner = Rol::firstOrCreate(['slug' => 'owner', 'taller_id' => null], ['nombre' => 'Propietario', 'es_sistema' => true, 'activo' => true]);
    $owner = UsuarioSistema::factory()->create();

    app(AsignarRolAction::class)->execute($owner, $rolOwner, $taller->id, asignadoPor: null);

    return $owner;
}

it('un owner no puede asignar roles fuera de su propio taller', function () {
    $tallerPropio = Taller::factory()->create();
    $tallerAjeno = Taller::factory()->create();
    $owner = crearOwnerDeTaller($tallerPropio);

    $rolCajero = Rol::factory()->create(['taller_id' => null, 'slug' => 'cajero']);
    $empleado = UsuarioSistema::factory()->create();

    expect(fn () => app(AsignarRolAction::class)->execute($empleado, $rolCajero, $tallerAjeno->id, asignadoPor: $owner))
        ->toThrow(BusinessException::class);
});

it('un owner sí puede asignar roles dentro de su propio taller', function () {
    $taller = Taller::factory()->create();
    $owner = crearOwnerDeTaller($taller);

    $rolCajero = Rol::factory()->create(['taller_id' => null, 'slug' => 'cajero']);
    $empleado = UsuarioSistema::factory()->create();

    $asignacion = app(AsignarRolAction::class)->execute($empleado, $rolCajero, $taller->id, asignadoPor: $owner);

    expect($asignacion->taller_id)->toBe($taller->id);
});

it('un owner no puede asignar roles globales', function () {
    $taller = Taller::factory()->create();
    $owner = crearOwnerDeTaller($taller);

    $rolGlobal = Rol::factory()->create(['taller_id' => null, 'slug' => 'marketplace-user']);
    $objetivo = UsuarioSistema::factory()->create();

    expect(fn () => app(AsignarRolAction::class)->execute($objetivo, $rolGlobal, null, asignadoPor: $owner))
        ->toThrow(BusinessException::class);
});
