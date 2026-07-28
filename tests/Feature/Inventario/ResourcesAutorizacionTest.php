<?php

use App\Actions\Roles\AsignarRolAction;
use App\Filament\Erp\Resources\MovimientoInventarioResource;
use App\Filament\Erp\Resources\ProveedorResource;
use App\Filament\Erp\Resources\RepuestoResource;
use App\Models\InventarioMovimiento;
use App\Models\Permiso;
use App\Models\Proveedor;
use App\Models\Repuesto;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;

function usuarioConPermisosDeInventario(array $slugs, int $tallerId): UsuarioSistema
{
    $permisos = collect($slugs)->map(fn (string $slug) => Permiso::factory()->create(['slug' => $slug, 'activo' => true]));
    $rol = Rol::factory()->create(['taller_id' => $tallerId]);
    $rol->permisos()->attach($permisos->pluck('id'));
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerId, asignadoPor: null);

    return $usuario;
}

// --- RepuestoResource ---

it('RepuestoResource: cada permiso (ver/crear/editar/eliminar) es independiente', function () {
    $taller = Taller::factory()->create();
    $repuesto = Repuesto::factory()->create(['taller_id' => $taller->id]);
    $soloVer = usuarioConPermisosDeInventario(['repuestos.ver'], $taller->id);
    $sinPermiso = UsuarioSistema::factory()->create();

    session(['taller_activo_id' => $taller->id]);

    $this->actingAs($soloVer, 'sistema');
    expect(RepuestoResource::canViewAny())->toBeTrue();
    expect(RepuestoResource::canCreate())->toBeFalse();
    expect(RepuestoResource::canEdit($repuesto))->toBeFalse();
    expect(RepuestoResource::canDelete($repuesto))->toBeFalse();

    $this->actingAs($sinPermiso, 'sistema');
    expect(RepuestoResource::canViewAny())->toBeFalse();
});

it('RepuestoResource: solo lista repuestos del taller activo', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    Repuesto::factory()->create(['taller_id' => $tallerA->id]);
    Repuesto::factory()->create(['taller_id' => $tallerB->id]);

    session(['taller_activo_id' => $tallerA->id]);

    expect(RepuestoResource::getEloquentQuery()->pluck('taller_id')->unique()->all())->toBe([$tallerA->id]);
});

it('RepuestoResource: las paginas de listado, creacion y edicion cargan con permiso', function () {
    $taller = Taller::factory()->create();
    $repuesto = Repuesto::factory()->create(['taller_id' => $taller->id]);
    $usuario = usuarioConPermisosDeInventario(['repuestos.ver', 'repuestos.crear', 'repuestos.editar'], $taller->id);

    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    $this->get(RepuestoResource::getUrl('index', panel: 'erp'))->assertSuccessful();
    $this->get(RepuestoResource::getUrl('create', panel: 'erp'))->assertSuccessful();
    $this->get(RepuestoResource::getUrl('edit', ['record' => $repuesto], panel: 'erp'))->assertSuccessful();
});

// --- ProveedorResource ---

it('ProveedorResource: cada permiso (ver/crear/editar/eliminar) es independiente', function () {
    $taller = Taller::factory()->create();
    $proveedor = Proveedor::factory()->create(['taller_id' => $taller->id]);
    $soloVer = usuarioConPermisosDeInventario(['proveedores.ver'], $taller->id);
    $sinPermiso = UsuarioSistema::factory()->create();

    session(['taller_activo_id' => $taller->id]);

    $this->actingAs($soloVer, 'sistema');
    expect(ProveedorResource::canViewAny())->toBeTrue();
    expect(ProveedorResource::canCreate())->toBeFalse();
    expect(ProveedorResource::canEdit($proveedor))->toBeFalse();
    expect(ProveedorResource::canDelete($proveedor))->toBeFalse();

    $this->actingAs($sinPermiso, 'sistema');
    expect(ProveedorResource::canViewAny())->toBeFalse();
});

it('ProveedorResource: solo lista proveedores del taller activo', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    Proveedor::factory()->create(['taller_id' => $tallerA->id]);
    Proveedor::factory()->create(['taller_id' => $tallerB->id]);

    session(['taller_activo_id' => $tallerA->id]);

    expect(ProveedorResource::getEloquentQuery()->pluck('taller_id')->unique()->all())->toBe([$tallerA->id]);
});

it('ProveedorResource: las paginas de listado, creacion y edicion cargan con permiso', function () {
    $taller = Taller::factory()->create();
    $proveedor = Proveedor::factory()->create(['taller_id' => $taller->id]);
    $usuario = usuarioConPermisosDeInventario(['proveedores.ver', 'proveedores.crear', 'proveedores.editar'], $taller->id);

    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    $this->get(ProveedorResource::getUrl('index', panel: 'erp'))->assertSuccessful();
    $this->get(ProveedorResource::getUrl('create', panel: 'erp'))->assertSuccessful();
    $this->get(ProveedorResource::getUrl('edit', ['record' => $proveedor], panel: 'erp'))->assertSuccessful();
});

// --- MovimientoInventarioResource (solo lectura) ---

it('MovimientoInventarioResource: requiere permiso inventario.ver y nunca permite crear/editar/eliminar', function () {
    $taller = Taller::factory()->create();
    $repuesto = Repuesto::factory()->create(['taller_id' => $taller->id]);
    $movimiento = InventarioMovimiento::factory()->create(['taller_id' => $taller->id, 'repuesto_id' => $repuesto->id]);
    $conPermiso = usuarioConPermisosDeInventario(['inventario.ver'], $taller->id);
    $sinPermiso = UsuarioSistema::factory()->create();

    session(['taller_activo_id' => $taller->id]);

    $this->actingAs($conPermiso, 'sistema');
    expect(MovimientoInventarioResource::canViewAny())->toBeTrue();
    expect(MovimientoInventarioResource::canCreate())->toBeFalse();
    expect(MovimientoInventarioResource::canEdit($movimiento))->toBeFalse();
    expect(MovimientoInventarioResource::canDelete($movimiento))->toBeFalse();

    $this->actingAs($sinPermiso, 'sistema');
    expect(MovimientoInventarioResource::canViewAny())->toBeFalse();
});

it('MovimientoInventarioResource: solo lista movimientos del taller activo y la pagina de listado carga', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    $repuestoA = Repuesto::factory()->create(['taller_id' => $tallerA->id]);
    $repuestoB = Repuesto::factory()->create(['taller_id' => $tallerB->id]);
    InventarioMovimiento::factory()->create(['taller_id' => $tallerA->id, 'repuesto_id' => $repuestoA->id]);
    InventarioMovimiento::factory()->create(['taller_id' => $tallerB->id, 'repuesto_id' => $repuestoB->id]);

    $usuario = usuarioConPermisosDeInventario(['inventario.ver'], $tallerA->id);
    session(['taller_activo_id' => $tallerA->id]);
    $this->actingAs($usuario, 'sistema');

    expect(MovimientoInventarioResource::getEloquentQuery()->pluck('taller_id')->unique()->all())->toBe([$tallerA->id]);
    $this->get(MovimientoInventarioResource::getUrl('index', panel: 'erp'))->assertSuccessful();
});
