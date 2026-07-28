# Tasks — Marketplace: Búsqueda y Perfil Público

## Modelo y scopes
- [x] Scope `Taller::scopeActivosVisibles()` (estado ACTIVO + visible_en_mapa TRUE + geom no nulo + `deleted_at IS NULL`).
- [x] Método `Taller::estaAbiertoAhora()` con zona horaria `America/La_Paz`.
- [x] Método `Taller::toSearchJsonResponse()` que serializa el formato JSON de búsqueda (con categorías, abierto_ahora, distancia).

## API JSON
- [x] Controlador `TallerBusquedaApiController@search` con filtros: `lat`, `lon`, `radio`, `categoria`, `q`, `min_calificacion`, `open_now`, `sort`.
- [x] Ruta `GET /api/talleres/search` con `throttle:30,1`.
- [x] Query PostGIS: `ST_DistanceSphere` + `ST_MakePoint` para filtro por radio.
- [x] Cache opcional para queries repetidas.

## Controladores Blade
- [x] `TallerBusquedaController@index` — renderiza vista de búsqueda.
- [x] `TallerPerfilController@show` — perfil público por slug, 404 si inactivo/no visible.

## Frontend (Alpine + Leaflet)
- [x] `Alpine.store('search')` en `resources/js/alpine/store.js` — estado de filtros, fetch a API, actualiza resultados.
- [x] Componente Alpine `map.js` — inicializa Leaflet, dibuja marcadores desde resultados.
- [x] Vista `marketplace/home.blade.php` — hero + banda categorías + split mapa+lista.
- [x] Vista `marketplace/workshops/show.blade.php` — perfil público con reseñas.

## Tests Pest
- [x] Búsqueda nunca devuelve talleres inactivos/no visibles/borrados.
- [x] `estaAbiertoAhora` correcto en bordes de horario.
- [x] Perfil de taller inactivo devuelve 404.
- [x] API JSON incluye todos los campos requeridos.
- [x] Radio filter con `ST_DistanceSphere` excluye talleres fuera del radio.
- [x] Rate limiting: más de 30 requests por minuto es rechazado.
