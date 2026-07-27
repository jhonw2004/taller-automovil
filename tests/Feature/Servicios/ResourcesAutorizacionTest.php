<?php

use App\Actions\Roles\AsignarRolAction;
use App\Filament\Erp\Resources\ServicioResource;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\ServicioCatalogo;
use App\Models\Taller;
use App\Models\UsuarioSistema;

function usuarioConPermisosDeServicio(array $slugs, int $tallerId): UsuarioSistema
{
    $permisos = collect($slugs)->map(fn (string $slug) => Permiso::factory()->create(['slug' => $slug, 'activo' => true]));
    $rol = Rol::factory()->create(['taller_id' => $tallerId]);
    $rol->permisos()->attach($permisos->pluck('id'));
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerId, asignadoPor: null);

    return $usuario;
}

it('ServicioResource: cada permiso (ver/crear/editar/eliminar) es independiente', function () {
    $taller = Taller::factory()->create();
    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $taller->id]);
    $soloVer = usuarioConPermisosDeServicio(['servicios.ver'], $taller->id);
    $sinPermiso = UsuarioSistema::factory()->create();

    session(['taller_activo_id' => $taller->id]);

    $this->actingAs($soloVer, 'sistema');
    expect(ServicioResource::canViewAny())->toBeTrue();
    expect(ServicioResource::canCreate())->toBeFalse();
    expect(ServicioResource::canEdit($servicio))->toBeFalse();
    expect(ServicioResource::canDelete($servicio))->toBeFalse();

    $this->actingAs($sinPermiso, 'sistema');
    expect(ServicioResource::canViewAny())->toBeFalse();
});

it('ServicioResource: solo lista servicios del taller activo', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    ServicioCatalogo::factory()->create(['taller_id' => $tallerA->id]);
    ServicioCatalogo::factory()->create(['taller_id' => $tallerB->id]);

    session(['taller_activo_id' => $tallerA->id]);

    expect(ServicioResource::getEloquentQuery()->pluck('taller_id')->unique()->all())->toBe([$tallerA->id]);
});

it('ServicioResource: las paginas de listado, creacion y edicion cargan con permiso', function () {
    $taller = Taller::factory()->create();
    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $taller->id]);
    $usuario = usuarioConPermisosDeServicio(['servicios.ver', 'servicios.crear', 'servicios.editar'], $taller->id);

    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    $this->get(ServicioResource::getUrl('index', panel: 'erp'))->assertSuccessful();
    $this->get(ServicioResource::getUrl('create', panel: 'erp'))->assertSuccessful();
    $this->get(ServicioResource::getUrl('edit', ['record' => $servicio], panel: 'erp'))->assertSuccessful();
});
