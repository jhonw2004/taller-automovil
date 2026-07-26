# Tasks — ERP: Nota de Venta o Servicio

- [ ] Migraciones `notas_venta`, `notas_venta_lineas` con CHECKs de mutua exclusión servicio/repuesto.
- [ ] Modelo `NotaVenta` (`BelongsToTaller`) + `NotaVentaLinea`.
- [ ] Action `GenerarCodigoNota` (secuencia por taller, `NV-YYYY-###`).
- [ ] Regla definida: múltiples notas activas por orden (permisiva). `CrearNotaVentaDesdeOrden` no bloquea por nota activa existente.
- [ ] Action `CrearNotaVentaDesdeOrden` (copia líneas, calcula totales).
- [ ] Action `CrearNotaVentaDirecta` (con generación opcional de salida de inventario, evitando doble descuento).
- [ ] Método `recalcularTotales()`.
- [ ] Action `AnularNotaVenta` (transacción única: bloquea si hay pagos, repone stock si es venta directa, no toca stock si viene de orden, motivo obligatorio, audita).
- [ ] Filament: listado con filtros, detalle con líneas/historial de pagos/botones de acción.
- [ ] Tests Pest: mutua exclusión servicio/repuesto en línea, totales consistentes, nota anulada no recibe pagos, doble descuento de inventario evitado en venta directa vs. orden.
