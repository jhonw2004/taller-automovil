# Plan — Gestión de Talleres

## Tablas

### `talleres`
- `propietario_usuario_sistema_id` FK NULL -> `usuarios_sistema.id`.
- `nombre` VARCHAR(255) NOT NULL, `slug` VARCHAR(255) UNIQUE NOT NULL, `descripcion` TEXT NULL.
- `telefono`, `email`, `nit`, `direccion` (VARCHAR). `lat`/`lon` DOUBLE PRECISION NOT NULL. `geom` GEOMETRY(Point,4326) NOT NULL. `osm_id` VARCHAR(255) NULL UNIQUE.
- `logo_url` TEXT NULL. `estado` VARCHAR(20) DEFAULT 'ACTIVO', `CHECK (estado IN ('ACTIVO','INACTIVO','SUSPENDIDO'))`.
- `visible_en_mapa` BOOLEAN DEFAULT FALSE. `calificacion_promedio` NUMERIC(3,2) DEFAULT 0. `cantidad_resenas` INTEGER DEFAULT 0.
- `CHECK (lat BETWEEN -90 AND 90)`, `CHECK (lon BETWEEN -180 AND 180)`. Soft delete.
- Índices: `GIST (geom)`, B-tree `estado`, B-tree `visible_en_mapa`.

### `categorias`
- `nombre` VARCHAR(150), `slug` VARCHAR(170) UNIQUE, `descripcion` TEXT NULL, `activo` BOOLEAN DEFAULT TRUE.

### `talleres_categorias`
- PK compuesta (`taller_id`, `categoria_id`). `orden` SMALLINT DEFAULT 0.

### `talleres_horarios`
- `taller_id` FK NOT NULL. `dia_semana` SMALLINT, `CHECK (dia_semana BETWEEN 1 AND 7)`.
- `hora_apertura`/`hora_cierre` TIME NULL. `cerrado` BOOLEAN DEFAULT FALSE.
- `UNIQUE (taller_id, dia_semana)`. `CHECK (cerrado = TRUE OR (hora_apertura IS NOT NULL AND hora_cierre IS NOT NULL))`. `CHECK (hora_cierre > hora_apertura)`.

> Nota: la migración actual del prototipo (`create_talleres_table`) no coincide con este esquema (falta `slug`, `estado`, `visible_en_mapa`, `calificacion_promedio`, `cantidad_resenas`; usa columna `horario` string en vez de tabla `talleres_horarios`). Requiere migración de reemplazo, no incremental.

## Implementación

- Modelo `Taller` usa trait `App\Traits\HasGeolocation` (cast `App\Casts\GeometryCast`, hook `saving` sincroniza `geom` desde `lat`/`lon`), scopes `scopeCercanoA($lat,$lon,$radioMetros)` y `scopeEnBoundingBox(...)` (ver `005-marketplace-busqueda-perfil` para su uso).
- `spatie/laravel-sluggable` para `slug` a partir de `nombre` (colisión → sufijo numérico automático).
- Recalculo de `calificacion_promedio`/`cantidad_resenas`: listener sobre eventos de `Resena` (creado/actualizado/eliminado) definido en `006-resenas-favoritos`, ejecuta `UPDATE` agregando solo `estado = 'PUBLICADA'`.
- Cambio de `visible_en_mapa` y de propietario pasan por Action dedicada que registra evento en `auditoria_eventos` (ver `015-auditoria`).
- Filament: Resource `TallerResource` (panel `/erp`, campos según permiso `taller.configurar`) y Resource global en `/admin` (ver `UI_Spec` original §6.3 — tabla global talleres, detalle con auditoría y estadísticas).
