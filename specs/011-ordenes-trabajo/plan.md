# Plan — ERP: Órdenes de Trabajo

## Tablas

### `ordenes_trabajo`
- `taller_id` FK NOT NULL. `codigo` UNIQUE por taller. `cliente_id`, `vehiculo_id` FK NOT NULL. `empleado_asignado_id` FK NULL. `creado_por_usuario_sistema_id` FK NULL.
- `estado` VARCHAR(30) DEFAULT 'PENDIENTE', `CHECK (estado IN ('PENDIENTE','EN_DIAGNOSTICO','ESPERANDO_APROBACION','EN_PROGRESO','PAUSADA','COMPLETADA','ENTREGADA','ANULADA'))`.
- `prioridad` VARCHAR(10) DEFAULT 'MEDIA', `CHECK (prioridad IN ('BAJA','MEDIA','ALTA'))`.
- `fecha_recepcion` DEFAULT NOW(), `fecha_estimada_entrega`, `fecha_entrega_real` NULL. `kilometraje_ingreso` NULL. `sintomas`, `diagnostico`, `observaciones` TEXT NULL.
- `subtotal_servicios`, `subtotal_repuestos`, `descuento`, `total` NUMERIC(12,2) DEFAULT 0, `CHECK (descuento >= 0)`, `CHECK (total = subtotal_servicios + subtotal_repuestos - descuento)`.
- `UNIQUE (taller_id, codigo)`. Soft delete.

### `ordenes_trabajo_servicios`
- `orden_trabajo_id`, `servicio_catalogo_id` FK NOT NULL. `cantidad` SMALLINT DEFAULT 1, `CHECK (cantidad > 0)`.
- `precio_unitario` NUMERIC(12,2) NOT NULL, `CHECK (>= 0)`. `descuento` DEFAULT 0. `subtotal`, `CHECK (subtotal = cantidad*precio_unitario - descuento)`.
- `estado` VARCHAR(20) DEFAULT 'PENDIENTE', `CHECK (estado IN ('PENDIENTE','REALIZADO','ANULADO'))`.

### `ordenes_trabajo_repuestos`
- Igual estructura que servicios pero `repuesto_id` FK, `cantidad` NUMERIC(12,3), estados `PENDIENTE|ENTREGADO|ANULADO`.

### `ordenes_trabajo_estados_historial`
- `orden_trabajo_id` FK NOT NULL. `estado_anterior` NULL, `estado_nuevo` NOT NULL. `usuario_sistema_id` FK NULL. `observacion` NULL. Append-only.

### `ordenes_trabajo_notas`
- `orden_trabajo_id` FK NOT NULL. `usuario_sistema_id` FK NULL. `tipo` VARCHAR(20) DEFAULT 'INTERNA', `CHECK (tipo IN ('INTERNA','PUBLICA'))`. `nota` TEXT NOT NULL.

## Implementación

- Modelo `OrdenTrabajo` con `BelongsToTaller`; código generado por Action `GenerarCodigoOrden` (secuencia por taller, formato `OT-YYYY-###`, dentro de la misma transacción de creación para evitar colisión).
- Action `CrearOrdenTrabajo` (transacción): valida vehículo pertenece a cliente y ambos al taller activo, crea orden con valores iniciales, inserta historial inicial.
- Action `CambiarEstadoOrden`: valida transición contra la tabla del spec, actualiza orden, inserta historial, dispara efectos colaterales (p. ej. al entrar a `EN_PROGRESO` no hay efecto de stock; el descuento de stock ocurre a nivel de línea de repuesto, no de orden).
- Action `MarcarLineaRepuestoEntregada`: valida stock vía `010-inventario-repuestos` (`RegistrarMovimientoInventario`), si falla no cambia el estado de la línea y propaga el error de negocio "stock insuficiente" a la UI (toast).
- Recalculo de totales: método `OrdenTrabajo::recalcularTotales()` invocado dentro de la misma transacción tras cualquier alta/baja/edición de línea.
- Anulación: Action `AnularOrdenTrabajo` ejecuta en una **sola transacción DB**:
  1. Valida estado actual de la orden (debe permitir transición a `ANULADA`).
  2. Si tiene nota de venta `PENDIENTE` o `PAGADA` → lanza excepción, rollback.
  3. Si tiene nota de venta `EMITIDA` → invoca `AnularNotaVenta` internamente (misma transacción).
  4. Por cada línea de repuesto en estado `ENTREGADO`, genera movimiento de reposición de stock vía `010-inventario-repuestos` (`RegistrarMovimientoInventario` con tipo `AJUSTE`).
  5. Marca líneas de servicio y repuesto como `ANULADAS`.
  6. Cambia estado de la orden a `ANULADA`.
  7. Inserta historial con motivo obligatorio.
  8. Si algo falla en cualquier paso → rollback completo.

## UI (Filament, panel `/erp`)

- Vista Kanban/lista por estado; tarjeta con código, cliente, vehículo (placa), empleado, total, prioridad.
- Detalle: pestañas Servicios/Repuestos/Notas/Historial; columna lateral con datos de cliente/vehículo, empleado (select), síntomas/diagnóstico, resumen de totales.
- Dropdown de cambio de estado solo muestra transiciones permitidas desde el estado actual (regla de UI ligada 1:1 a la tabla de máquina de estados de este spec).
- Toast rojo "Stock insuficiente para [Repuesto]. Disponible: X" cuando falla el descuento de stock; la línea permanece en su estado previo.
