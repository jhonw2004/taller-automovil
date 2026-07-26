# Plan — ERP: Empleados y Usuarios

## Tabla

### `empleados`
- `taller_id` FK NOT NULL. `usuario_sistema_id` FK NULL -> `usuarios_sistema.id`.
- `codigo` VARCHAR(50), `nombre` NOT NULL, `apellido`, `cargo`, `telefono`, `email`, `fecha_ingreso` DATE NULL.
- `activo` BOOLEAN DEFAULT TRUE. Soft delete.
- `UNIQUE (taller_id, codigo)`, `UNIQUE (taller_id, usuario_sistema_id)`.

## Implementación

- Modelo `Empleado` con `BelongsToTaller` (`007-clientes-vehiculos`), relación `belongsTo(UsuarioSistema::class)` opcional.
- Action `CrearEmpleadoConAcceso` (transacción): reutiliza el flujo de alta de usuario sistema de `001-identidad-autenticacion` (identidad + usuario + credencial con password temporal) + `AsignarRolAction` de `002-roles-permisos` + vincula `empleado.usuario_sistema_id` + evento de auditoría.
- Action `CrearEmpleadoSinAcceso`: solo inserta `empleados`, sin identidad ni credencial.
- Toggle "Tiene acceso al sistema" en el formulario de empleado (Filament) dispara la Action correspondiente; muestra el password temporal generado una sola vez.
- Restablecer contraseña / activar-desactivar usuario: Actions dedicadas con verificación de permiso `usuarios.gestionar` y de que el usuario pertenece al taller activo.

## UI (Filament, panel `/erp`)

- Vista Empleados: tabla (código, nombre, cargo, usuario vinculado, estado); formulario con datos personales + toggle de acceso.
- Vista Usuarios y Roles del taller: tabla de usuarios (username, nombre, roles, último acceso, activo) y tabla de roles con permisos agrupados por módulo (checkboxes).
