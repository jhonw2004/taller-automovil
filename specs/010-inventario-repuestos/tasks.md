# Tasks — ERP: Inventario

- [x] Migraciones `repuestos`, `unidades_medida`, `proveedores`, `repuestos_proveedores`, `inventario_movimientos`.
- [x] Modelos `Repuesto`, `Proveedor` (`BelongsToTaller`), `UnidadMedida` (global), `InventarioMovimiento` (append-only, sin `$timestamps` de updated_at).
- [x] Action `RegistrarMovimientoInventario` con `lockForUpdate`, cálculo de efecto por tipo, rechazo si stock quedaría negativo.
- [x] Hook de consumo automático: línea de repuesto de orden pasa a `ENTREGADO` → `SALIDA` (una sola vez por línea, guardar bandera o verificar movimiento existente por `orden_trabajo_repuesto_id`).
- [x] Hook de reposición: anulación de línea `ENTREGADO` → `AJUSTE_POSITIVO` opcional con motivo obligatorio si no hay reposición.
- [x] Evento `StockBajoDetectado` tras cada movimiento (consumido por `014-notificaciones`).
- [x] Filament `RepuestoResource` (columna stock en rojo si <= mínimo, modal "Ajustar Stock"), `ProveedorResource`, `MovimientoInventarioResource` (solo lectura).
- [x] Tests Pest: movimiento que dejaría stock negativo se rechaza, stock_resultante = stock_anterior ± cantidad según tipo, doble salida para la misma línea de repuesto es imposible, movimientos son append-only (no editable ni borrable), aislamiento por taller.
