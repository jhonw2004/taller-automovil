# Plan — Roles y Permisos

## Tablas

### `roles`
- `taller_id` FK NULL -> `talleres.id`. `nombre` VARCHAR(100), `slug` VARCHAR(120), `descripcion` TEXT NULL.
- `es_sistema` BOOLEAN DEFAULT FALSE. `activo` BOOLEAN DEFAULT TRUE.
- `UNIQUE (taller_id, slug)`; índice único parcial `UNIQUE (slug) WHERE taller_id IS NULL`.

### `permisos`
- `modulo` VARCHAR(100), `nombre` VARCHAR(150), `slug` VARCHAR(150) UNIQUE, `descripcion` TEXT NULL, `activo` BOOLEAN DEFAULT TRUE.

### `roles_permisos`
- PK compuesta (`rol_id`, `permiso_id`), ambas FK.

### `asignaciones_rol`
- `usuario_sistema_id`, `rol_id` FK NOT NULL. `taller_id` FK NULL.
- `activo` BOOLEAN DEFAULT TRUE. `asignado_por_usuario_sistema_id` FK NULL -> `usuarios_sistema.id`.
- `vigente_desde` DATE DEFAULT CURRENT_DATE, `vigente_hasta` DATE NULL, `CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde)`.
- `UNIQUE (usuario_sistema_id, rol_id, taller_id)`; índice único parcial `UNIQUE (usuario_sistema_id, rol_id) WHERE taller_id IS NULL`.

## Catálogo final de permisos (aprobado)

### ERP —ámbito taller (evaluados con `taller_id` activo)

| Módulo | Permiso |
|---|---|
| `taller` | `taller.ver`, `taller.editar`, `taller.configurar`, `taller.cambiar_propietario` |
| `clientes` | `clientes.ver`, `clientes.crear`, `clientes.editar`, `clientes.eliminar` |
| `vehiculos` | `vehiculos.ver`, `vehiculos.crear`, `vehiculos.editar`, `vehiculos.eliminar` |
| `empleados` | `empleados.ver`, `empleados.crear`, `empleados.editar`, `empleados.eliminar` |
| `usuarios` | `usuarios.ver`, `usuarios.gestionar` |
| `servicios` | `servicios.ver`, `servicios.crear`, `servicios.editar`, `servicios.eliminar` |
| `repuestos` | `repuestos.ver`, `repuestos.crear`, `repuestos.editar`, `repuestos.eliminar` |
| `inventario` | `inventario.ver`, `inventario.ajustar` |
| `proveedores` | `proveedores.ver`, `proveedores.crear`, `proveedores.editar`, `proveedores.eliminar` |
| `ordenes` | `ordenes.ver`, `ordenes.crear`, `ordenes.editar`, `ordenes.anular`, `ordenes.eliminar` |
| `notas` | `notas.ver`, `notas.crear`, `notas.editar`, `notas.anular`, `notas.eliminar` |
| `pagos` | `pagos.ver`, `pagos.registrar`, `pagos.anular` |
| `roles` | `roles.ver`, `roles.crear`, `roles.editar`, `roles.eliminar` |
| `auditoria` | `auditoria.ver` |
| `notificaciones` | `notificaciones.ver` |

### Super Admin —ámbito global

| Módulo | Permiso |
|---|---|
| `solicitudes` | `solicitudes.ver`, `solicitudes.revisar`, `solicitudes.aprobar`, `solicitudes.rechazar`, `solicitudes.crear_taller` |
| `moderacion` | `moderacion.resenas` |
| `admin` | `admin.talleres.ver`, `admin.talleres.suspender`, `admin.talleres.cambiar_propietario`, `admin.auditoria.ver`, `admin.roles.gestionar` |

### Marketplace —ámbito web (otro guard)

| Módulo | Permiso |
|---|---|
| `marketplace` | `marketplace.resenar`, `marketplace.favoritos` |

## Implementación

- `spatie/laravel-permission` con `'teams' => true`, `'team_foreign_key' => 'taller_id'` (`config/permission.php`). El modelo `Rol`/`Permiso` propios se mapean sobre las tablas de Spatie o se usan directamente los modelos de Spatie renombrados vía `config/permission.php` (`models.role`, `models.permission`) — decisión de implementación: usar los modelos y tablas nativas de Spatie en vez de duplicar tablas custom si el naming en español es solo cosmético; si se requiere naming exacto en español (`roles`, `permisos`, `roles_permisos`), publicar migración y renombrar columnas manteniendo la lógica de `teams`.
- Middleware `role:` y `permission:` de Spatie en rutas del ERP.
- `filament/spatie-laravel-permission-plugin`: UI de asignación de roles/permisos filtrada por team (taller) activo.
- Resolución de "team" activo: listener que setea `setPermissionsTeamId(session('taller_activo_id'))` en cada request autenticado del guard `sistema`.

## Validación de ámbito (Form Request / Action)

- `AsignarRolAction`: valida `rol.taller_id === asignacion.taller_id` (o ambos NULL), usuario y rol activos, y que solo super admin asigne `SUPER_ADMIN`.
