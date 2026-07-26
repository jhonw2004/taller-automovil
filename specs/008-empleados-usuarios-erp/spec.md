---
id: 008-empleados-usuarios-erp
status: draft
depends_on: [001-identidad-autenticacion, 002-roles-permisos, 003-gestion-talleres]
resumen: "Empleados de taller con o sin acceso al ERP; creación de usuario sistema vinculado y gestión de cuenta dentro del taller."
---

# ERP: Empleados y Usuarios

## Propósito

Registrar el personal operativo de un taller (con o sin acceso al sistema) y, para quienes sí necesitan acceso, crear su usuario sistema vinculado con rol asignado. Este spec aplica las reglas de `001-identidad-autenticacion` y `002-roles-permisos` al caso concreto de alta de personal desde el ERP.

## Actores

- Owner / Shop admin con permiso `empleados.*` / `usuarios.gestionar`.
- Empleado con acceso (usuario sistema vinculado).

## Criterios de aceptación

### Empleado

- Un empleado pertenece a exactamente un taller. `codigo` de empleado es único por taller.
- Un empleado puede existir sin usuario sistema ("empleado sin acceso"): solo registro operativo, asignable a órdenes de trabajo, no inicia sesión.
- Un empleado puede tener usuario sistema vinculado ("empleado con acceso"): existe `usuarios_sistema` relacionado, con roles asignados; su acceso depende de esos roles y permisos.
- Un usuario sistema solo puede ser empleado una vez por taller (`UNIQUE (taller_id, usuario_sistema_id)`).
- Un empleado puede estar activo o inactivo; `cargo` es informativo, sin efecto en permisos.

### Creación de usuario sistema desde el ERP

- Dado un owner/admin con permiso, cuando crea un usuario sistema para un empleado, entonces en una sola transacción: se crea la identidad sistema, el usuario sistema, la credencial (con `debe_cambiar_password = true` recomendado), se asigna al menos un rol correspondiente al taller, se vincula el empleado, y se registra auditoría (ver `001-identidad-autenticacion`, `002-roles-permisos`, `015-auditoria`).
- El usuario debe tener al menos un rol activo en el taller para poder acceder a él.
- El rol asignado en esta creación debe corresponder al mismo taller del empleado.

### Edición de cuenta

- El propio usuario puede cambiar su contraseña.
- El admin puede restablecer la contraseña de un usuario de su taller.
- El admin puede activar o desactivar un usuario; desactivar no borra su histórico y el usuario desactivado no puede iniciar sesión.

## Fuera de alcance (MVP)

- Autoservicio de alta de empleado por el propio empleado.
- Roles personalizados por taller: la creación del catálogo de roles vive en `002-roles-permisos`; aquí solo se referencia su asignación.
