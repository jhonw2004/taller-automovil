# Plan — ERP: Nota de Venta o Servicio

## Tablas

### `notas_venta`
- `taller_id` FK NOT NULL. `codigo` UNIQUE por taller. `cliente_id` FK NULL. `orden_trabajo_id` FK NULL. `usuario_sistema_id` FK NULL.
- `fecha_emision` DATE DEFAULT CURRENT_DATE. `estado` VARCHAR(20) DEFAULT 'EMITIDA', `CHECK (estado IN ('EMITIDA','PENDIENTE','PAGADA','ANULADA'))`.
- `subtotal`, `descuento`, `total`, `monto_pagado`, `saldo` NUMERIC(12,2) DEFAULT 0.
- `CHECK (descuento >= 0)`, `CHECK (total = subtotal - descuento)`, `CHECK (saldo = total - monto_pagado)`, `CHECK (monto_pagado >= 0)`.
- `UNIQUE (taller_id, codigo)`. Soft delete.

### `notas_venta_lineas`
- `nota_venta_id` FK NOT NULL. `servicio_catalogo_id` FK NULL, `repuesto_id` FK NULL (mutuamente excluyentes, `CHECK`).
- `descripcion` VARCHAR(255) NOT NULL. `cantidad` NUMERIC(12,3) DEFAULT 1, `CHECK (cantidad > 0)`. `precio_unitario` `CHECK (>= 0)`. `descuento` DEFAULT 0. `subtotal`, `CHECK (subtotal = cantidad*precio_unitario - descuento)`.

## Implementación

- Modelo `NotaVenta` (`BelongsToTaller`), código vía `GenerarCodigoNota` (mismo patrón que `GenerarCodigoOrden` de `011-ordenes-trabajo`, formato `NV-YYYY-###`).
- Action `CrearNotaVentaDesdeOrden` (transacción): copia líneas de la orden completada, calcula totales. **Decisión ya resuelta** (2026-07-25, ver `resume.md`): múltiples notas activas por orden están permitidas (regla permisiva, sin bloqueo de unicidad) — no hay restricción de "una nota activa por orden" que parametrizar.
- Action `CrearNotaVentaDirecta` (venta sin orden): líneas pueden generar salida de inventario propia vía `010-inventario-repuestos`, validando que la línea no provenga ya de una orden que descontó stock.
- Método `NotaVenta::recalcularTotales()` invocado en la misma transacción que cualquier alta/baja/edición de línea o de pago (el recálculo por pagos vive en `013-pagos`).
- Action `AnularNotaVenta` ejecuta en una **sola transacción DB**:
  1. Valida que no tenga pagos confirmados (si los tiene → excepción, rollback).
  2. Si es venta directa (sin `orden_trabajo_id`) y tiene líneas de repuesto: genera movimiento de reposición de stock vía `010-inventario-repuestos`.
  3. Si proviene de una orden (`orden_trabajo_id` no nulo): no toca inventario.
  4. Cambia estado de la nota a `ANULADA`.
  5. Inserta historial con motivo obligatorio.
  6. Si algo falla en cualquier paso → rollback completo.

## UI (Filament, panel `/erp` — módulo Caja)

- Tabla: código, cliente, orden (enlace), fecha, total, pagado, saldo, estado; filtros por estado y rango de fechas.
- Detalle: líneas, totales, historial de pagos, botones "Registrar Pago" (ver `013-pagos`) y "Anular Nota".
