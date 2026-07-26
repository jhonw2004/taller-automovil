---
id: 003-gestion-talleres
status: implemented
depends_on: [001-identidad-autenticacion, 002-roles-permisos]
resumen: "Taller como tenant aislado: datos, estados, slug, geolocalización, categorías, horarios y calificación agregada."
---

# Gestión de Talleres

## Propósito

El taller es la entidad central del tenant: contiene sus datos públicos/operativos, controla su visibilidad en el marketplace y es la raíz de aislamiento (`taller_id`) para todo el ERP.

## Actores

- Super admin (crea talleres directamente, suspende, cambia propietario).
- Owner / Shop admin (edita datos de su taller si tiene permiso).

## Criterios de aceptación

### Datos y estado

- Un taller tiene `estado` en `ACTIVO | INACTIVO | SUSPENDIDO`.
- Dado un taller `SUSPENDIDO`, cuando se evalúa su visibilidad en marketplace, entonces no aparece, y su acceso ERP puede quedar restringido.
- Dado un taller `INACTIVO`, cuando se evalúa su visibilidad, entonces no aparece en marketplace pero conserva sus datos históricos.

### Slug

- El `slug` es único globalmente y se genera a partir del nombre; si hay colisión, se agrega sufijo numérico automático.

### Visibilidad en marketplace

- Dado un taller, cuando `estado = ACTIVO` AND `visible_en_mapa = TRUE` AND `geom IS NOT NULL` AND no está borrado lógicamente, entonces aparece en el marketplace; si falta cualquiera de esas condiciones, no aparece.
- El cambio de `visible_en_mapa` solo lo hace super admin, owner o admin con permiso `taller.configurar`, y queda auditado (ver `015-auditoria`).
- Un taller sin `lat`/`lon` no puede marcarse visible en mapa.

### Geolocalización

- `lat` y `lon` son la fuente de verdad; `geom` se sincroniza automáticamente en la misma transacción que la escritura de `lat`/`lon` (SRID 4326, tipo Point).
- Dado `lat` fuera de `[-90, 90]` o `lon` fuera de `[-180, 180]`, cuando se intenta guardar, entonces la operación se rechaza como error de negocio.

### Categorías

- Un taller puede tener varias categorías y una categoría puede estar en varios talleres; el `orden` determina cuál se muestra como principal.
- Las categorías inactivas no se muestran en el marketplace aunque estén asignadas al taller.

### Horarios

- Un taller tiene a lo sumo un registro de horario por día de semana (`dia_semana` 1=lunes .. 7=domingo).
- Dado `cerrado = FALSE`, cuando se guarda el horario, entonces `hora_apertura` y `hora_cierre` son obligatorias y `hora_cierre` debe ser posterior a `hora_apertura`.
- Dado `cerrado = TRUE`, las horas no son obligatorias.
- No se soportan horarios partidos (dos franjas el mismo día) en el MVP.

### Calificación agregada

- `calificacion_promedio` y `cantidad_resenas` se calculan solo a partir de reseñas en estado `PUBLICADA` (reseñas ocultas o reportadas no cuentan). Si no hay reseñas publicadas, `calificacion_promedio = 0` y `cantidad_resenas = 0`.
- El recálculo ocurre cuando cambia el estado de una reseña relacionada (ver `006-resenas-favoritos`); puede ser transaccional o asíncrono.

### Propietario

- Un taller puede tener un `propietario_usuario_sistema_id`; ese usuario también debe tener asignación de rol `OWNER` (o equivalente) para ese taller.
- El cambio de propietario lo hace super admin, o el propietario actual si el negocio lo permite, y queda auditado.

### Soft delete y cascada

- Cuando un taller se marca como eliminado (`deleted_at`), **no se bloquea**:
  - Todas las entidades hijas (clientes, vehículos, empleados, usuarios sistema, servicios, repuestos, proveedores, órdenes, notas, pagos, movimientos de inventario, reseñas) **no se eliminan ni modifican** — conservan su `taller_id` histórico.
  - El global scope `BelongsToTaller` excluye automáticamente registros con `taller_id` apuntando a un taller `deleted` de todas las queries del ERP, salvo `withoutGlobalScope` del Super Admin.
  - El marketplace nunca muestra talleres con `deleted_at NOT NULL`, independientemente de `estado` y `visible_en_mapa`.
- No existe `ON DELETE CASCADE` en la BD para `taller_id`. El borrado lógico del taller es un UPDATE de `deleted_at`, no un DELETE físico, por lo que las FK no se violan.
- Dado que un taller soft-deleteado tiene entidades hijas con `taller_id` no nulo, cuando el Super Admin restaura el taller (`deleted_at = NULL`), entonces todas las entidades hijas vuelven a ser accesibles automáticamente, sin necesidad de migración.
- Un taller con entidades hijas activas (órdenes en curso, pagos pendientes) puede soft-deletearse. La operación no se bloquea — pero el sistema muestra una advertencia al Super Admin antes de confirmar.

## Fuera de alcance (MVP)

- Multi-sucursal por taller.
- Horarios partidos / excepciones por fecha (feriados).
