<?php

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Database\Seeder;

/**
 * Roles de sistema (`es_sistema = true`, `taller_id = null`): son plantillas globales que
 * luego se asignan a un taller concreto vía `asignaciones_rol.taller_id` (AsignarRolAction),
 * no se duplica una fila de rol por cada taller. Mapping de permisos: specs/017-infraestructura-sistema/plan.md §8.
 *
 * Los roles personalizados (creados por un admin de taller) no pasan por este seeder —
 * se crean con `taller_id` propio y su admin les asigna permisos manualmente.
 */
class RolSistemaSeeder extends Seeder
{
    public function run(): void
    {
        $this->crear('super-admin', 'Super Admin', Permiso::pluck('slug')->all());

        $this->crear('owner', 'Propietario', [
            'taller.ver', 'taller.editar', 'taller.configurar', 'taller.cambiar_propietario',
            'clientes.ver', 'clientes.crear', 'clientes.editar', 'clientes.eliminar',
            'vehiculos.ver', 'vehiculos.crear', 'vehiculos.editar', 'vehiculos.eliminar',
            'empleados.ver', 'empleados.crear', 'empleados.editar', 'empleados.eliminar',
            'usuarios.ver', 'usuarios.gestionar',
            'servicios.ver', 'servicios.crear', 'servicios.editar', 'servicios.eliminar',
            'repuestos.ver', 'repuestos.crear', 'repuestos.editar', 'repuestos.eliminar',
            'inventario.ver', 'inventario.ajustar',
            'proveedores.ver', 'proveedores.crear', 'proveedores.editar', 'proveedores.eliminar',
            'ordenes.ver', 'ordenes.crear', 'ordenes.editar', 'ordenes.anular', 'ordenes.eliminar',
            'notas.ver', 'notas.crear', 'notas.editar', 'notas.anular', 'notas.eliminar',
            'pagos.ver', 'pagos.registrar', 'pagos.anular',
            'roles.ver', 'roles.crear', 'roles.editar', 'roles.eliminar',
            'auditoria.ver',
            'notificaciones.ver',
        ]);

        $this->crear('shop-admin', 'Administrador de Taller', [
            'taller.ver', 'taller.editar', 'taller.configurar',
            'clientes.ver', 'clientes.crear', 'clientes.editar', 'clientes.eliminar',
            'vehiculos.ver', 'vehiculos.crear', 'vehiculos.editar', 'vehiculos.eliminar',
            'empleados.ver', 'empleados.crear', 'empleados.editar',
            'servicios.ver', 'servicios.crear', 'servicios.editar', 'servicios.eliminar',
            'repuestos.ver', 'repuestos.crear', 'repuestos.editar', 'repuestos.eliminar',
            'inventario.ver', 'inventario.ajustar',
            'proveedores.ver', 'proveedores.crear', 'proveedores.editar', 'proveedores.eliminar',
            'ordenes.ver', 'ordenes.crear', 'ordenes.editar',
            'notas.ver', 'notas.crear', 'notas.editar',
            'pagos.ver', 'pagos.registrar', 'pagos.anular',
            'roles.ver',
            'notificaciones.ver',
        ]);

        $this->crear('mecanico', 'Mecánico', [
            'ordenes.ver', 'ordenes.editar',
            'inventario.ver',
        ]);

        $this->crear('cajero', 'Cajero', [
            'notas.ver', 'notas.crear', 'notas.editar',
            'pagos.ver', 'pagos.registrar',
            'clientes.ver', 'clientes.crear',
        ]);

        $this->crear('recepcionista', 'Recepcionista', [
            'ordenes.ver', 'ordenes.crear',
            'clientes.ver', 'clientes.crear',
            'vehiculos.ver', 'vehiculos.crear',
        ]);

        $this->crear('supervisor', 'Supervisor', [
            'ordenes.ver', 'ordenes.crear', 'ordenes.editar', 'ordenes.anular',
            'notas.ver', 'notas.crear', 'notas.editar', 'notas.anular',
            'pagos.ver',
            'empleados.ver',
            'inventario.ver',
            'clientes.ver', 'clientes.crear', 'clientes.editar',
            'vehiculos.ver', 'vehiculos.crear', 'vehiculos.editar',
        ]);

        $this->crear('vendedor', 'Vendedor', [
            'notas.ver', 'notas.crear',
            'pagos.registrar',
            'clientes.ver', 'clientes.crear',
            'repuestos.ver',
        ]);

        $this->crear('marketplace-user', 'Usuario Marketplace', [
            'marketplace.resenar', 'marketplace.favoritos',
        ]);
    }

    private function crear(string $slug, string $nombre, array $permisoSlugs): void
    {
        $rol = Rol::firstOrCreate(
            ['slug' => $slug, 'taller_id' => null],
            ['nombre' => $nombre, 'es_sistema' => true, 'activo' => true],
        );

        $permisoIds = Permiso::whereIn('slug', $permisoSlugs)->pluck('id');

        $rol->permisos()->sync($permisoIds);
    }
}
