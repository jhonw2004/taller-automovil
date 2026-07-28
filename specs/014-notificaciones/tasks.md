# Tasks — Notificaciones

- [x] Migración `notificaciones` con CHECK de destinatario único.
- [x] Modelo `Notificacion` + relación polimórfica opcional a taller/orden.
- [x] Clase `SolicitudAprobadaNotification` (trigger: 004).
- [x] Clase `SolicitudRechazadaNotification` (trigger: 004).
- [x] Clase `OrdenAsignadaNotification` (trigger: 011).
- [x] Clase `OrdenCambioEstadoNotification` (trigger: 011).
- [x] Clase `NotaEmitidaNotification` (trigger: 012).
- [x] Clase `PagoRegistradoNotification` (trigger: 013).
- [x] Clase `StockBajoNotification` (trigger: 010).
- [x] Clase `ResenaNuevaNotification` (trigger: 006).
- [x] Clase `UsuarioCreadoNotification` (trigger: 008).
- [x] Clase `PasswordExpiradaNotification` (trigger: 001).
- [x] Listeners conectando eventos de otras features (lista completa en plan.md).
- [x] Endpoint marcar-como-leída con verificación de propiedad del destinatario.
- [x] Campana de notificaciones en topbar ERP + sección en dashboard marketplace.
- [x] Tests Pest: notificación siempre tiene exactamente un destinatario, usuario no puede marcar como leída una notificación ajena.
