<?php

use App\Actions\Roles\AsignarRolAction;
use App\Filament\Erp\Resources\ClienteResource;
use App\Filament\Erp\Resources\EmpleadoResource;
use App\Filament\Erp\Resources\NotaVentaResource;
use App\Filament\Erp\Resources\OrdenTrabajoResource;
use App\Filament\Erp\Resources\ProveedorResource;
use App\Filament\Erp\Resources\RepuestoResource;
use App\Filament\Erp\Resources\ServicioResource;
use App\Filament\Erp\Resources\VehiculoResource;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\NotaVenta;
use App\Models\OrdenTrabajo;
use App\Models\Permiso;
use App\Models\Proveedor;
use App\Models\Repuesto;
use App\Models\Rol;
use App\Models\ServicioCatalogo;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use App\Models\Vehiculo;

/**
 * 020-seguridad-produccion §G: suite consolidada de IDOR (acceso cruzado entre talleres por
 * manipulación directa de ID) — no reemplaza los tests de `ResourcesAutorizacionTest.php` que ya
 * existen por feature (esos verifican que `getEloquentQuery()` filtra el listado), sino que
 * ejercita el caso que ninguno prueba explícitamente vía HTTP: un usuario con permiso real de
 * edición en SU taller pide, por URL directa, el registro de OTRO taller.
 *
 * Todos los Resources probados comparten el mismo mecanismo de defensa (`BelongsToTaller` global
 * scope filtrando por `session('taller_activo_id')`, del que `getEloquentQuery()` depende) — por
 * eso alcanza con probar la página de edición de cada uno, no ver+editar+eliminar por separado:
 * las tres resuelven el registro contra la misma query ya scopeada.
 */
function usuarioSeguridadConPermisoEnTaller(string $slug, int $tallerId): UsuarioSistema
{
    $permiso = Permiso::factory()->create(['slug' => $slug, 'activo' => true]);
    $rol = Rol::factory()->create(['taller_id' => $tallerId]);
    $rol->permisos()->attach($permiso->id);
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerId, asignadoPor: null);

    return $usuario;
}

it('ClienteResource: un usuario de un taller no puede editar por ID directo un cliente de otro taller', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    $clienteAjeno = Cliente::factory()->create(['taller_id' => $tallerB->id]);
    $usuario = usuarioSeguridadConPermisoEnTaller('clientes.editar', $tallerA->id);

    session(['taller_activo_id' => $tallerA->id]);
    $this->actingAs($usuario, 'sistema');

    $this->get(ClienteResource::getUrl('edit', ['record' => $clienteAjeno], panel: 'erp'))->assertNotFound();
});

it('VehiculoResource: un usuario de un taller no puede editar por ID directo un vehiculo de otro taller', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    $clienteAjeno = Cliente::factory()->create(['taller_id' => $tallerB->id]);
    $vehiculoAjeno = Vehiculo::factory()->create(['taller_id' => $tallerB->id, 'cliente_id' => $clienteAjeno->id]);
    $usuario = usuarioSeguridadConPermisoEnTaller('vehiculos.editar', $tallerA->id);

    session(['taller_activo_id' => $tallerA->id]);
    $this->actingAs($usuario, 'sistema');

    $this->get(VehiculoResource::getUrl('edit', ['record' => $vehiculoAjeno], panel: 'erp'))->assertNotFound();
});

it('EmpleadoResource: un usuario de un taller no puede editar por ID directo un empleado de otro taller', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    $empleadoAjeno = Empleado::factory()->create(['taller_id' => $tallerB->id]);
    $usuario = usuarioSeguridadConPermisoEnTaller('empleados.editar', $tallerA->id);

    session(['taller_activo_id' => $tallerA->id]);
    $this->actingAs($usuario, 'sistema');

    $this->get(EmpleadoResource::getUrl('edit', ['record' => $empleadoAjeno], panel: 'erp'))->assertNotFound();
});

it('ServicioResource: un usuario de un taller no puede editar por ID directo un servicio de otro taller', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    $servicioAjeno = ServicioCatalogo::factory()->create(['taller_id' => $tallerB->id]);
    $usuario = usuarioSeguridadConPermisoEnTaller('servicios.editar', $tallerA->id);

    session(['taller_activo_id' => $tallerA->id]);
    $this->actingAs($usuario, 'sistema');

    $this->get(ServicioResource::getUrl('edit', ['record' => $servicioAjeno], panel: 'erp'))->assertNotFound();
});

it('RepuestoResource: un usuario de un taller no puede editar por ID directo un repuesto de otro taller', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    $repuestoAjeno = Repuesto::factory()->create(['taller_id' => $tallerB->id]);
    $usuario = usuarioSeguridadConPermisoEnTaller('repuestos.editar', $tallerA->id);

    session(['taller_activo_id' => $tallerA->id]);
    $this->actingAs($usuario, 'sistema');

    $this->get(RepuestoResource::getUrl('edit', ['record' => $repuestoAjeno], panel: 'erp'))->assertNotFound();
});

it('ProveedorResource: un usuario de un taller no puede editar por ID directo un proveedor de otro taller', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    $proveedorAjeno = Proveedor::factory()->create(['taller_id' => $tallerB->id]);
    $usuario = usuarioSeguridadConPermisoEnTaller('proveedores.editar', $tallerA->id);

    session(['taller_activo_id' => $tallerA->id]);
    $this->actingAs($usuario, 'sistema');

    $this->get(ProveedorResource::getUrl('edit', ['record' => $proveedorAjeno], panel: 'erp'))->assertNotFound();
});

it('OrdenTrabajoResource: un usuario de un taller no puede editar por ID directo una orden de otro taller', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    $clienteAjeno = Cliente::factory()->create(['taller_id' => $tallerB->id]);
    $ordenAjena = OrdenTrabajo::factory()->create(['taller_id' => $tallerB->id, 'cliente_id' => $clienteAjeno->id]);
    $usuario = usuarioSeguridadConPermisoEnTaller('ordenes.editar', $tallerA->id);

    session(['taller_activo_id' => $tallerA->id]);
    $this->actingAs($usuario, 'sistema');

    $this->get(OrdenTrabajoResource::getUrl('edit', ['record' => $ordenAjena], panel: 'erp'))->assertNotFound();
});

it('NotaVentaResource: un usuario de un taller no puede editar por ID directo una nota de otro taller', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    $notaAjena = NotaVenta::factory()->create(['taller_id' => $tallerB->id]);
    $usuario = usuarioSeguridadConPermisoEnTaller('notas.editar', $tallerA->id);

    session(['taller_activo_id' => $tallerA->id]);
    $this->actingAs($usuario, 'sistema');

    $this->get(NotaVentaResource::getUrl('edit', ['record' => $notaAjena], panel: 'erp'))->assertNotFound();
});
