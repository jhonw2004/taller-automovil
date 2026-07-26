<?php

use App\Actions\Roles\AsignarRolAction;
use App\Exceptions\BusinessException;
use App\Models\Rol;
use App\Models\UsuarioSistema;

it('rechaza la asignación si el usuario sistema está inactivo', function () {
    $usuario = UsuarioSistema::factory()->create(['activo' => false]);
    $rol = Rol::factory()->create(['taller_id' => null]);

    expect(fn () => app(AsignarRolAction::class)->execute($usuario, $rol, null))
        ->toThrow(BusinessException::class);
});

it('rechaza la asignación si el rol está inactivo', function () {
    $usuario = UsuarioSistema::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null, 'activo' => false]);

    expect(fn () => app(AsignarRolAction::class)->execute($usuario, $rol, null))
        ->toThrow(BusinessException::class);
});
