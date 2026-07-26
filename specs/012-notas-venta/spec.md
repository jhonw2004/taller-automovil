---
id: 012-notas-venta
status: draft
depends_on: [011-ordenes-trabajo]
resumen: "Documento interno no fiscal de cobro (por servicios/repuestos/mixto), con o sin orden de origen, y saldo derivado de pagos."
---

# ERP: Nota de Venta o Servicio

## Propósito

Registrar el cobro por servicios y/o repuestos, opcionalmente originado desde una orden de trabajo, sin función de facturación fiscal.

## Actores

- Usuario sistema con permiso `notas.*`.

## Criterios de aceptación

### Creación

- Requiere permiso `notas.crear`, taller activo, al menos una línea. `cliente_id` es opcional (venta directa); `orden_trabajo_id` es opcional.
- El código es único por taller (formato recomendado `NV-YYYY-###`), secuencia por taller, nunca reutilizable.
- Dado que la nota se origina desde una orden, se copian sus líneas. Se permiten **múltiples notas activas (no anuladas) por orden**.

### Líneas de nota

- Una línea referencia un servicio, o un repuesto, o ninguno (línea personalizada con descripción libre) — nunca ambos a la vez.
- `descripcion` es obligatoria en toda línea. `cantidad > 0`, `precio_unitario >= 0`, descuento no supera el bruto de la línea, `subtotal = cantidad * precio_unitario - descuento`.

### Estados

| Estado actual | Transiciones permitidas |
|---|---|
| EMITIDA | PENDIENTE, PAGADA, ANULADA |
| PENDIENTE | PAGADA, ANULADA |
| PAGADA | estado final (no se recomienda anular salvo error) |
| ANULADA | estado final |

- `EMITIDA`: sin pagos confirmados. `PENDIENTE`: con pagos parciales. `PAGADA`: saldo = 0. `ANULADA`: cancelada.

### Totales

- `subtotal` = suma de subtotales de líneas no anuladas. `total = subtotal - descuento`. `monto_pagado` = suma de pagos confirmados no anulados. `saldo = total - monto_pagado`.
- `descuento` no negativo ni mayor al subtotal; `total` no negativo; `monto_pagado` no negativo y no supera `total`; `saldo` no negativo.

### Relación con orden

- La nota guarda referencia a la orden de origen si existe, pero no modifica directamente los totales de la orden.

### Relación con inventario

- Si la nota proviene de una orden, el inventario ya fue afectado por las líneas de repuesto entregadas de esa orden (`010-inventario-repuestos`); no debe generarse una segunda salida.
- Si la nota es venta directa de un repuesto (sin orden), puede generar salida de inventario propia — solo si esa línea no está ya vinculada a una orden que descontó stock (evitar doble contabilización).

### Anulación

- Una nota `ANULADA` no puede recibir pagos ni contar para reportes de ingresos.
- Si la nota tiene pagos confirmados, la anulación se **BLOQUEA** ("Debe reversar los pagos primero").
- Si la nota es venta directa (sin orden de origen) y descontó stock de repuestos, la anulación **repone el inventario automáticamente** en la misma transacción.
- Si la nota proviene de una orden (`orden_trabajo_id` no nulo), **no se repone inventario** — el descuento de stock ocurrió al marcar los repuestos como `ENTREGADO` en la orden, no en la nota.
- El motivo de anulación es **obligatorio**.
- La anulación queda auditada.

## Fuera de alcance (MVP)

- Notas de crédito o devoluciones.
- Facturación fiscal o cálculo automático de impuestos (ver `memory/constitution.md`).
