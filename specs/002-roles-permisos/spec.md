---
id: 002-roles-permisos
status: draft
depends_on: [001-identidad-autenticacion]
resumen: "Catálogo global de permisos y roles (globales o por taller) asignables a usuarios sistema, con reglas estrictas de ámbito."
---

# Roles y Permisos

## Propósito

Controlar qué puede hacer cada usuario sistema, globalmente o dentro de un taller específico, vía un catálogo de permisos global y roles que agrupan permisos.

## Actores

- Super admin (administra catálogo de permisos y roles globales).
- Owner / Shop admin (crea y asigna roles personalizados dentro de su taller).
- Usuario sistema (recibe asignaciones de rol).

## Criterios de aceptación

### Permisos

- Los permisos siguen el formato `modulo.accion` (p. ej. `ordenes.crear`) y son globales: no existen permisos propios de un taller.
- Solo el super admin crea o edita el catálogo de permisos; un owner/admin de taller solo puede asignar permisos ya existentes a roles personalizados de su taller, nunca crear permisos nuevos.
- El catálogo completo de permisos está definido en `plan.md` (aprobado).

### Roles

- Un rol tiene `taller_id = NULL` (rol global, administrado por super admin) o `taller_id = <id>` (rol personalizado, administrado por el dueño/admin de ese taller).
- Dado un rol con `taller_id` no nulo, cuando se le intenta asignar un permiso, entonces solo se aceptan permisos del catálogo global (no se pueden inventar).
- Un rol personalizado de un taller no puede editarse ni asignarse desde otro taller.
- Roles del sistema recomendados: `SUPER_ADMIN` (global), `OWNER` (por taller), `SHOP_ADMIN` (por taller), `MARKETPLACE_USER` (global). Roles operativos (`MECANICO`, `CAJERO`, `RECEPCIONISTA`, `SUPERVISOR`, `VENDEDOR`) son roles de sistema fijos, seedeados con `es_sistema = true` y `taller_id = NULL`.
- Un admin de taller puede crear roles personalizados adicionales (con `taller_id = <id>` y `es_sistema = false`) y asignarles cualquier permiso del catálogo global.
- Un admin de taller NO puede eliminar ni modificar roles con `es_sistema = true`; solo puede asignarlos o desasignarlos a usuarios de su taller.

### Asignación de roles

- Dado un rol con `taller_id` no nulo, cuando se crea una `asignacion_rol`, entonces `asignacion.taller_id` debe ser igual a `rol.taller_id`; si no coincide, la asignación se rechaza.
- Dado un usuario sistema inactivo o un rol inactivo, cuando se intenta asignar, entonces la asignación se rechaza.
- Solo un super admin puede asignar el rol `SUPER_ADMIN`.
- Un owner o admin de taller no puede asignar roles fuera de su propio taller.
- Dado un usuario con varias asignaciones de rol en distintos talleres, cuando selecciona un taller activo en el ERP, entonces solo puede operar sobre ese taller si tiene al menos una asignación activa para él.
- Dado un usuario que pierde su única asignación activa a un taller, cuando intenta operar sobre ese taller, entonces el acceso se deniega.

## Fuera de alcance (MVP)

- Permisos con expiración automática distinta de `vigente_desde`/`vigente_hasta` ya soportados por la asignación.
- Jerarquía de roles (herencia de permisos entre roles).
