# Tareas — 019-mapa-busqueda-ux

## Preparación

- [ ] Revisar `tests/Feature/` existentes que toquen `/talleres/buscar` o `TallerBusquedaApiController` para no romper aserciones sobre HTML/JSON actual antes de tocar la vista.
- [ ] Verificar con Context7 (`leaflet`) la API vigente de `L.control.attribution`, `L.divIcon`, `bindPopup`/`autoPan` y `zoomControl` antes de escribir el JS de `map.js`.

## Layout "app-shell"

- [ ] Agregar variante opt-in `layout_variant = 'app-shell'` a `marketplace/layouts/app.blade.php` (flex `h-dvh`, `main` con `flex-1 min-h-0`, footer y banners de sesión omitidos solo en esta variante) sin alterar el comportamiento por defecto de ninguna otra vista.
- [ ] Migrar `search/index.blade.php` a `@section('layout_variant', 'app-shell')` con un contenedor de contenido `h-full`.
- [ ] Verificar que el header (`sticky`, `z-40`) nunca queda tapado ni tapa el mapa en 375px/768px/1280px, incluyendo tras resize y con teclado virtual abierto en móvil.

## Partial `map-experience.blade.php`

- [ ] Crear `resources/views/marketplace/search/map-experience.blade.php` con las capas: mapa (`absolute inset-0`), controles del mapa, drawer de filtros/resultados (desktop/tablet), hoja inferior (móvil), card flotante de detalle.
- [ ] Drawer flotante desktop/tablet: colapsable/expandible con estado local (`x-data`), scroll interno propio, ancho máximo legible, sin reflow del mapa al abrir/cerrar.
- [ ] Hoja inferior móvil: 3 estados (`peek`/`half`/`full`) con transición CSS, control táctil para ciclar estados, mapa interactivo cuando está en `peek`.
- [ ] Reutilizar `search-filters.blade.php` sin cambios de `@props`/`x-model` dentro del nuevo contenedor.
- [ ] Evaluar y, si aplica, migrar el markup inline de resultados a `<x-marketplace.workshop-card>` (variante compacta si el ancho del drawer lo exige).

## Marcadores y controles propios

- [ ] Reemplazar el ícono por defecto de Leaflet por un `L.divIcon` propio (SVG, paleta `016`), con estado visual distinto para el marcador seleccionado.
- [ ] Agregar `selected` a `Alpine.store('search')` (id del taller seleccionado); `updateMarkers()` re-renderiza solo el ícono afectado al cambiar.
- [ ] `zoomControl: false` en la inicialización del mapa; construir botones propios de zoom in/out y "centrar en mi ubicación" (reutilizando `useMyLocation()` existente), con `aria-label`, tamaño táctil ≥40px y posición que no choque con la hoja inferior en `peek`.
- [ ] Verificar operabilidad por teclado de los controles nuevos (foco visible, `Enter`/`Space`).

## Card flotante de detalle

- [ ] Crear `components/marketplace/taller-popover-content.blade.php` (o generador JS equivalente) con nombre, categorías, calificación, badge abierto/cerrado, dirección, distancia y CTA "Ver perfil completo".
- [ ] Desktop/tablet: `bindPopup` de Leaflet con `className: 'taller-popover'`, `closeButton: false`, `autoPan: true`, botón de cierre propio ligado a `$store.search.selected = null`.
- [ ] `resources/css/app.css`: clase `.taller-popover` que sobreescribe el estilo por defecto del popup de Leaflet para que se vea como una card del design system.
- [ ] Móvil (`<768px`): suprimir el popup nativo de Leaflet y mostrar la misma card como hoja inferior propia (`x-show="$store.search.selected"`, `x-transition`).
- [ ] Verificar: un solo taller seleccionado a la vez, click en marcador vacío del mapa deselecciona, click en ítem de la lista abre la misma card que el marcador, card no se descoloca al hacer pan/zoom.

## Atribución Leaflet / OpenStreetMap

- [ ] En `addTileLayer()` (`resources/js/alpine/components/map.js`, compartida por `map()` y `singleMap()`): `attributionControl: false` en el mapa + `L.control.attribution({ prefix: false, position: 'bottomright' })` agregado manualmente.
- [ ] CSS para `.leaflet-control-attribution`: tamaño de fuente y opacidad reducidos (discreto, no oculto), `hover:opacity-100`, sin `display:none`/`visibility:hidden`.
- [ ] Verificar que el enlace de atribución de OpenStreetMap sigue siendo alcanzable por teclado y lector de pantalla, y que el texto "Leaflet" ya no aparece en la UI.
- [ ] Confirmar visualmente que el mapa de un solo marcador del perfil público (`workshops/show.blade.php`) hereda la corrección sin tocar esa vista.

## Backend — datos para la card flotante

- [ ] Agregar `logo_url` y `direccion` a `App\Models\Taller::toSearchJsonResponse()` (columnas ya existentes, sin migración ni lógica nueva).
- [ ] Revisar si `005-marketplace-busqueda-perfil/spec.md` necesita una nota de actualización del contrato de respuesta del endpoint (campos agregados, no removidos — no debería romper consumidores existentes).

## Estados y responsividad

- [ ] Verificar `<x-skeleton>` durante carga y `<x-empty-state>` en cero resultados dentro del drawer/hoja inferior, con el mapa visible detrás en ambos casos.
- [ ] Verificar mensaje de error existente ante fallo de red/API sin dejar el mapa en blanco.
- [ ] Revisar los 3 breakpoints de referencia (375px, 768px, 1280px) sin overflow horizontal, sin elementos cortados/superpuestos no intencionales.

## Cierre de feature

- [ ] Suite completa de Pest en verde.
- [ ] `npm run build` sin errores.
- [ ] `vendor/bin/pint --dirty` sin pendientes.
- [ ] Actualizar `status` de `specs/019-mapa-busqueda-ux/spec.md` a `implemented` solo cuando todos los bloques de arriba estén marcados y verificados.
