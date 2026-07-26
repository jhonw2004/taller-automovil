# Tasks — Marketplace: Búsqueda y Perfil Público

## Modelo y scopes
- [ ] Scope `Taller::scopeActivosVisibles()` (estado ACTIVO + visible_en_mapa TRUE + geom no nulo + `deleted_at IS NULL`).
- [ ] Método `Taller::estaAbiertoAhora()` con zona horaria `America/La_Paz`.
- [ ] Método `Taller::toSearchJsonResponse()` que serializa el formato JSON de búsqueda (con categorías, abierto_ahora, distancia).

## API JSON
- [ ] Controlador `TallerBusquedaApiController@search` con filtros: `lat`, `lon`, `radio`, `categoria`, `q`, `min_calificacion`, `open_now`, `sort`.
- [ ] Ruta `GET /api/talleres/search` con `throttle:30,1`.
- [ ] Query PostGIS: `ST_DistanceSphere` + `ST_MakePoint` para filtro por radio.
- [ ] Cache opcional para queries repetidas.

## Controladores Blade
- [ ] `TallerBusquedaController@index` — renderiza vista de búsqueda.
- [ ] `TallerPerfilController@show` — perfil público por slug, 404 si inactivo/no visible.

## Frontend (Alpine + Leaflet)
- [ ] `Alpine.store('search')` en `resources/js/alpine/store.js` — estado de filtros, fetch a API, actualiza resultados.
- [ ] Componente Alpine `map.js` — inicializa Leaflet, dibuja marcadores desde resultados.
- [ ] Vista `marketplace/home.blade.php` — hero + banda categorías + split mapa+lista.
- [ ] Vista `marketplace/workshops/show.blade.php` — perfil público con reseñas.

## Tests Pest
- [ ] Búsqueda nunca devuelve talleres inactivos/no visibles/borrados.
- [ ] `estaAbiertoAhora` correcto en bordes de horario.
- [ ] Perfil de taller inactivo devuelve 404.
- [ ] API JSON incluye todos los campos requeridos.
- [ ] Radio filter con `ST_DistanceSphere` excluye talleres fuera del radio.
- [ ] Rate limiting: más de 30 requests por minuto es rechazado.
