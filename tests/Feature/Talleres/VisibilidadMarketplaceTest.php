<?php

use App\Actions\Roles\AsignarRolAction;
use App\Actions\Talleres\CambiarVisibilidadTallerAction;
use App\Exceptions\BusinessException;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;

it('aparece en el marketplace solo cuando esta activo, visible y con geom', function () {
    $visible = Taller::factory()->visible()->create();
    $sinVisible = Taller::factory()->create(['visible_en_mapa' => false]);
    $suspendido = Taller::factory()->visible()->suspendido()->create();
    $inactivo = Taller::factory()->visible()->inactivo()->create();

    $resultado = Taller::visibleEnMarketplace()->pluck('id');

    expect($resultado)->toContain($visible->id)
        ->and($resultado)->not->toContain($sinVisible->id)
        ->and($resultado)->not->toContain($suspendido->id)
        ->and($resultado)->not->toContain($inactivo->id);
});

it('excluye del marketplace a un taller soft-deleteado aunque cumpla el resto de condiciones', function () {
    $taller = Taller::factory()->visible()->create();
    $taller->delete();

    $resultado = Taller::visibleEnMarketplace()->pluck('id');

    expect($resultado)->not->toContain($taller->id);
});

it('permite marcar visible en mapa a super admin o con permiso taller.configurar', function () {
    $taller = Taller::factory()->create();
    $superAdmin = UsuarioSistema::factory()->create();
    $rolSuperAdmin = Rol::factory()->create(['slug' => 'super-admin', 'taller_id' => null]);
    app(AsignarRolAction::class)->execute($superAdmin, $rolSuperAdmin, null);

    $resultado = app(CambiarVisibilidadTallerAction::class)->execute($taller, true, $superAdmin);

    expect($resultado->visible_en_mapa)->toBeTrue();
});

it('permite marcar visible en mapa con permiso taller.configurar en ese taller', function () {
    $taller = Taller::factory()->create();
    $actor = UsuarioSistema::factory()->create();
    $permiso = Permiso::factory()->create(['slug' => 'taller.configurar']);
    $rol = Rol::factory()->create(['taller_id' => $taller->id]);
    $rol->permisos()->attach($permiso->id);
    app(AsignarRolAction::class)->execute($actor, $rol, $taller->id);

    $resultado = app(CambiarVisibilidadTallerAction::class)->execute($taller, true, $actor);

    expect($resultado->visible_en_mapa)->toBeTrue();
});

it('rechaza cambiar visibilidad sin permiso taller.configurar ni ser super admin', function () {
    $taller = Taller::factory()->create();
    $actor = UsuarioSistema::factory()->create();

    expect(fn () => app(CambiarVisibilidadTallerAction::class)->execute($taller, true, $actor))
        ->toThrow(BusinessException::class);
});

it('rechaza marcar visible en mapa a un taller sin lat/lon/geom', function () {
    $taller = Taller::factory()->create(['lat' => -17.78, 'lon' => -63.18]);
    // Simula un taller sin geolocalización confirmada aún (defensivo: en el esquema actual
    // lat/lon/geom son NOT NULL, pero la Action valida igual por la regla explícita del spec).
    $taller->lat = null;
    $taller->lon = null;

    $superAdmin = UsuarioSistema::factory()->create();
    $rolSuperAdmin = Rol::factory()->create(['slug' => 'super-admin', 'taller_id' => null]);
    app(AsignarRolAction::class)->execute($superAdmin, $rolSuperAdmin, null);

    expect(fn () => app(CambiarVisibilidadTallerAction::class)->execute($taller, true, $superAdmin))
        ->toThrow(BusinessException::class);
});
