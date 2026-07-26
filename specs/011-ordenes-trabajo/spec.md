---
id: 011-ordenes-trabajo
status: draft
depends_on: [007-clientes-vehiculos, 008-empleados-usuarios-erp, 009-catalogo-servicios, 010-inventario-repuestos]
resumen: "Documento operativo central del taller: cliente+vehículo+líneas de servicio/repuesto con máquina de estados y totales transaccionales."
---

# ERP: Órdenes de Trabajo

## Propósito

Registrar y seguir el trabajo realizado sobre un vehículo: diagnóstico, servicios, repuestos, empleado asignado y estado, con totales siempre consistentes con sus líneas.

## Actores

- Usuario sistema con permiso `ordenes.*`.
- Empleado asignado (mecánico).

## Criterios de aceptación

### Creación

- Requiere permiso `ordenes.crear`, taller activo, cliente y vehículo existentes del mismo taller, y el vehículo debe pertenecer al cliente indicado.
- El código de orden es único por taller (formato recomendado `OT-YYYY-###`), secuencia por taller, nunca reutilizable.
- Valores iniciales: `estado = PENDIENTE`, `prioridad = MEDIA`, `fecha_recepcion = NOW()`, subtotales y total en 0.

### Máquina de estados

| Estado actual | Transiciones permitidas |
|---|---|
| PENDIENTE | EN_DIAGNOSTICO, ESPERANDO_APROBACION, EN_PROGRESO, ANULADA |
| EN_DIAGNOSTICO | ESPERANDO_APROBACION, EN_PROGRESO, PAUSADA, ANULADA |
| ESPERANDO_APROBACION | EN_PROGRESO, PAUSADA, ANULADA |
| EN_PROGRESO | PAUSADA, COMPLETADA, ANULADA |
| PAUSADA | EN_PROGRESO, ANULADA |
| COMPLETADA | ENTREGADA, ANULADA |
| ENTREGADA | estado final (no se recomienda anular salvo error) |
| ANULADA | estado final |

- Cualquier transición no listada se rechaza. Cada cambio de estado genera historial con estado anterior/nuevo, usuario y observación. Anular exige permiso especial y **motivo obligatorio**.

### Líneas de servicio y repuesto

- Cantidad > 0 en ambos tipos de línea; `precio_unitario` es snapshot al momento de agregar la línea (no cambia si el catálogo cambia después).
- Descuento de línea no puede superar el bruto de esa línea; `subtotal = cantidad * precio_unitario - descuento`.
- Línea de servicio: estados `PENDIENTE`, `REALIZADA`, `ANULADA`. Línea de repuesto: estados `PENDIENTE`, `ENTREGADO`, `ANULADA`. Líneas anuladas no suman al total.
- El paso de una línea de repuesto a `ENTREGADO` descuenta stock (ver `010-inventario-repuestos`); si no hay stock suficiente, la operación se rechaza y la línea no cambia de estado.

### Totales

- `subtotal_servicios` = suma de subtotales de líneas de servicio no anuladas. `subtotal_repuestos` = suma de subtotales de líneas de repuesto no anuladas.
- `total = subtotal_servicios + subtotal_repuestos - descuento`. `descuento` no puede ser negativo ni superar la suma de subtotales. `total` no puede ser negativo.
- Los totales se recalculan en la misma transacción que cualquier cambio de líneas.

### Empleado asignado

- Debe pertenecer al mismo taller que la orden; puede no tener usuario sistema (empleado sin acceso).

### Notas de orden

- Pueden ser `INTERNA` (solo visibles en ERP) o `PUBLICA` (reservadas para un futuro portal de cliente, sin efecto visible en el MVP).

### Anulación

- Una orden `ANULADA` no puede editarse ni generar líneas o notas de venta nuevas.
- El motivo de anulación es **obligatorio**.
- **Sin nota de venta asociada**: anulación permitida. Se repone inventario automáticamente por cada línea de repuesto en estado `ENTREGADO`.
- **Con nota de venta EMITIDA (sin pagos)**: se permite anular. El sistema **auto-anula la nota de venta** y luego anula la orden, todo en la misma transacción. Se repone inventario si aplica.
- **Con nota de venta PENDIENTE o PAGADA**: se **BLOQUEA** la anulación ("Resuelva la nota NV-XXX primero — tiene pagos registrados").
- La reposición de inventario genera movimientos automáticos de ajuste en la misma transacción.
- La anulación queda auditada.

## Fuera de alcance (MVP)

- Múltiples empleados asignados a una misma orden.
- Adjuntos/fotos en la orden.
