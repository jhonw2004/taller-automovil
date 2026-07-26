# Tasks — Auditoría

- [ ] Migraciones `auditoria_eventos`, `auditoria_accesos`, `auditoria_talleres` (con índices GIST).
- [ ] Instalar `spatie/laravel-activitylog`, configurar `LogsActivity` en modelos operativos clave (definir `$logAttributes`/`$logOnlyDirty` por modelo).
- [ ] Observer `TallerObserver` para `auditoria_talleres` (captura old/new de lat/lon/geom).
- [ ] Listeners de eventos de auth (`Login`, `Logout`, `Failed`, `PasswordReset`) → `auditoria_accesos`.
- [ ] Instalar `filament/spatie-laravel-activitylog-plugin`, Resources de solo lectura en `/admin` y `/erp`.
- [ ] Bloquear a nivel de modelo cualquier `update`/`delete` sobre las tres tablas de auditoría (append-only forzado en código).
- [ ] Tests Pest: password_hash nunca aparece en `auditoria_*`, registros de auditoría no son editables/borrables desde la aplicación, cambio de lat/lon de un taller genera geom_old/geom_new correctos.
