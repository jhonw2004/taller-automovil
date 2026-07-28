---
id: 013-pagos
status: implemented
depends_on: [012-notas-venta]
resumen: "Registro de pagos (parciales o totales) contra una nota de venta, con recálculo transaccional de saldo y estado."
---

# ERP: Pagos

## Propósito

Registrar cobros asociados a una nota de venta y mantener su saldo y estado siempre consistentes con los pagos confirmados.

## Actores

- Usuario sistema con permiso `pagos.registrar` / `pagos.anular`.

## Criterios de aceptación

### Registro de pago

- Requiere permiso `pagos.registrar`, nota de venta existente y no anulada, método de pago activo, monto mayor a cero.
- Se permiten varios pagos por nota (parciales). La suma de pagos confirmados nunca puede superar el `total` de la nota — un intento de exceder el saldo se rechaza.
- El pago guarda referencia opcional y el usuario sistema que lo registró.

### Recálculo de nota

- Tras crear, actualizar o anular un pago: `monto_pagado` = suma de pagos `CONFIRMADO`; `saldo = total - monto_pagado`.
- Si `saldo = 0` y `total > 0` → estado `PAGADA`. Si `saldo > 0` y `monto_pagado > 0` → estado `PENDIENTE`. Si `monto_pagado = 0` → estado `EMITIDA`.
- Una nota `ANULADA` no se recalcula ante nuevos pagos (los pagos nuevos sobre una nota anulada se rechazan, ver `012-notas-venta`).

### Anulación de pago

- Solo usuarios con permiso pueden anular un pago; el pago pasa a `ANULADO` y se excluye de `monto_pagado`.
- Dado que una nota estaba `PAGADA` y se anula uno de sus pagos, entonces la nota vuelve a `PENDIENTE` (o `EMITIDA` si `monto_pagado` queda en 0).
- La anulación de pago queda auditada.

### Métodos de pago

- Los métodos de pago son globales (no por taller): ejemplos efectivo, tarjeta, QR, transferencia. Solo métodos activos pueden usarse en pagos nuevos; desactivar un método no afecta pagos históricos.

## Fuera de alcance (MVP)

- Pagos en línea / pasarelas de pago.
- Anticipos que superen el total de la nota.
