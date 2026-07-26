# Tasks — Notificaciones

- [ ] Migración `notificaciones` con CHECK de destinatario único.
- [ ] Modelo `Notificacion` + relación polimórfica opcional a taller/orden.
- [ ] Clase `SolicitudAprobadaNotification` (trigger: 004).
- [ ] Clase `SolicitudRechazadaNotification` (trigger: 004).
- [ ] Clase `OrdenAsignadaNotification` (trigger: 011).
- [ ] Clase `OrdenCambioEstadoNotification` (trigger: 011).
- [ ] Clase `NotaEmitidaNotification` (trigger: 012).
- [ ] Clase `PagoRegistradoNotification` (trigger: 013).
- [ ] Clase `StockBajoNotification` (trigger: 010).
- [ ] Clase `ResenaNuevaNotification` (trigger: 006).
- [ ] Clase `UsuarioCreadoNotification` (trigger: 008).
- [ ] Clase `PasswordExpiradaNotification` (trigger: 001).
- [ ] Listeners conectando eventos de otras features (lista completa en plan.md).
- [ ] Endpoint marcar-como-leída con verificación de propiedad del destinatario.
- [ ] Campana de notificaciones en topbar ERP + sección en dashboard marketplace.
- [ ] Tests Pest: notificación siempre tiene exactamente un destinatario, usuario no puede marcar como leída una notificación ajena.
