# Tareas — 019-mapa-busqueda-ux

## Preparación

- [x] Revisar `tests/Feature/` existentes que toquen `/talleres/buscar` o `TallerBusquedaApiController` antes de tocar la vista — ninguno hace aserciones de HTML/estructura sobre `search-experience.blade.php` más allá de rutas/JSON, verificado por la suite en verde tras la migración.
- [x] Verificar con Context7 (`leaflet`) la API vigente de `L.control.attribution` (`prefix`, `position`), `L.divIcon`, `bindPopup`/`autoPan` y `zoomControl` — confirmado 2026-07-29 contra `/websites/leafletjs_reference-2_0_0`, coincide con el uso real en `map.js`.

## Layout "app-shell"

- [x] Variante opt-in `layout_variant = 'app-shell'` en `marketplace/layouts/app.blade.php` (flex `h-dvh`, `main` con `flex-1 min-h-0`, footer y banners de sesión omitidos solo en esta variante).
- [x] `search/index.blade.php` migrada a `@section('layout_variant', 'app-shell')`.
- [x] Header `sticky top-0 z-40` (`nav.blade.php`) — capas del mapa usan `z-20`, sin colisión de índices; verificado por lectura de código en 375/768/1280px (sin `claude-in-chrome` disponible para confirmar visualmente tras resize/teclado virtual, mismo motivo estructural de siempre).

## Partial `map-experience.blade.php`

- [x] `map-experience.blade.php` con las 4 capas (mapa `absolute inset-0`, controles, drawer desktop/tablet, hoja inferior móvil, card flotante).
- [x] Drawer flotante desktop/tablet colapsable (`x-data="{ expanded: true }"`), scroll interno propio (`overflow-y-auto`), ancho fijo legible (`w-[360px] max-w-[90vw]`), sin reflow del mapa (mapa es capa independiente `absolute inset-0`).
- [x] Hoja inferior móvil con 3 estados (`peek`/`half`/`full`), transición CSS (`.map-sheet` en `app.css`), control táctil (botón que cicla estados), mapa interactivo detrás en `peek` (hoja no cubre el mapa a esa altura).
- [x] `search-filters.blade.php` reutilizado sin cambios de `@props`/`x-model` dentro del drawer y la hoja inferior.
- [x] Resultados migrados a `_results-list.blade.php` compartido (markup propio, no `<x-marketplace.workshop-card>` — se evaluó y se descartó: esa card está pensada para grid de página completa, no para el ancho angosto del drawer/hoja; el markup propio ya sigue los mismos tokens del design system).

## Marcadores y controles propios

- [x] `L.divIcon` propio (SVG, paleta `016`: `#18181b` graphite normal / `#ff5a00` ember seleccionado) reemplazando el ícono azul por defecto.
- [x] `selected` en `Alpine.store('search')`; `syncSelection()`/`updateMarkers()` re-renderizan solo el ícono afectado.
- [x] `zoomControl: false` + botones propios de zoom in/out y "centrar en mi ubicación" (`useMyLocation()` existente reutilizado), `aria-label`, tamaño táctil `size-40` (40px), posición `right-16` con `controls-bottom-class` parametrizado para no chocar con la hoja inferior en `peek` (`bottom-[104px] md:bottom-24`).
- [x] Controles nuevos son `<button type="button">` nativos — operables por teclado (foco/`Enter`/`Space` nativos del elemento, con `focus:ring-2` visible) sin JS adicional.

## Card flotante de detalle

- [x] `components/marketplace/taller-popover-content.blade.php`: nombre, categorías, calificación, badge abierto/cerrado, dirección, distancia, CTA "Ver perfil completo".
- [x] Desktop/tablet: `bindPopup` con `className: 'taller-popover'`, `closeButton: false`, `autoPan: true`, botón de cierre propio (`data-close-popover`) ligado a `$store.search.selected = null` vía listener `popupopen`.
- [x] `.taller-popover` en `app.css` sobreescribe el popup por defecto de Leaflet con estilo de card del design system.
- [x] Móvil (`<768px`, `isMobileViewport()`): `syncSelection()` no abre el popup nativo; la capa 4 de `map-experience.blade.php` (`x-show="$store.search.selected"`, `x-transition`) muestra `taller-popover-content` como hoja inferior propia.
- [x] Un solo taller seleccionado a la vez (`store.select()` con toggle), click en marcador vacío/mapa deselecciona (`map.on('click', ...)`), click en ítem de lista abre la misma card (mismo `store.select()` que el marcador), `autoPan: true` + `panTo()` evitan que la card se descoloque en pan/zoom.

## Atribución Leaflet / OpenStreetMap

- [x] `addTileLayer()` compartida por `map()` y `singleMap()`: `attributionControl: false` en el mapa + `L.control.attribution({ prefix: false, position: 'bottomright' })` manual.
- [x] `.leaflet-control-attribution` en `app.css`: fuente/opacidad reducidos, `hover:opacity-100`/`focus-within:opacity-100`, sin `display:none`.
- [x] Enlace de atribución OSM es el `<a>` nativo generado por Leaflet (siempre en el DOM, alcanzable por teclado/lector de pantalla); `prefix: false` quita únicamente el texto "Leaflet ", el enlace a OpenStreetMap permanece.
- [x] `workshops/show.blade.php` usa `singleMap()`, que llama a la misma `addTileLayer()` — hereda la corrección de atribución sin tocar esa vista (confirmado por lectura de `map.js`).

## Backend — datos para la card flotante

- [x] `logo_url` y `direccion` agregados a `Taller::toSearchJsonResponse()`.
- [x] `005-marketplace-busqueda-perfil/spec.md` actualizado: contrato de respuesta del endpoint ahora lista `logo_url`/`direccion` con nota de que es aditivo (2026-07-29).

## Estados y responsividad

- [x] `<x-skeleton>` (3 filas) durante carga y `<x-empty-state>` en cero resultados dentro de `_results-list.blade.php`, con el mapa siempre visible detrás (capas independientes, drawer/hoja nunca ocultan el mapa por completo salvo hoja en `full`).
- [x] Mensaje de error de red/API (`$store.search.error`) se muestra dentro de la lista, mapa permanece visible detrás (nunca en blanco).
- [x] Revisados 375px/768px/1280px por lectura de clases Tailwind (drawer oculto `<md`, hoja oculta `>=md`, anchos con `max-w-[90vw]`/`max-w-[calc(100%-56px)]`) — sin verificación visual real en navegador (`claude-in-chrome` no disponible, mismo motivo estructural de todas las sesiones).

## Cierre de feature

- [x] Suite completa de Pest en verde.
- [x] `npm run build` sin errores.
- [x] `vendor/bin/pint --dirty` sin pendientes.
- [x] `status` de `specs/019-mapa-busqueda-ux/spec.md` actualizado a `implemented` (2026-07-29) — todos los bloques verificados salvo la verificación visual real en navegador, documentada como limitación estructural recurrente en `resume.md`, igual criterio que el resto de features `implemented` del proyecto.
