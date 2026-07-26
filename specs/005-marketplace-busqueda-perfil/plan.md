# Plan — Marketplace: Búsqueda y Perfil Público

## Sin tablas propias

Consulta sobre `talleres`, `categorias`, `talleres_categorias`, `talleres_horarios` (definidas en `003-gestion-talleres`) y `resenas` (definidas en `006-resenas-favoritos`).

## Implementación

### Endpoints

| Método | Ruta | Controlador | Propósito |
|---|---|---|---|
| GET | `/talleres/buscar` (Blade) | `TallerBusquedaController@index` | Renderiza la vista de búsqueda con Alpine |
| GET | `/api/talleres/search` (JSON) | `TallerBusquedaApiController@search` | Devuelve JSON para Alpine.fetch() |
| GET | `/talleres/{slug}` (Blade) | `TallerPerfilController@show` | Perfil público del taller |

### API JSON (`GET /api/talleres/search`)

- Parámetros query: `lat` (opcional), `lon` (opcional), `radio` (km, default 10), `categoria` (slug, opcional), `q` (búsqueda por nombre, LIKE), `min_calificacion` (1-5, opcional), `open_now` (bool, opcional), `sort` (`cercania`|`calificacion`|`resenas`, default `cercania`).
- Query base: `Taller::activosVisibles()` que aplica `estado = ACTIVO AND visible_en_mapa = TRUE AND geom IS NOT NULL AND deleted_at IS NULL`.
- Si se envían `lat`/`lon`: agrega `->selectRaw("ST_DistanceSphere(geom, ST_MakePoint(?, ?)) as distancia", [$lon, $lat])->having('distancia', '<', $radio * 1000)`.
- Si se envía `categoria`: join con `talleres_categorias` donde `categoria_id` = slug resuelto.
- Si se envía `q`: `WHERE nombre ILIKE ?`.
- Si se envía `min_calificacion`: `WHERE calificacion_promedio >= ?`.
- Si se envía `open_now`: agrega un LEFT JOIN a `talleres_horarios` para el día de hoy y filtra donde la hora actual está entre `hora_apertura` y `hora_cierre` y `cerrado = FALSE`. Se resuelve en SQL, no post-query, para que la paginación sea correcta:

  ```php
  if ($request->boolean('open_now')) {
      $diaHoy = now('America/La_Paz')->isoWeekday();
      $horaActual = now('America/La_Paz')->format('H:i:s');

      $query->join('talleres_horarios as th_open', function ($join) use ($diaHoy) {
          $join->on('talleres.id', '=', 'th_open.taller_id')
               ->where('th_open.dia_semana', $diaHoy)
               ->where('th_open.cerrado', false);
      })->whereTime('th_open.hora_apertura', '<=', $horaActual)
        ->whereTime('th_open.hora_cierre', '>=', $horaActual);
  }
  ```

  Esto garantiza que `paginate()` cuente correctamente porque el filtro está en la query SQL, no en colecciones post-procesadas.
- Respuesta JSON: `{ data: [{ id, nombre, slug, descripcion, lat, lon, calificacion_promedio, cantidad_resenas, categorias: [], abierto_ahora: bool, distancia_km: float|null }], meta: { total } }`.
- Rate limiting: `throttle:30,1` (30 requests por minuto por IP).
- Cache opcional: `Cache::remember('search_'.md5(http_build_query($params)), 60, fn() => ...)` para queries repetidas.

### Controladores

- `TallerBusquedaController@index`: renderiza vista Blade con Alpine. No ejecuta la query — Alpine la hace desde el cliente al endpoint JSON.
- `TallerBusquedaApiController@search`: construye query con los filtros descritos, ejecuta, devuelve JSON.
- `TallerPerfilController@show`: busca por `slug`, valida que esté activo y visible, si no → 404. Renderiza perfil.

### Frontend (Blade + Alpine)

- Layout: panel izquierdo 35% (filtros + lista de tarjetas), panel derecho 65% (mapa interactivo); en móvil, lista por defecto con botón flotante "Ver Mapa" y bottom-sheet.
- `Alpine.store('search')` en `resources/js/alpine/store.js` maneja estado de filtros, ejecuta fetch a `/api/talleres/search` y actualiza resultados + marcadores del mapa.
- Mapa con Leaflet directo (npm package `leaflet`), marcadores sincronizados con resultados.
- Empty state de búsqueda: "No encontramos talleres con esos filtros. Amplía el radio de búsqueda." (componente `<x-empty-state>` de `016-ui-design-system`).
- Perfil público: sección de reseñas reutiliza `<x-review-card>` de `016-ui-design-system`.
