---
id: 010-inventario-repuestos
status: draft
depends_on: [003-gestion-talleres]
resumen: "Repuestos, proveedores y movimientos de inventario append-only con stock nunca negativo, actualizado transaccionalmente."
---

# ERP: Inventario

## Propósito

Gestionar el stock de repuestos por taller mediante movimientos append-only, garantizando que el stock nunca sea negativo y que cada cambio quede trazado.

## Actores

- Usuario sistema con permiso `inventario.*` / `repuestos.*`.

## Criterios de aceptación

### Repuestos

- Un repuesto pertenece a exactamente un taller. `codigo` es único por taller; si se registra `codigo_barras`, también es único por taller.
- `stock_actual` se deriva de los movimientos de inventario, nunca se edita a mano; no puede quedar negativo. `precio_costo` y `precio_venta` no pueden ser negativos.
- Un repuesto puede estar activo o inactivo; solo repuestos activos pueden usarse en líneas nuevas de orden o nota.

### Unidades de medida

- Las unidades son globales (no por taller); ejemplos: unidad, litro, metro, kilo. El símbolo es único. Un repuesto puede tener una unidad; las cantidades pueden ser decimales.

### Proveedores

- Un proveedor pertenece a exactamente un taller; si registra NIT, es único por taller. Un proveedor puede suministrar varios repuestos y viceversa (relación N:M con precio de referencia por proveedor, código propio, y marca de proveedor principal).

### Movimientos de inventario

- Tipos: `ENTRADA`, `SALIDA`, `AJUSTE_POSITIVO`, `AJUSTE_NEGATIVO`. Todo movimiento es append-only: nunca se edita ni se borra.
- Cada movimiento guarda `stock_anterior` y `stock_resultante`; la cantidad debe ser positiva; el efecto depende del tipo (`ENTRADA`/`AJUSTE_POSITIVO` suman, `SALIDA`/`AJUSTE_NEGATIVO` restan).
- Dado un movimiento cuyo resultado dejaría `stock_resultante < 0`, cuando se intenta registrar, entonces la operación se rechaza como error de negocio ("stock insuficiente").
- El movimiento y la actualización de `repuestos.stock_actual` ocurren en la misma transacción, con bloqueo de fila (`FOR UPDATE`) sobre el repuesto.

### Consumo por orden de trabajo

- Una línea de repuesto de una orden en estado `PENDIENTE` no descuenta stock.
- Dado que una línea de repuesto pasa a `ENTREGADO`, entonces se genera exactamente una `SALIDA` de inventario por esa línea (nunca doble salida para la misma línea).
- Una línea `ANULADA` antes de `ENTREGADO` no genera movimiento.
- Una línea `ANULADA` después de `ENTREGADO` puede generar `AJUSTE_POSITIVO` si hay reposición física; si no hay reposición, la anulación debe registrar un motivo.

### Stock mínimo

- `stock_minimo` es referencial; cuando `stock_actual <= stock_minimo`, el sistema puede notificar a usuarios con permiso de inventario del taller (ver `014-notificaciones`). No bloquea operaciones en el MVP.

## Fuera de alcance (MVP)

- Órdenes de compra a proveedor.
- Múltiples almacenes/ubicaciones físicas por taller.
