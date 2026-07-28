# Tasks — Reseñas y Favoritos

## Base de datos y modelos
- [x] Migraciones `resenas` (UNIQUE usuario+taller, CHECK calificacion) y `favoritos` (UNIQUE usuario+taller).
- [x] Modelos `Resena`, `Favorito` + eventos `ResenaGuardada`/`ResenaEliminada`.
- [x] Listener de recálculo de calificación del taller (depende de `003-gestion-talleres`).

## API endpoints (JSON)
- [x] Controlador `ResenaApiController@storeOrUpdate` — upsert de reseña, validación 1-5, comentario opcional, guard `web`.
- [x] Controlador `ResenaApiController@destroy` — eliminación de propia reseña con verificación de pertenencia.
- [x] Controlador `FavoritoApiController@store` — agregar favorito, idempotente.
- [x] Controlador `FavoritoApiController@destroy` — quitar favorito, idempotente.
- [x] Rutas: `POST /api/resenas`, `DELETE /api/resenas/{id}`, `POST /api/favoritos`, `POST /api/favoritos/delete` con middleware `auth:web` + `throttle:10,1`.

## Frontend (Blade + Alpine)
- [x] Componente Alpine en perfil público para enviar reseña vía fetch.
- [x] Componente Alpine para toggle de favorito (icono corazón lleno/vacío).
- [x] Componente Blade "Escribir reseña" / "Mi reseña" en perfil público.
- [x] Dashboard usuario marketplace: grid "Mis Favoritos", lista "Mis Reseñas" con editar/eliminar.

## Moderación (Super Admin)
- [x] Filament Resource de moderación en `/admin` (filtros Reportadas/Ocultas, acción ocultar/publicar).

## Tests Pest
- [x] Unicidad reseña por usuario-taller (upsert no duplica).
- [x] Unicidad favorito por usuario-taller (store idempotente no duplica).
- [x] Solo PUBLICADA cuenta en calificación promedio.
- [x] Visitante sin sesión no puede reseñar/favoritear (401).
- [x] Taller no puede editar reseñas ajenas.
- [x] DELETE de reseña ajena es rechazado.
- [x] DELETE de favorito inexistente responde 200 (idempotente).
