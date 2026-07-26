<?php

use App\Actions\Roles\AsignarRolAction;
use App\Exceptions\BusinessException;
use App\Models\AsignacionRol;
use App\Models\Rol;
use App\Models\UsuarioSistema;

it('un usuario que no es super admin no puede asignar el rol SUPER_ADMIN', function () {
    $rolSuperAdmin = Rol::factory()->create(['taller_id' => null, 'slug' => 'super-admin']);
    $asignadoPor = UsuarioSistema::factory()->create();
    $objetivo = UsuarioSistema::factory()->create();

    expect(fn () => app(AsignarRolAction::class)->execute($objetivo, $rolSuperAdmin, null, asignadoPor: $asignadoPor))
        ->toThrow(BusinessException::class);
});

it('un super admin sí puede asignar el rol SUPER_ADMIN a otro usuario', function () {
    $rolSuperAdmin = Rol::factory()->create(['taller_id' => null, 'slug' => 'super-admin']);

    $superAdmin = UsuarioSistema::factory()->create();
    // Bootstrap: el primer super admin se auto-asigna vía seeder, sin `asignadoPor`.
    app(AsignarRolAction::class)->execute($superAdmin, $rolSuperAdmin, null, asignadoPor: null);

    $objetivo = UsuarioSistema::factory()->create();
    $asignacion = app(AsignarRolAction::class)->execute($objetivo, $rolSuperAdmin, null, asignadoPor: $superAdmin);

    expect($asignacion)->toBeInstanceOf(AsignacionRol::class);
});
