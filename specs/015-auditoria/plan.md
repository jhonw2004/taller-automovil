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

- `spatie/laravel-activitylog` para auditoría genérica de modelos (`logsOnly()`/`logOnlyDirty()`) alimentando `auditoria_eventos` — trait `LogsActivity` en `Taller`, `Cliente`, `Vehiculo`, `OrdenTrabajo`, `NotaVenta`, `Pago`, etc., con `$logAttributes` explícitos y `$logOnlyDirty = true`.
- `auditoria_talleres` requiere Observer propio (`TallerObserver`) porque Spatie Activitylog no maneja PostGIS nativamente (necesita capturar `geom_old`/`geom_new`).
- `auditoria_accesos` alimentada por listeners de eventos nativos de autenticación de Laravel (`Login`, `Logout`, `Failed`, `PasswordReset`), ver `001-identidad-autenticacion`.
- `filament/spatie-laravel-activitylog-plugin`: Resource de solo lectura en `/admin` (todos los eventos) y en `/erp` (eventos del taller activo, si el usuario tiene permiso).
- Ningún modelo de auditoría expone métodos `update`/`delete` a nivel de aplicación (forzar append-only también en código, no solo en BD).
