# Tasks — ERP: Empleados y Usuarios

- [x] Migración `empleados` (UNIQUE taller+codigo, UNIQUE taller+usuario_sistema_id).
- [x] Modelo `Empleado` con `BelongsToTaller` y relación opcional a `UsuarioSistema`.
- [x] Action `CrearEmpleadoConAcceso` (transacción: identidad + usuario sistema + credencial + rol + vínculo + auditoría).
- [x] Action `CrearEmpleadoSinAcceso`.
- [x] Actions `RestablecerPasswordUsuario`, `ActivarDesactivarUsuarioSistema` (con verificación de pertenencia al taller activo).
- [x] Filament: formulario Empleado con toggle "Tiene acceso al sistema" y despliegue del password temporal generado.
- [x] Filament: tabla Usuarios del taller y tabla Roles con checkboxes de permisos agrupados por módulo.
- [x] Tests Pest: un usuario sistema no puede ser empleado dos veces en el mismo taller, alta con acceso es transaccional (falla intermedia no deja usuario huérfano), usuario desactivado no puede iniciar sesión, rol asignado debe corresponder al taller del empleado.
