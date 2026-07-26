# Tasks — Solicitud de Alta de Taller

- [x] Migraciones `solicitudes_taller` y `solicitudes_taller_historial`.
- [x] Modelo `SolicitudTaller` con `HasGeolocation` y `SolicitudTallerHistorial`.
- [x] Form Request público de creación (nombre/email/teléfono solicitante + nombre taller obligatorios).
- [x] Action `CrearSolicitudTaller` (transacción: insert + token UUID + historial inicial + geom si hay lat/lon).
- [x] Actions de transición: `IniciarRevisionSolicitud`, `AprobarSinCompletarSolicitud`, `AprobarYCompletarSolicitud` (con `lockForUpdate`), `RechazarSolicitud` (exige motivo), `CancelarSolicitud`. Cada una valida la tabla de transiciones del spec y rechaza transiciones no listadas.
- [x] Endpoint/página pública de seguimiento por `token_publico` (no expone IDs internos).
- [x] Formulario público multi-paso (solicitante → taller → mapa → confirmación).
- [x] Filament: listado + detalle de solicitudes en panel `/admin`, modal de aprobación pre-cargando formulario de `Taller`, modal de rechazo con motivo obligatorio.
- [x] Integración con auditoría: vía `activity()` (spatie/laravel-activitylog), no `auditoria_eventos` de `015-auditoria` (esa tabla no existe todavía — mismo patrón documentado en `003-gestion-talleres`).
- [x] Tests Pest: transiciones válidas/inválidas de la tabla de estados, aprobación crea taller en la misma transacción y hace rollback si falla, doble aprobación (protección de estado tras `lockForUpdate`), rechazo exige motivo, CHECK completada-implica-taller y rechazada-implica-sin-taller.
- [ ] Detección de duplicados (osm_id/nombre/teléfono cercano): **no implementada a propósito** — el criterio del spec la marca explícitamente como opcional/informativa ("no bloquea... salvo que el negocio lo decida explícitamente"). Ninguna decisión de negocio la activó en esta sesión.
