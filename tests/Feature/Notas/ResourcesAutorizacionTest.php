<?php

use App\Actions\Roles\AsignarRolAction;
use App\Filament\Erp\Resources\NotaVentaResource;
use App\Models\NotaVenta;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;

function usuarioConPermisosDeNotas(array $slugs, int $tallerId): UsuarioSistema
{
    $permisos = collect($slugs)->map(fn (string $slug) => Permiso::factory()->create(['slug' => $slug, 'activo' => true]));
    $rol = Rol::factory()->create(['taller_id' => $tallerId]);
    $rol->permisos()->attach($permisos->pluck('id'));
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerId, asignadoPor: null);

    return $usuario;
}

it('cada permiso (ver/crear/editar/anular/eliminar) es independiente', function () {
    $taller = Taller::factory()->create();
    $nota = NotaVenta::factory()->create(['taller_id' => $taller->id]);
    $soloVer = usuarioConPermisosDeNotas(['notas.ver'], $taller->id);
    $sinPermiso = UsuarioSistema::factory()->create();

    session(['taller_activo_id' => $taller->id]);

    $this->actingAs($soloVer, 'sistema');
    expect(NotaVentaResource::canViewAny())->toBeTrue();
    expect(NotaVentaResource::canCreate())->toBeFalse();
    expect(NotaVentaResource::canEdit($nota))->toBeFalse();
    expect(NotaVentaResource::canDelete($nota))->toBeFalse();

    $this->actingAs($sinPermiso, 'sistema');
    expect(NotaVentaResource::canViewAny())->toBeFalse();
});

it('solo lista notas de venta del taller activo', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    NotaVenta::factory()->create(['taller_id' => $tallerA->id]);
    NotaVenta::factory()->create(['taller_id' => $tallerB->id]);

    session(['taller_activo_id' => $tallerA->id]);

    expect(NotaVentaResource::getEloquentQuery()->pluck('taller_id')->unique()->all())->toBe([$tallerA->id]);
});

it('las paginas de listado, creacion y edicion cargan con permiso', function () {
    $taller = Taller::factory()->create();
    $nota = NotaVenta::factory()->create(['taller_id' => $taller->id]);
    $usuario = usuarioConPermisosDeNotas(['notas.ver', 'notas.crear', 'notas.editar'], $taller->id);

    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    $this->get(NotaVentaResource::getUrl('index', panel: 'erp'))->assertSuccessful();
    $this->get(NotaVentaResource::getUrl('create', panel: 'erp'))->assertSuccessful();
    $this->get(NotaVentaResource::getUrl('edit', ['record' => $nota], panel: 'erp'))->assertSuccessful();
});
