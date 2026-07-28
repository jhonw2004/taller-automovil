---
id: 005-marketplace-busqueda-perfil
status: implemented
depends_on: [003-gestion-talleres, 016-ui-design-system]
resumen: "Búsqueda geográfica/por filtros de talleres y perfil público, sin requerir login."
---

# Marketplace: Búsqueda y Perfil Público

## Propósito

Permitir a cualquier visitante buscar talleres por ubicación, categoría y otros filtros, y ver el perfil público de un taller, sin necesidad de sesión.

## Actores

- Visitante público.
- Usuario marketplace autenticado (mismas capacidades de búsqueda + favoritear/reseñar, ver `006-resenas-favoritos`).

## Criterios de aceptación

### Búsqueda

- Dado cualquier visitante, cuando busca talleres por nombre, categoría, ubicación+radio, calificación mínima o "abierto ahora", entonces solo se devuelven talleres con `estado = ACTIVO` y `visible_en_mapa = TRUE`.
- La búsqueda geográfica usa `geom` con funciones PostGIS (radio en metros o bounding box); talleres inactivos o no visibles nunca aparecen en los resultados, sin excepción.
- El orden de resultados puede priorizar cercanía, calificación o cantidad de reseñas (parametrizable).

### API JSON de búsqueda

- Dado que el frontend Alpine necesita talleres para el mapa y la lista, cuando se consulta `GET /api/talleres/search?lat=&lon=&radio=&categoria=&q=&min_calificacion=&open_now=&sort=`, entonces devuelve un JSON con los talleres que cumplen los filtros, incluyendo `lat`, `lon` para los marcadores del mapa.
- La respuesta JSON incluye: `id`, `nombre`, `slug`, `descripcion_corta`, `lat`, `lon`, `calificacion_promedio`, `cantidad_resenas`, `categorias`, `abierto_ahora`, `distancia_km` (si se enviaron `lat`/`lon`).
- La respuesta nunca incluye talleres `INACTIVO`, `SUSPENDIDO`, `visible_en_mapa = FALSE`, ni soft-deleteados.
- El endpoint es público (sin autenticación). Incluye rate limiting `throttle:30,1`.

### Perfil público

- Dado un taller `ACTIVO` y visible, cuando se accede a su perfil público, entonces se muestran nombre, descripción, logo, dirección, teléfono, categorías, horarios, ubicación en mapa, calificación promedio, cantidad de reseñas y reseñas publicadas.
- Dado un taller `INACTIVO` o `SUSPENDIDO`, cuando se intenta acceder a su perfil público, entonces no se muestra (404 o equivalente).
- Dado un visitante sin sesión, cuando ve el perfil, entonces puede consultar todo el contenido pero no puede favoritear ni reseñar (esas acciones exigen login marketplace, ver `006-resenas-favoritos`).

### Abierto ahora

- El cálculo usa la zona horaria `America/La_Paz`.
- Dado el horario del día actual con `cerrado = TRUE`, el taller se considera cerrado.
- Dado un horario con `cerrado = FALSE`, el taller está "abierto ahora" si la hora actual está entre `hora_apertura` y `hora_cierre`.
- No se soportan horarios partidos en el MVP (ya declarado en `003-gestion-talleres`).

## Fuera de alcance (MVP)

- Búsqueda full-text avanzada (se usa PostGIS + `LIKE`, sin `laravel/scout`).
- Ordenamiento personalizado por el usuario (guardado de preferencias).
