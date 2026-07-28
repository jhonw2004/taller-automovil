# Tasks — ERP: Nota de Venta o Servicio

- [x] Migraciones `notas_venta`, `notas_venta_lineas` con CHECKs de mutua exclusión servicio/repuesto.
- [x] Modelo `NotaVenta` (`BelongsToTaller`) + `NotaVentaLinea`.
- [x] Action `GenerarCodigoNota` (secuencia por taller, `NV-YYYY-###`).
- [x] Regla definida: múltiples notas activas por orden (permisiva). `CrearNotaVentaDesdeOrden` no bloquea por nota activa existente.
- [x] Action `CrearNotaVentaDesdeOrden` (copia líneas, calcula totales).
- [x] Action `CrearNotaVentaDirecta` (con generación opcional de salida de inventario, evitando doble descuento).
- [x] Método `recalcularTotales()`.
- [x] Action `AnularNotaVenta` (transacción única: bloquea si hay pagos, repone stock si es venta directa, no toca stock si viene de orden, motivo obligatorio, audita).
- [x] Filament: listado con filtros, detalle con líneas/historial de pagos/botones de acción.
- [x] Tests Pest: mutua exclusión servicio/repuesto en línea, totales consistentes, nota anulada no recibe pagos, doble descuento de inventario evitado en venta directa vs. orden.
