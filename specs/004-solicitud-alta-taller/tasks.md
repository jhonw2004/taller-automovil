# Tasks — Solicitud de Alta de Taller

- [ ] Migraciones `solicitudes_taller` y `solicitudes_taller_historial`.
- [ ] Modelo `SolicitudTaller` con `HasGeolocation` y `SolicitudTallerHistorial`.
- [ ] Form Request público de creación (nombre/email/teléfono solicitante + nombre taller obligatorios).
- [ ] Action `CrearSolicitudTaller` (transacción: insert + token UUID + historial inicial + geom si hay lat/lon).
- [ ] Actions de transición: `IniciarRevisionSolicitud`, `AprobarSinCompletarSolicitud`, `AprobarYCompletarSolicitud` (con `lockForUpdate`), `RechazarSolicitud` (exige motivo), `CancelarSolicitud`. Cada una valida la tabla de transiciones del spec y rechaza transiciones no listadas.
- [ ] Endpoint/página pública de seguimiento por `token_publico` (no expone IDs internos).
- [ ] Formulario público multi-paso (solicitante → taller → mapa → confirmación).
- [ ] Filament: listado + detalle de solicitudes en panel `/admin`, modal de aprobación pre-cargando formulario de `Taller`, modal de rechazo con motivo obligatorio.
- [ ] Integración con auditoría (`015-auditoria`) en aprobación/rechazo.
- [ ] Tests Pest: transiciones válidas/inválidas de la tabla de estados, aprobación crea taller en la misma transacción y hace rollback si falla, doble aprobación concurrente bloqueada por `lockForUpdate`, rechazo exige motivo, CHECK completada-implica-taller y rechazada-implica-sin-taller.
