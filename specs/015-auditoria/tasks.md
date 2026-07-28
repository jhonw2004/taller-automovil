# Tasks — Auditoría

- [x] Migraciones `auditoria_eventos`, `auditoria_accesos`, `auditoria_talleres` (con índices GIST) + migración de drop de `activity_log`.
- [x] ~~Instalar `spatie/laravel-activitylog`, configurar `LogsActivity` en modelos operativos clave~~ **Descartado en la implementación** (ver nota en `plan.md`): se construyeron `RegistrarEventoAuditoriaAction`/`RegistrarAccesoAuditoriaAction` como único punto de escritura, sin depender de ningún paquete externo. El paquete fue removido por completo (`composer remove`, tabla `activity_log` dropeada).
- [x] Observer `TallerObserver` para `auditoria_talleres` (captura old/new de lat/lon/geom).
- [x] Listeners de eventos de auth (`Login`, `Logout`, `Failed`) + `CambiarPasswordAction` directo → `auditoria_accesos` (`PasswordReset` no aplica: no existe flujo de recuperación por email en este proyecto, ver `001-identidad-autenticacion`).
- [x] ~~Instalar `filament/spatie-laravel-activitylog-plugin`~~ **Descartado**, junto con el punto anterior: 6 Resources Filament propios de solo lectura (3 tablas × `/admin`+`/erp`), sin ningún plugin de Spatie.
- [x] Bloquear a nivel de modelo cualquier `update`/`delete` sobre las tres tablas de auditoría (append-only forzado en código, override de `save()`/`delete()` lanzando `BusinessException`).
- [x] Tests Pest: password_hash nunca aparece en `auditoria_*`, registros de auditoría no son editables/borrables desde la aplicación, cambio de lat/lon de un taller genera geom_old/geom_new correctos. 31 tests en `tests/Feature/Auditoria/`.
