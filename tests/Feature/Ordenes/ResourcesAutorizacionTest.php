<?php

use App\Actions\Roles\AsignarRolAction;
use App\Filament\Erp\Resources\OrdenTrabajoResource;
use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;

function usuarioConPermisosDeOrdenes(array $slugs, int $tallerId): UsuarioSistema
{
    $permisos = collect($slugs)->map(fn (string $slug) => Permiso::factory()->create(['slug' => $slug, 'activo' => true]));
    $rol = Rol::factory()->create(['taller_id' => $tallerId]);
    $rol->permisos()->attach($permisos->pluck('id'));
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerId, asignadoPor: null);

    return $usuario;
}

it('cada permiso (ver/crear/editar/eliminar/anular) es independiente', function () {
    $taller = Taller::factory()->create();
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $orden = OrdenTrabajo::factory()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id]);
    $soloVer = usuarioConPermisosDeOrdenes(['ordenes.ver'], $taller->id);
    $sinPermiso = UsuarioSistema::factory()->create();

    session(['taller_activo_id' => $taller->id]);

    $this->actingAs($soloVer, 'sistema');
    expect(OrdenTrabajoResource::canViewAny())->toBeTrue();
    expect(OrdenTrabajoResource::canCreate())->toBeFalse();
    expect(OrdenTrabajoResource::canEdit($orden))->toBeFalse();
    expect(OrdenTrabajoResource::canDelete($orden))->toBeFalse();

    $this->actingAs($sinPermiso, 'sistema');
    expect(OrdenTrabajoResource::canViewAny())->toBeFalse();
});

it('una orden ANULADA nunca es editable aunque el usuario tenga el permiso', function () {
    $taller = Taller::factory()->create();
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $orden = OrdenTrabajo::factory()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id, 'estado' => 'ANULADA']);
    $usuario = usuarioConPermisosDeOrdenes(['ordenes.ver', 'ordenes.editar'], $taller->id);

    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    expect(OrdenTrabajoResource::canEdit($orden))->toBeFalse();
});

it('solo lista ordenes del taller activo', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    $clienteA = Cliente::factory()->create(['taller_id' => $tallerA->id]);
    $clienteB = Cliente::factory()->create(['taller_id' => $tallerB->id]);
    OrdenTrabajo::factory()->create(['taller_id' => $tallerA->id, 'cliente_id' => $clienteA->id]);
    OrdenTrabajo::factory()->create(['taller_id' => $tallerB->id, 'cliente_id' => $clienteB->id]);

    session(['taller_activo_id' => $tallerA->id]);

    expect(OrdenTrabajoResource::getEloquentQuery()->pluck('taller_id')->unique()->all())->toBe([$tallerA->id]);
});

it('las paginas de listado, creacion y edicion cargan con permiso', function () {
    $taller = Taller::factory()->create();
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $orden = OrdenTrabajo::factory()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id]);
    $usuario = usuarioConPermisosDeOrdenes(['ordenes.ver', 'ordenes.crear', 'ordenes.editar'], $taller->id);

    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    $this->get(OrdenTrabajoResource::getUrl('index', panel: 'erp'))->assertSuccessful();
    $this->get(OrdenTrabajoResource::getUrl('create', panel: 'erp'))->assertSuccessful();
    $this->get(OrdenTrabajoResource::getUrl('edit', ['record' => $orden], panel: 'erp'))->assertSuccessful();
});
