---
id: 006-resenas-favoritos
status: implemented
depends_on: [001-identidad-autenticacion, 003-gestion-talleres]
resumen: "Reseñas (1 por usuario-taller, moderables) y favoritos de talleres para usuarios marketplace autenticados."
---

# Reseñas y Favoritos

## Propósito

Dar a los usuarios marketplace la capacidad de calificar y guardar talleres, con una única reseña por taller y moderación por super admin.

## Actores

- Usuario marketplace autenticado.
- Super admin (modera reseñas).

## Criterios de aceptación

### Reseñas

- Solo un usuario marketplace autenticado puede crear una reseña; un visitante sin sesión no puede.
- Un usuario marketplace tiene como máximo una reseña por taller (`UNIQUE (usuario_marketplace_id, taller_id)`); un segundo intento de reseñar el mismo taller se rechaza o edita la existente, no crea una nueva.
- La calificación es un entero entre 1 y 5; el comentario es opcional.
- Una reseña tiene estado `PUBLICADA`, `OCULTA` o `REPORTADA`. Solo las `PUBLICADA` cuentan para `calificacion_promedio`/`cantidad_resenas` del taller (`003-gestion-talleres`).
- El autor puede editar su propia reseña.
- El autor puede eliminar su propia reseña (soft delete) si el sistema lo permite.
- El taller (owner/admin/empleado) no puede editar reseñas de usuarios.
- Solo el super admin puede ocultar o marcar como reportada una reseña, con fines de moderación.
- Dado un usuario marketplace autenticado, cuando envía `POST /api/resenas` con `taller_id`, `calificacion` y opcional `comentario`, entonces si no existe reseña previa del usuario para ese taller, se crea una nueva; si ya existe, se actualiza la existente (upsert).
- Dado un usuario marketplace autenticado, cuando envía `DELETE /api/resenas/{id}` de su propia reseña, entonces se marca como soft-delete y se recalcula la calificación del taller.

### Favoritos

- Solo un usuario marketplace autenticado puede marcar un taller como favorito.
- Un usuario puede marcar un taller como favorito una sola vez (`UNIQUE (usuario_marketplace_id, taller_id)`).
- Un favorito puede eliminarse.
- Los favoritos no afectan la calificación del taller; se usan para el listado personal "Mis Favoritos" del dashboard del usuario.
- Dado un usuario marketplace autenticado, cuando envía `POST /api/favoritos` con `taller_id`, entonces se crea el favorito (si no existe) o se devuelve el existente (idempotente).
- Dado un usuario marketplace autenticado, cuando envía `POST /api/favoritos/delete` con `taller_id`, entonces se elimina el favorito si existe; si no existe, la operación es exitosa (idempotente).

## Fuera de alcance (MVP)

- Respuesta pública del taller a una reseña.
- Reseñas con fotos/adjuntos.
