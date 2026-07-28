# Plan — Auditoría

## Tablas

### `auditoria_eventos`
- `usuario_marketplace_id`/`usuario_sistema_id` FK NULL. `taller_id` FK NULL. `evento` VARCHAR(100) NOT NULL.
- `entidad_tipo` VARCHAR(100) NULL, `entidad_id` NULL. `datos` JSONB NULL. `ip` INET NULL. `user_agent` TEXT NULL. Append-only.

### `auditoria_accesos`
- `usuario_marketplace_id`/`usuario_sistema_id` FK NULL. `taller_id` FK NULL.
- `tipo_acceso` VARCHAR(30), `CHECK (tipo_acceso IN ('LOGIN','LOGOUT','PASSWORD_CHANGE','FAILED_LOGIN'))`.
- `resultado` VARCHAR(20), `CHECK (resultado IN ('EXITOSO','FALLIDO','DENEGADO'))`. `identificador`, `ip`, `user_agent`. Append-only.

### `auditoria_talleres`
- `taller_id` FK NOT NULL. `usuario_sistema_id` FK NULL. `operacion` VARCHAR(10), `CHECK (operacion IN ('INSERT','UPDATE','DELETE'))`.
- `datos_old`/`datos_new` JSONB NULL. `lat_old`/`lat_new`/`lon_old`/`lon_new` DOUBLE PRECISION NULL. `geom_old`/`geom_new` GEOMETRY(Point,4326) NULL. `ip`, `user_agent`. Append-only.
- Índices `GIST (geom_old)`, `GIST (geom_new)`.

## Implementación

**Nota (implementación real, difiere del plan original de abajo):** este plan originalmente proponía `spatie/laravel-activitylog` como motor genérico de `auditoria_eventos`. Se descartó por completo durante la implementación: el helper `activity()` no puede modelar el CHECK "a lo sumo un actor" (marketplace o sistema, o ninguno) ni el bloqueo de `update()`/`delete()` a nivel de código que exige `tasks.md`. Se implementó en su lugar con **Actions propias** (`RegistrarEventoAuditoriaAction`/`RegistrarAccesoAuditoriaAction`) escribiendo directo a las tablas de abajo, y `spatie/laravel-activitylog` fue removido por completo del proyecto (`composer remove`, tabla `activity_log` dropeada). El texto siguiente queda como referencia histórica del plan original:

- ~~`spatie/laravel-activitylog` para auditoría genérica de modelos (`logsOnly()`/`logOnlyDirty()`) alimentando `auditoria_eventos` — trait `LogsActivity` en `Taller`, `Cliente`, `Vehiculo`, `OrdenTrabajo`, `NotaVenta`, `Pago`, etc., con `$logAttributes` explícitos y `$logOnlyDirty = true`.~~ Reemplazado por `RegistrarEventoAuditoriaAction`, invocada explícitamente desde cada Action/Observer de origen.
- `auditoria_talleres` requiere Observer propio (`TallerObserver`) porque ni Spatie Activitylog ni ningún otro helper genérico maneja PostGIS nativamente (necesita capturar `geom_old`/`geom_new`).
- `auditoria_accesos` alimentada por listeners de eventos nativos de autenticación de Laravel (`Login`, `Logout`, `Failed`) + `CambiarPasswordAction` directo para `PASSWORD_CHANGE`, ver `001-identidad-autenticacion`.
- ~~`filament/spatie-laravel-activitylog-plugin`: Resource de solo lectura...~~ Reemplazado por 6 Resources Filament propios (uno por tabla × panel), sin ningún plugin de Spatie.
- Ningún modelo de auditoría expone métodos `update`/`delete` a nivel de aplicación (forzar append-only también en código, no solo en BD) — implementado vía override de `save()`/`delete()` que lanzan `BusinessException`.
