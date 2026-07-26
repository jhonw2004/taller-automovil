# Tasks — Reseñas y Favoritos

## Base de datos y modelos
- [ ] Migraciones `resenas` (UNIQUE usuario+taller, CHECK calificacion) y `favoritos` (UNIQUE usuario+taller).
- [ ] Modelos `Resena`, `Favorito` + eventos `ResenaGuardada`/`ResenaEliminada`.
- [ ] Listener de recálculo de calificación del taller (depende de `003-gestion-talleres`).

## API endpoints (JSON)
- [ ] Controlador `ResenaApiController@storeOrUpdate` — upsert de reseña, validación 1-5, comentario opcional, guard `web`.
- [ ] Controlador `ResenaApiController@destroy` — eliminación de propia reseña con verificación de pertenencia.
- [ ] Controlador `FavoritoApiController@store` — agregar favorito, idempotente.
- [ ] Controlador `FavoritoApiController@destroy` — quitar favorito, idempotente.
- [ ] Rutas: `POST /api/resenas`, `DELETE /api/resenas/{id}`, `POST /api/favoritos`, `POST /api/favoritos/delete` con middleware `auth:web` + `throttle:10,1`.

## Frontend (Blade + Alpine)
- [ ] Componente Alpine en perfil público para enviar reseña vía fetch.
- [ ] Componente Alpine para toggle de favorito (icono corazón lleno/vacío).
- [ ] Componente Blade "Escribir reseña" / "Mi reseña" en perfil público.
- [ ] Dashboard usuario marketplace: grid "Mis Favoritos", lista "Mis Reseñas" con editar/eliminar.

## Moderación (Super Admin)
- [ ] Filament Resource de moderación en `/admin` (filtros Reportadas/Ocultas, acción ocultar/publicar).

## Tests Pest
- [ ] Unicidad reseña por usuario-taller (upsert no duplica).
- [ ] Unicidad favorito por usuario-taller (store idempotente no duplica).
- [ ] Solo PUBLICADA cuenta en calificación promedio.
- [ ] Visitante sin sesión no puede reseñar/favoritear (401).
- [ ] Taller no puede editar reseñas ajenas.
- [ ] DELETE de reseña ajena es rechazado.
- [ ] DELETE de favorito inexistente responde 200 (idempotente).
