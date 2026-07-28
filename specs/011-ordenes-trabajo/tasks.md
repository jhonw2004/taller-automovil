# Tasks — ERP: Órdenes de Trabajo

- [x] Migraciones: `ordenes_trabajo`, `ordenes_trabajo_servicios`, `ordenes_trabajo_repuestos`, `ordenes_trabajo_estados_historial`, `ordenes_trabajo_notas`.
- [x] Modelo `OrdenTrabajo` (`BelongsToTaller`) + modelos de líneas/historial/notas.
- [x] Action `GenerarCodigoOrden` (secuencia por taller, formato `OT-YYYY-###`, sin colisión bajo concurrencia).
- [x] Action `CrearOrdenTrabajo` (transacción, valida vehículo-cliente-taller).
- [x] Action `CambiarEstadoOrden` con validación estricta de la tabla de transiciones.
- [x] Método `recalcularTotales()` invocado en la misma transacción que altas/bajas de líneas.
- [x] Action `MarcarLineaRepuestoEntregada` integrada con `010-inventario-repuestos` (rollback de línea si stock insuficiente).
- [x] Action `AnularOrdenTrabajo` (transacción única: bloquea si nota PENDIENTE/PAGADA, auto-anula nota EMITIDA, repone stock de líneas ENTREGADO, motivo obligatorio, audita).
- [x] Filament: vista Kanban/lista, detalle con pestañas, dropdown de estado filtrado por transiciones válidas.
- [x] Tests Pest: transición inválida rechazada, totales consistentes tras alta/baja de línea, línea de repuesto sin stock no cambia a ENTREGADO, anulación bloqueada si hay nota pagada, código de orden único por taller bajo concurrencia.
