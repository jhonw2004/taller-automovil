# Tasks — Gestión de Talleres

- [ ] Nueva migración de reemplazo para `talleres` (el prototipo actual no coincide con el esquema del spec: falta slug/estado/visible_en_mapa/calificacion/cantidad_resenas, sobra columna `horario` string).
- [ ] Migraciones `categorias`, `talleres_categorias`, `talleres_horarios`.
- [ ] Cast `App\Casts\GeometryCast` (WKB/WKT <-> `['lat'=>..,'lon'=>..]` vía `ST_GeomFromText`/`ST_AsText`).
- [ ] Trait `App\Traits\HasGeolocation` (hook `saving` sincroniza `geom`; scopes `scopeCercanoA`, `scopeEnBoundingBox`).
- [ ] Modelo `Taller` (usa `HasGeolocation`, `HasSlug` de `spatie/laravel-sluggable`, soft deletes).
- [ ] Modelos `Categoria`, `TallerHorario` + relaciones many-to-many taller-categoría con `orden`.
- [ ] Validación de horario en Form Request: `cerrado=false` exige apertura/cierre, `cierre > apertura`, único por `(taller_id, dia_semana)`.
- [ ] Action `CambiarVisibilidadTaller` (valida `lat/lon` y `geom` no nulos) + registro en auditoría.
- [ ] Action `CambiarPropietarioTaller` (valida asignación de rol OWNER) + registro en auditoría.
- [ ] Listener de recálculo de `calificacion_promedio`/`cantidad_resenas` sobre eventos de `Resena`.
- [ ] Filament Resource `TallerResource` en panel `/erp` (permiso `taller.configurar`) y vista global en `/admin`.
- [ ] Global scope `BelongsToTaller` excluye talleres `deleted` de queries ERP (implementado en 017).
- [ ] Advertencia al Super Admin antes de soft-deletear taller con entidades hijas activas.
- [ ] Tests Pest: CHECK lat/lon, unicidad de slug con colisión, visibilidad requiere estado+visible+geom, horario cierre>apertura, recálculo de calificación excluye reseñas no publicadas, taller soft-deleteado no aparece en marketplace, restauración de taller reactiva acceso a hijas.
