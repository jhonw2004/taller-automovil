# Plan — ERP: Pagos

## Tablas

### `pagos`
- `nota_venta_id`, `metodo_pago_id` FK NOT NULL. `usuario_sistema_id` FK NULL. `fecha_pago` DEFAULT NOW().
- `monto` NUMERIC(12,2), `CHECK (monto > 0)`. `referencia`, `observacion` NULL.
- `estado` VARCHAR(20) DEFAULT 'CONFIRMADO', `CHECK (estado IN ('CONFIRMADO','ANULADO'))`.

### `metodos_pago`
- `nombre` UNIQUE, `slug` UNIQUE, `descripcion`, `activo` DEFAULT TRUE. Global.

## Implementación

- Action `RegistrarPago` (transacción): `NotaVenta::whereKey($id)->lockForUpdate()`, valida nota no anulada y método activo, inserta pago, invoca `NotaVenta::recalcularTotales()` (definido en `012-notas-venta`, extendido aquí para incluir suma de pagos), actualiza estado de nota, audita.
- Action `AnularPago`: bloquea pago y nota, cambia pago a `ANULADO`, recalcula nota, audita.
- Ambas Actions viven junto al modelo `Pago`, no en el controller.
- Filament: modal "Registrar Pago" con input `max` = saldo pendiente (validación en cliente + servidor), select de método de pago activo, referencia opcional; tabla de historial de pagos en el detalle de la nota.
