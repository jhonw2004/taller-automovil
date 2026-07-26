<?php

use App\Actions\Roles\AsignarRolAction;
use App\Exceptions\BusinessException;
use App\Models\AsignacionRol;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;

it('rechaza la asignación cuando el taller no coincide con el taller del rol', function () {
    $tallerDelRol = Taller::factory()->create();
    $otroTaller = Taller::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => $tallerDelRol->id]);
    $usuario = UsuarioSistema::factory()->create();

    expect(fn () => app(AsignarRolAction::class)->execute($usuario, $rol, $otroTaller->id))
        ->toThrow(BusinessException::class);
});

it('acepta la asignación cuando el taller coincide con el taller del rol', function () {
    $taller = Taller::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => $taller->id]);
    $usuario = UsuarioSistema::factory()->create();

    $asignacion = app(AsignarRolAction::class)->execute($usuario, $rol, $taller->id);

    expect($asignacion)->toBeInstanceOf(AsignacionRol::class)
        ->and($asignacion->taller_id)->toBe($taller->id);
});

it('rechaza una asignación duplicada de mismo usuario, rol y taller', function () {
    $taller = Taller::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null]);
    $usuario = UsuarioSistema::factory()->create();

    app(AsignarRolAction::class)->execute($usuario, $rol, $taller->id);

    expect(fn () => app(AsignarRolAction::class)->execute($usuario, $rol, $taller->id))
        ->toThrow(BusinessException::class);
});
