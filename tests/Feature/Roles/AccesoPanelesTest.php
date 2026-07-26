<?php

use App\Actions\Roles\AsignarRolAction;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use Filament\Facades\Filament;

it('deniega el acceso al panel /admin a un usuario sin rol SUPER_ADMIN', function () {
    $usuario = UsuarioSistema::factory()->create();

    expect($usuario->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});

it('permite el acceso al panel /admin a un usuario con rol SUPER_ADMIN vigente', function () {
    $rolSuperAdmin = Rol::factory()->create(['taller_id' => null, 'slug' => 'super-admin']);
    $usuario = UsuarioSistema::factory()->create();

    app(AsignarRolAction::class)->execute($usuario, $rolSuperAdmin, null, asignadoPor: null);

    expect($usuario->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
});

it('deniega el acceso al ERP cuando el usuario pierde su única asignación activa a un taller', function () {
    $taller = Taller::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null]);
    $usuario = UsuarioSistema::factory()->create();

    $asignacion = app(AsignarRolAction::class)->execute($usuario, $rol, $taller->id, asignadoPor: null);

    $panelErp = Filament::getPanel('erp');
    expect($usuario->canAccessPanel($panelErp))->toBeTrue();

    $asignacion->update(['activo' => false]);
    $usuario->refresh();

    expect($usuario->canAccessPanel($panelErp))->toBeFalse();
});

it('deniega el acceso a todo panel si el usuario sistema está inactivo, aunque tenga rol', function () {
    $rolSuperAdmin = Rol::factory()->create(['taller_id' => null, 'slug' => 'super-admin']);
    $usuario = UsuarioSistema::factory()->create();

    app(AsignarRolAction::class)->execute($usuario, $rolSuperAdmin, null, asignadoPor: null);
    $usuario->update(['activo' => false]);
    $usuario->refresh();

    expect($usuario->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});
