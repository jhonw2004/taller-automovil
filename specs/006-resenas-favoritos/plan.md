# Plan — Reseñas y Favoritos

## Tablas

### `resenas`
- `usuario_marketplace_id`, `taller_id` FK NOT NULL. `orden_trabajo_id` FK NULL -> `ordenes_trabajo.id` (referencia opcional, ver `011-ordenes-trabajo`).
- `calificacion` SMALLINT, `CHECK (calificacion BETWEEN 1 AND 5)`. `comentario` TEXT NULL.
- `estado` VARCHAR(20) DEFAULT 'PUBLICADA', `CHECK (estado IN ('PUBLICADA','OCULTA','REPORTADA'))`.
- `UNIQUE (usuario_marketplace_id, taller_id)`. Soft delete.

### `favoritos`
- `usuario_marketplace_id`, `taller_id` FK NOT NULL. `UNIQUE (usuario_marketplace_id, taller_id)`. Sin `updated_at` (solo alta/baja).

## Endpoints API (JSON — consumidos por Alpine)

| Método | Ruta | Controlador | Propósito |
|---|---|---|---|
| POST | `/api/resenas` | `ResenaApiController@storeOrUpdate` | Crear o actualizar reseña (upsert) |
| DELETE | `/api/resenas/{id}` | `ResenaApiController@destroy` | Eliminar propia reseña |
| POST | `/api/favoritos` | `FavoritoApiController@store` | Agregar favorito (idempotente) |
| POST | `/api/favoritos/delete` | `FavoritoApiController@destroy` | Quitar favorito (idempotente) |

- Todos los endpoints requieren guard `web` (usuario marketplace autenticado) y `throttle:10,1`.
- El endpoint `POST /api/resenas` recibe `taller_id` (obligatorio), `calificacion` (1-5), `comentario` (opcional). Si ya existe una reseña del mismo `usuario_marketplace_id` + `taller_id`, hace UPDATE; si no, INSERT.
- El endpoint `DELETE /api/resenas/{id}` verifica que la reseña pertenezca al usuario autenticado.
- El endpoint `POST /api/favoritos` recibe `taller_id`; si el favorito ya existe, responde 200 sin duplicar (idempotente).
- El endpoint `POST /api/favoritos/delete` recibe `taller_id`; si no existe, responde 200 igualmente (idempotente).

## Implementación

- Modelo `Resena` dispara evento `ResenaGuardada`/`ResenaEliminada`; listener en `003-gestion-talleres` recalcula `calificacion_promedio`/`cantidad_resenas` del taller filtrando `estado = 'PUBLICADA'`.
- Guard `web` obligatorio en rutas de creación/edición de reseña y de favorito.
- Moderación (`OCULTA`/`REPORTADA`) solo disponible en panel Super Admin (`/admin`), Resource `ModeracionResenasResource` con filtros "Reportadas"/"Ocultas" y acción de publicar/ocultar.
- Componente Blade de reseñas en el perfil público del taller (`005-marketplace-busqueda-perfil`): formulario "Escribir reseña" si hay sesión y no reseñó antes; muestra su propia reseña con editar/borrar si ya existe.
