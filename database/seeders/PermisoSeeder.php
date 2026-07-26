<?php

namespace Database\Seeders;

use App\Models\Permiso;
use Illuminate\Database\Seeder;

/**
 * Catálogo global de permisos (aprobado, specs/002-roles-permisos/plan.md). Los permisos
 * nunca se crean por taller: son fijos y solo el super admin los administra.
 */
class PermisoSeeder extends Seeder
{
    protected array $permisos = [
        // ERP — ámbito taller
        ['modulo' => 'taller', 'nombre' => 'Ver taller', 'slug' => 'taller.ver'],
        ['modulo' => 'taller', 'nombre' => 'Editar taller', 'slug' => 'taller.editar'],
        ['modulo' => 'taller', 'nombre' => 'Configurar taller', 'slug' => 'taller.configurar'],
        ['modulo' => 'taller', 'nombre' => 'Cambiar propietario', 'slug' => 'taller.cambiar_propietario'],

        ['modulo' => 'clientes', 'nombre' => 'Ver clientes', 'slug' => 'clientes.ver'],
        ['modulo' => 'clientes', 'nombre' => 'Crear clientes', 'slug' => 'clientes.crear'],
        ['modulo' => 'clientes', 'nombre' => 'Editar clientes', 'slug' => 'clientes.editar'],
        ['modulo' => 'clientes', 'nombre' => 'Eliminar clientes', 'slug' => 'clientes.eliminar'],

        ['modulo' => 'vehiculos', 'nombre' => 'Ver vehículos', 'slug' => 'vehiculos.ver'],
        ['modulo' => 'vehiculos', 'nombre' => 'Crear vehículos', 'slug' => 'vehiculos.crear'],
        ['modulo' => 'vehiculos', 'nombre' => 'Editar vehículos', 'slug' => 'vehiculos.editar'],
        ['modulo' => 'vehiculos', 'nombre' => 'Eliminar vehículos', 'slug' => 'vehiculos.eliminar'],

        ['modulo' => 'empleados', 'nombre' => 'Ver empleados', 'slug' => 'empleados.ver'],
        ['modulo' => 'empleados', 'nombre' => 'Crear empleados', 'slug' => 'empleados.crear'],
        ['modulo' => 'empleados', 'nombre' => 'Editar empleados', 'slug' => 'empleados.editar'],
        ['modulo' => 'empleados', 'nombre' => 'Eliminar empleados', 'slug' => 'empleados.eliminar'],

        ['modulo' => 'usuarios', 'nombre' => 'Ver usuarios', 'slug' => 'usuarios.ver'],
        ['modulo' => 'usuarios', 'nombre' => 'Gestionar usuarios', 'slug' => 'usuarios.gestionar'],

        ['modulo' => 'servicios', 'nombre' => 'Ver servicios', 'slug' => 'servicios.ver'],
        ['modulo' => 'servicios', 'nombre' => 'Crear servicios', 'slug' => 'servicios.crear'],
        ['modulo' => 'servicios', 'nombre' => 'Editar servicios', 'slug' => 'servicios.editar'],
        ['modulo' => 'servicios', 'nombre' => 'Eliminar servicios', 'slug' => 'servicios.eliminar'],

        ['modulo' => 'repuestos', 'nombre' => 'Ver repuestos', 'slug' => 'repuestos.ver'],
        ['modulo' => 'repuestos', 'nombre' => 'Crear repuestos', 'slug' => 'repuestos.crear'],
        ['modulo' => 'repuestos', 'nombre' => 'Editar repuestos', 'slug' => 'repuestos.editar'],
        ['modulo' => 'repuestos', 'nombre' => 'Eliminar repuestos', 'slug' => 'repuestos.eliminar'],

        ['modulo' => 'inventario', 'nombre' => 'Ver inventario', 'slug' => 'inventario.ver'],
        ['modulo' => 'inventario', 'nombre' => 'Ajustar inventario', 'slug' => 'inventario.ajustar'],

        ['modulo' => 'proveedores', 'nombre' => 'Ver proveedores', 'slug' => 'proveedores.ver'],
        ['modulo' => 'proveedores', 'nombre' => 'Crear proveedores', 'slug' => 'proveedores.crear'],
        ['modulo' => 'proveedores', 'nombre' => 'Editar proveedores', 'slug' => 'proveedores.editar'],
        ['modulo' => 'proveedores', 'nombre' => 'Eliminar proveedores', 'slug' => 'proveedores.eliminar'],

        ['modulo' => 'ordenes', 'nombre' => 'Ver órdenes', 'slug' => 'ordenes.ver'],
        ['modulo' => 'ordenes', 'nombre' => 'Crear órdenes', 'slug' => 'ordenes.crear'],
        ['modulo' => 'ordenes', 'nombre' => 'Editar órdenes', 'slug' => 'ordenes.editar'],
        ['modulo' => 'ordenes', 'nombre' => 'Anular órdenes', 'slug' => 'ordenes.anular'],
        ['modulo' => 'ordenes', 'nombre' => 'Eliminar órdenes', 'slug' => 'ordenes.eliminar'],

        ['modulo' => 'notas', 'nombre' => 'Ver notas de venta', 'slug' => 'notas.ver'],
        ['modulo' => 'notas', 'nombre' => 'Crear notas de venta', 'slug' => 'notas.crear'],
        ['modulo' => 'notas', 'nombre' => 'Editar notas de venta', 'slug' => 'notas.editar'],
        ['modulo' => 'notas', 'nombre' => 'Anular notas de venta', 'slug' => 'notas.anular'],
        ['modulo' => 'notas', 'nombre' => 'Eliminar notas de venta', 'slug' => 'notas.eliminar'],

        ['modulo' => 'pagos', 'nombre' => 'Ver pagos', 'slug' => 'pagos.ver'],
        ['modulo' => 'pagos', 'nombre' => 'Registrar pagos', 'slug' => 'pagos.registrar'],
        ['modulo' => 'pagos', 'nombre' => 'Anular pagos', 'slug' => 'pagos.anular'],

        ['modulo' => 'roles', 'nombre' => 'Ver roles', 'slug' => 'roles.ver'],
        ['modulo' => 'roles', 'nombre' => 'Crear roles', 'slug' => 'roles.crear'],
        ['modulo' => 'roles', 'nombre' => 'Editar roles', 'slug' => 'roles.editar'],
        ['modulo' => 'roles', 'nombre' => 'Eliminar roles', 'slug' => 'roles.eliminar'],

        ['modulo' => 'auditoria', 'nombre' => 'Ver auditoría', 'slug' => 'auditoria.ver'],

        ['modulo' => 'notificaciones', 'nombre' => 'Ver notificaciones', 'slug' => 'notificaciones.ver'],

        // Super Admin — ámbito global
        ['modulo' => 'solicitudes', 'nombre' => 'Ver solicitudes de alta', 'slug' => 'solicitudes.ver'],
        ['modulo' => 'solicitudes', 'nombre' => 'Revisar solicitudes de alta', 'slug' => 'solicitudes.revisar'],
        ['modulo' => 'solicitudes', 'nombre' => 'Aprobar solicitudes de alta', 'slug' => 'solicitudes.aprobar'],
        ['modulo' => 'solicitudes', 'nombre' => 'Rechazar solicitudes de alta', 'slug' => 'solicitudes.rechazar'],
        ['modulo' => 'solicitudes', 'nombre' => 'Crear taller desde solicitud', 'slug' => 'solicitudes.crear_taller'],

        ['modulo' => 'moderacion', 'nombre' => 'Moderar reseñas', 'slug' => 'moderacion.resenas'],

        ['modulo' => 'admin', 'nombre' => 'Ver talleres (admin)', 'slug' => 'admin.talleres.ver'],
        ['modulo' => 'admin', 'nombre' => 'Suspender talleres', 'slug' => 'admin.talleres.suspender'],
        ['modulo' => 'admin', 'nombre' => 'Cambiar propietario de taller', 'slug' => 'admin.talleres.cambiar_propietario'],
        ['modulo' => 'admin', 'nombre' => 'Ver auditoría global', 'slug' => 'admin.auditoria.ver'],
        ['modulo' => 'admin', 'nombre' => 'Gestionar catálogo de roles/permisos', 'slug' => 'admin.roles.gestionar'],

        // Marketplace — ámbito web (guard distinto)
        ['modulo' => 'marketplace', 'nombre' => 'Dejar reseña', 'slug' => 'marketplace.resenar'],
        ['modulo' => 'marketplace', 'nombre' => 'Gestionar favoritos', 'slug' => 'marketplace.favoritos'],
    ];

    public function run(): void
    {
        foreach ($this->permisos as $permiso) {
            Permiso::firstOrCreate(['slug' => $permiso['slug']], $permiso);
        }
    }
}
