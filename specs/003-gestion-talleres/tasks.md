# Tasks — Gestión de Talleres

- [x] Nueva migración de reemplazo para `talleres` (el prototipo original no coincidía con el esquema del spec: faltaba slug/estado/visible_en_mapa/calificacion/cantidad_resenas, sobraba columna `horario` string). `2026_07_26_060001_replace_talleres_table.php` — también dropea y recrea las FK de `roles.taller_id`/`asignaciones_rol.taller_id` que apuntaban a la tabla vieja.
- [x] Migraciones `categorias`, `talleres_categorias`, `talleres_horarios`.
- [x] Cast `App\Casts\GeometryCast` (WKB/WKT <-> `['lat'=>..,'lon'=>..]` vía `ST_GeomFromText`/`ST_AsText`). Implementado en `017`, verificado contra PostGIS real.
- [x] Trait `App\Traits\HasGeolocation` (hook `saving` sincroniza `geom`; scopes `scopeCercanoA`, `scopeEnBoundingBox`). Implementado en `017`.
- [x] Modelo `Taller` (usa `HasGeolocation`, `HasSlug` de `spatie/laravel-sluggable`, soft deletes).
- [x] Modelos `Categoria`, `TallerHorario` + relaciones many-to-many taller-categoría con `orden`.
- [x] Validación de horario en Form Request: `cerrado=false` exige apertura/cierre, `cierre > apertura`, único por `(taller_id, dia_semana)`. `GuardarHorarioTallerRequest`.
- [x] Action `CambiarVisibilidadTallerAction` (valida `lat/lon` no nulos) + registro en auditoría (`activity()`).
- [x] Action `CambiarPropietarioTallerAction` (valida asignación de rol OWNER) + registro en auditoría.
- [x] Action `CambiarEstadoTallerAction` (nueva, no estaba en el plan original: transición ACTIVO/INACTIVO/SUSPENDIDO, solo Super Admin, permiso `admin.talleres.suspender`) + registro en auditoría. Necesaria para el Resource global de `/admin` (suspender/activar). `app/Actions/Talleres/CambiarEstadoTallerAction.php`.
- [x] Listener de recálculo de `calificacion_promedio`/`cantidad_resenas` sobre eventos de `Resena` — **resuelto en la sesión de `006-resenas-favoritos`**: `RecalcularCalificacionTallerListener` sobre `ResenaGuardada`/`ResenaEliminada`.
- [x] Filament Resource `TallerResource` en panel `/erp` (permiso `taller.ver`/`taller.editar`/`taller.configurar`, de un solo registro: el propio taller activo — `canCreate()=false`, `canDelete()=false`) y `TallerResource` global en `/admin` (permiso `admin.talleres.ver`, con acciones suspender/activar/cambiar propietario, sin edición propia).
- [x] Filament Resource `CategoriaResource` en panel `/admin` (catálogo global, solo Super Admin — no había un Resource especificado explícitamente en ningún documento previo, se construyó siguiendo el patrón de catálogo simple de `PermisoResource`).
- [x] Global scope `BelongsToTaller` excluye talleres `deleted` de queries ERP (implementado en `017`).
- [x] Advertencia al Super Admin antes de soft-deletear taller con entidades hijas activas: **conteo real** vía `Taller::contarEntidadesHijasActivas()` (clientes/vehículos/empleados/repuestos/proveedores/servicios/órdenes activas/notas activas), agregado en la sesión de auditoría/limpieza ahora que los modelos con `BelongsToTaller` de `007+` ya existen. `tests/Feature/Talleres/ContarEntidadesHijasActivasTest.php`.
- [x] Tests Pest: CHECK lat/lon, unicidad de slug con colisión, visibilidad requiere estado+visible+geom, horario cierre>apertura, taller soft-deleteado no aparece en marketplace, restauración de taller reactiva acceso a hijas. 28 tests en `tests/Feature/Talleres/` (backend) + tests de `CambiarEstadoTallerAction` y autorización/render de los 3 Resources nuevos.
- [x] Test de recálculo de calificación excluyendo reseñas no publicadas — **resuelto en la sesión de `006-resenas-favoritos`** (`tests/Feature/Resenas/`, verifica que solo `PUBLICADA` cuenta).
