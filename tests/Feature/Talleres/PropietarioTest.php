<?php

use App\Actions\Roles\AsignarRolAction;
use App\Actions\Talleres\CambiarPropietarioTallerAction;
use App\Exceptions\BusinessException;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;

it('el super admin puede cambiar el propietario si el nuevo dueño ya tiene rol OWNER en ese taller', function () {
    $taller = Taller::factory()->create();
    $superAdmin = UsuarioSistema::factory()->create();
    $rolSuperAdmin = Rol::factory()->create(['slug' => 'super-admin', 'taller_id' => null]);
    app(AsignarRolAction::class)->execute($superAdmin, $rolSuperAdmin, null);

    $nuevoPropietario = UsuarioSistema::factory()->create();
    $rolOwner = Rol::factory()->create(['slug' => 'owner', 'taller_id' => $taller->id]);
    app(AsignarRolAction::class)->execute($nuevoPropietario, $rolOwner, $taller->id);

    $resultado = app(CambiarPropietarioTallerAction::class)->execute($taller, $nuevoPropietario, $superAdmin);

    expect($resultado->propietario_usuario_sistema_id)->toBe($nuevoPropietario->id);
});

it('rechaza el cambio de propietario si el nuevo dueño no tiene rol OWNER vigente en ese taller', function () {
    $taller = Taller::factory()->create();
    $superAdmin = UsuarioSistema::factory()->create();
    $rolSuperAdmin = Rol::factory()->create(['slug' => 'super-admin', 'taller_id' => null]);
    app(AsignarRolAction::class)->execute($superAdmin, $rolSuperAdmin, null);

    $nuevoPropietario = UsuarioSistema::factory()->create();

    expect(fn () => app(CambiarPropietarioTallerAction::class)->execute($taller, $nuevoPropietario, $superAdmin))
        ->toThrow(BusinessException::class);
});

it('rechaza el cambio de propietario si el actor no es super admin ni el propietario actual', function () {
    $propietarioActual = UsuarioSistema::factory()->create();
    $taller = Taller::factory()->create(['propietario_usuario_sistema_id' => $propietarioActual->id]);

    $intruso = UsuarioSistema::factory()->create();
    $nuevoPropietario = UsuarioSistema::factory()->create();
    $rolOwner = Rol::factory()->create(['slug' => 'owner', 'taller_id' => $taller->id]);
    app(AsignarRolAction::class)->execute($nuevoPropietario, $rolOwner, $taller->id);

    expect(fn () => app(CambiarPropietarioTallerAction::class)->execute($taller, $nuevoPropietario, $intruso))
        ->toThrow(BusinessException::class);
});

it('el propietario actual puede transferir el taller a un nuevo dueño con rol OWNER', function () {
    $propietarioActual = UsuarioSistema::factory()->create();
    $taller = Taller::factory()->create(['propietario_usuario_sistema_id' => $propietarioActual->id]);

    $nuevoPropietario = UsuarioSistema::factory()->create();
    $rolOwner = Rol::factory()->create(['slug' => 'owner', 'taller_id' => $taller->id]);
    app(AsignarRolAction::class)->execute($nuevoPropietario, $rolOwner, $taller->id);

    $resultado = app(CambiarPropietarioTallerAction::class)->execute($taller, $nuevoPropietario, $propietarioActual);

    expect($resultado->propietario_usuario_sistema_id)->toBe($nuevoPropietario->id);
});
