# Tasks — ERP: Pagos

- [x] Migraciones `pagos` y `metodos_pago` (+ seed de métodos base: efectivo, tarjeta, QR, transferencia).
- [x] Modelos `Pago`, `MetodoPago`.
- [x] Action `RegistrarPago` (lockForUpdate sobre nota, valida nota no anulada, método activo, monto no excede saldo).
- [x] Action `AnularPago` (lockForUpdate, recalcula nota).
- [x] Extender `NotaVenta::recalcularTotales()` (de `012-notas-venta`) para incluir suma de pagos confirmados y reglas de estado (PAGADA/PENDIENTE/EMITIDA).
- [x] Filament: modal "Registrar Pago" con `max` = saldo, tabla de historial de pagos.
- [x] Tests Pest: pago no puede exceder saldo, recalculo correcto tras crear/anular pago, nota PAGADA vuelve a PENDIENTE al anular un pago, método de pago inactivo rechazado en pagos nuevos, nota anulada rechaza pagos nuevos.
