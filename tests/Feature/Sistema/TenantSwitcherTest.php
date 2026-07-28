<?php

use App\Actions\Roles\AsignarRolAction;
use App\Filament\Erp\Widgets\TenantSwitcher;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;

it('no se muestra si el usuario tiene un solo taller vigente', function () {
    $taller = Taller::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null]);
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $taller->id, asignadoPor: null);

    $this->actingAs($usuario, 'sistema');

    expect(TenantSwitcher::canView())->toBeFalse();
});

it('se muestra si el usuario tiene mas de un taller vigente', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null]);
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerA->id, asignadoPor: null);
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerB->id, asignadoPor: null);

    $this->actingAs($usuario, 'sistema');

    expect(TenantSwitcher::canView())->toBeTrue();
});

it('no se muestra para un usuario sin sesion', function () {
    expect(TenantSwitcher::canView())->toBeFalse();
});
