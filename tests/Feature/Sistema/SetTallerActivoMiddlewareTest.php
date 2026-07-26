<?php

use App\Actions\Roles\AsignarRolAction;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;

it('setea el taller activo en sesion automaticamente cuando el usuario tiene un solo taller', function () {
    $taller = Taller::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null]);
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $taller->id, asignadoPor: null);

    $this->actingAs($usuario, 'sistema')->get('/erp')->assertSuccessful();

    expect(session('taller_activo_id'))->toBe($taller->id);
});

it('mantiene el taller activo de sesion si sigue vigente entre varios talleres', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null]);
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerA->id, asignadoPor: null);
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerB->id, asignadoPor: null);

    $this->withSession(['taller_activo_id' => $tallerB->id])
        ->actingAs($usuario, 'sistema')
        ->get('/erp')
        ->assertSuccessful();

    expect(session('taller_activo_id'))->toBe($tallerB->id);
});

it('usa el primer taller vigente si la sesion no tiene uno valido entre varios', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null]);
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerA->id, asignadoPor: null);
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerB->id, asignadoPor: null);

    $this->withSession(['taller_activo_id' => 999999])
        ->actingAs($usuario, 'sistema')
        ->get('/erp')
        ->assertSuccessful();

    expect(session('taller_activo_id'))->toBe(min($tallerA->id, $tallerB->id));
});

it('un super admin no dispara SetTallerActivo en el panel /admin', function () {
    $rolSuperAdmin = Rol::factory()->create(['taller_id' => null, 'slug' => 'super-admin']);
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rolSuperAdmin, null, asignadoPor: null);

    $this->actingAs($usuario, 'sistema')->get('/admin')->assertSuccessful();

    expect(session('taller_activo_id'))->toBeNull();
});
