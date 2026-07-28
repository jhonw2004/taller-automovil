---
id: 015-auditoria
status: implemented
depends_on: [001-identidad-autenticacion, 003-gestion-talleres]
resumen: "Auditoría append-only de accesos, eventos de negocio y cambios geográficos/de datos de talleres."
---

# Auditoría

## Propósito

Dejar trazabilidad inmutable de accesos, eventos críticos del negocio y cambios en datos sensibles de talleres (especialmente geográficos), para soporte y cumplimiento interno.

## Actores

- Super admin (consulta auditoría global).
- Owner / admin de taller (consulta auditoría de su propio taller, si tiene permiso).

## Criterios de aceptación

### Auditoría de accesos

- Registra login exitoso, login fallido, logout, cambio de contraseña y acceso denegado.
- Nunca guarda la contraseña; puede guardar username/email intentado, IP y user agent si están disponibles.
- Es append-only: no se edita ni se borra ningún registro.

### Auditoría de eventos

- Registra eventos de negocio: creación de usuario, asignación de rol, creación de taller, aprobación/rechazo de solicitud, anulación de orden/nota/pago, ajuste de inventario, cambio de propietario, cambio de visibilidad.
- Puede guardar datos antes/después en JSON y referenciar tipo e ID de la entidad afectada. Append-only.

### Auditoría de talleres

- Registra cambios en campos sensibles del taller (nombre, teléfono, email, dirección, estado, visible_en_mapa, lat, lon, geom, osm_id), incluyendo valores antiguo y nuevo (`geom_old`/`geom_new` incluidos).
- Append-only; no se edita ni se borra.

### Acceso de Super Admin a otros talleres

- Cuando el super admin accede a datos de un taller fuera de su propia gestión (desactivando el scope de aislamiento), esa acción queda registrada en auditoría de eventos (regla transversal ya declarada en `memory/constitution.md`, aplicada aquí a nivel de implementación).

## Fuera de alcance (MVP)

- Exportación/reportes avanzados de auditoría (más allá de exportación simple, ver `Baterias.md` original — `pxlrbt/filament-excel`).
- Retención/purga automática de auditorías antiguas.
