# Plan — ERP: Inventario

## Tablas

### `repuestos`
- `taller_id` FK NOT NULL. `codigo`, `nombre` NOT NULL, `descripcion`, `codigo_barras`, `unidad_medida_id` FK NULL.
- `stock_actual` NUMERIC(12,3) DEFAULT 0, `CHECK (stock_actual >= 0)`. `stock_minimo` NUMERIC(12,3) DEFAULT 0.
- `precio_costo`/`precio_venta` NUMERIC(12,2) DEFAULT 0, `CHECK (>= 0)`. `activo` BOOLEAN DEFAULT TRUE. Soft delete.
- `UNIQUE (taller_id, codigo)`; índice único parcial `UNIQUE (taller_id, codigo_barras) WHERE codigo_barras IS NOT NULL`.

### `unidades_medida`
- `nombre` UNIQUE, `simbolo` UNIQUE, `descripcion`, `activo` DEFAULT TRUE. Global, no por taller.

### `proveedores`
- `taller_id` FK NOT NULL. `nombre` NOT NULL, `contacto`, `telefono`, `email`, `direccion`, `nit`, `observaciones`, `activo` DEFAULT TRUE. Soft delete.
- Índice único parcial `UNIQUE (taller_id, nit) WHERE nit IS NOT NULL`.

### `repuestos_proveedores`
- PK compuesta (`repuesto_id`, `proveedor_id`). `codigo_proveedor`, `precio_referencia` `CHECK (>= 0)`, `tiempo_entrega_dias`, `es_principal` BOOLEAN DEFAULT FALSE.

### `inventario_movimientos`
- `taller_id`, `repuesto_id` FK NOT NULL. `tipo_movimiento` VARCHAR(20), `CHECK (tipo_movimiento IN ('ENTRADA','SALIDA','AJUSTE_POSITIVO','AJUSTE_NEGATIVO'))`.
- `cantidad` NUMERIC(12,3), `CHECK (cantidad > 0)`. `stock_anterior`/`stock_resultante` NUMERIC(12,3), `CHECK (stock_resultante >= 0)`.
- `costo_unitario` NULL, `proveedor_id` FK NULL, `orden_trabajo_repuesto_id` FK NULL -> `ordenes_trabajo_repuestos.id` (ver `011-ordenes-trabajo`), `usuario_sistema_id` FK NULL, `motivo`, `referencia`. Append-only (sin updated_at/deleted_at).

## Implementación

- Modelos `Repuesto`, `Proveedor` con `BelongsToTaller`; `UnidadMedida` sin tenancy (global).
- Action `RegistrarMovimientoInventario`: `DB::transaction()` con `Repuesto::whereKey($id)->lockForUpdate()`, calcula `stock_resultante` según tipo, rechaza si quedaría negativo, inserta movimiento, actualiza `repuestos.stock_actual` — todo en la misma transacción.
- Listener/Action de consumo por orden: al marcar línea de repuesto `ENTREGADO`, dispara `RegistrarMovimientoInventario(tipo: SALIDA, orden_trabajo_repuesto_id: ...)`; guarda flag para evitar doble salida sobre la misma línea (ver `011-ordenes-trabajo`).
- Notificación de stock bajo: evento `StockBajoDetectado` tras cada movimiento si `stock_actual <= stock_minimo`, consumido por `014-notificaciones`.
- Filament: `RepuestoResource` (stock en rojo si <= mínimo, botón "Ajustar Stock" con modal tipo/cantidad/motivo), `ProveedorResource` (CRUD simple), vista de solo lectura `MovimientoInventarioResource` (auditoría).
