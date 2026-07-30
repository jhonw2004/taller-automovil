---
id: 019-mapa-busqueda-ux
---

# Plan — Experiencia de mapa en `/talleres/buscar`

## Alcance técnico

Se tocan: `resources/views/marketplace/search/index.blade.php`, un nuevo partial dedicado a esta vista (`marketplace/search/map-experience.blade.php`, no se reutiliza `search-experience.blade.php` que sigue sirviendo al layout embebido de otras vistas si existiera), `resources/views/marketplace/layouts/app.blade.php` (variante de layout "app-shell", opt-in, sin cambiar el layout por defecto), componentes `components/marketplace/{map,search-filters}.blade.php`, `resources/js/alpine/components/map.js`, `resources/js/alpine/store.js`, `resources/css/app.css`. Toque mínimo de backend: `App\Models\Taller::toSearchJsonResponse()` (agregar `logo_url`/`direccion`, columnas ya existentes, cero lógica nueva). **No se toca**: rutas, `TallerBusquedaApiController` (filtros/orden), migraciones, permisos.

## 1. Layout "app-shell" (mapa a pantalla completa sin tapar el header)

`resources/views/marketplace/layouts/app.blade.php` gana una variante opt-in vía `@section('layout_variant', 'app-shell')`, sin afectar ninguna otra vista (por defecto sigue igual):

```blade
@php($layoutVariant = trim($__env->yieldContent('layout_variant')))
<body class="bg-paper font-cosmica text-graphite antialiased @if($layoutVariant === 'app-shell') flex h-dvh flex-col overflow-hidden @endif">
    <x-marketplace.toast-container />
    <x-marketplace.nav />

    @unless($layoutVariant === 'app-shell')
        {{-- banners de session('status')/session('error') igual que hoy --}}
    @endunless

    <main class="@if($layoutVariant === 'app-shell') min-h-0 flex-1 @endif">
        @yield('content')
    </main>

    @unless($layoutVariant === 'app-shell')
        <x-marketplace.footer />
    @endunless
</body>
```

Esto garantiza el requisito "el header nunca se tapa" con flexbox real (`header` fuera del flujo de scroll, `main` ocupa el resto vía `flex-1 min-h-0`), sin offsets en píxeles ni depender de la altura exacta del header (`nav.blade.php` no cambia). `search/index.blade.php` pasa a usar `@section('layout_variant', 'app-shell')` y su `@section('content')` es un único contenedor `class="relative flex h-full w-full"` que aloja el mapa (fondo, `absolute inset-0`) y el panel flotante (`absolute`/`relative` por encima, `z-10`).

Nota: el banner `session('status')`/`session('error')` no se muestra en esta vista con este cambio — verificado que `/talleres/buscar` no tiene ningún flujo de redirect-with-session hoy (búsqueda es 100% cliente vía `fetch`), por lo que no hay regresión real.

## 2. Estructura del partial `map-experience.blade.php`

Reemplaza el bloque `flex flex-col gap-24 lg:flex-row` de `search-experience.blade.php` (que se mantiene intacto para cualquier otro consumidor) por una composición en capas dentro de un contenedor `h-full`:

1. **Capa mapa** (`absolute inset-0 z-0`): `<x-marketplace.map height="h-full" />`.
2. **Capa controles del mapa** (`absolute z-10`, posición esquina, ej. `bottom-24 right-16` en desktop / ajustado en móvil para no chocar con la hoja inferior): botones propios de zoom in/out y "centrar en mi ubicación" (ver §4 — no son los controles nativos de Leaflet, que se deshabilitan con `zoomControl: false`).
3. **Capa drawer de filtros/resultados** (desktop/tablet: `absolute left-16 top-16 bottom-16 z-20 w-[360px] max-w-[90vw]` con `overflow-y-auto`, fondo `bg-white`, `rounded-cards`, `shadow-lg`; colapsable con un botón que reduce el drawer a una pestaña angosta con ícono, controlado por `x-data="{ expanded: true }"` local a este partial — no requiere estado global nuevo en `Alpine.store`).
4. **Capa hoja inferior en móvil** (`<768px`, reemplaza al drawer lateral vía `md:hidden` / `hidden md:block` en cada variante): mismo contenido de filtros+lista, presentado como bottom sheet con 3 estados (`peek`/`half`/`full`) manejados con `x-data="{ sheet: 'peek' }"` y clases de altura condicionales (`h-[88px]` peek, `h-1/2` half, `h-[calc(100%-56px)]` full) + transición CSS. Un simple *drag handle* visual con `@click` para ciclar estados es suficiente para el MVP — no se requiere gesto de arrastre libre (fuera de alcance, ver `spec.md`).
5. **Capa card flotante de detalle**: ver §5.

## 3. Marcadores propios (`resources/js/alpine/components/map.js`)

- Se reemplaza el ícono por defecto de Leaflet (`L.Icon.Default`, actualmente configurado con los PNG de `leaflet/dist/images/`) por un `L.divIcon` propio con un SVG inline (pin con la paleta de `016`: `obsidian`/`graphite` normal, `ember` para el marcador seleccionado), evitando cargar los PNG por defecto para los marcadores de talleres (se mantiene el import de los PNG solo si algún otro punto del código los sigue usando; si no, se elimina).
- Estado "seleccionado": se guarda el `id` del taller seleccionado en el store (`$store.search.selected`, nuevo campo, mismo store existente — no es una feature nueva, es estado de UI derivado de la búsqueda ya existente). `updateMarkers()` re-renderiza el ícono del marcador afectado cuando `selected` cambia, sin recrear todos los marcadores.
- Click en marcador → `this.$store.search.selected = taller.id` (dispara la card flotante, ver §5) + `map.panTo(...)` si el marcador queda parcialmente fuera del viewport visible (considerando el drawer/hoja inferior como "zona ocupada", con `paddingTopLeft`/`paddingBottomRight` de Leaflet si aplica).
- Click en el mapa fuera de cualquier marcador → `this.$store.search.selected = null`.

## 4. Controles propios del mapa

- `L.map(el, { zoomControl: false, attributionControl: false })` (ver §6 para atribución).
- Botones propios (Blade + Alpine, no controles Leaflet) para zoom in/out (`map.zoomIn()`/`map.zoomOut()`) y "centrar en mi ubicación" (llama a `$store.search.useMyLocation()`, ya existente — sin lógica nueva), estilados con los tokens de `016` (`rounded-buttons`, `shadow-md`, `bg-white`), `aria-label="Acercar"`/`"Alejar"`/`"Usar mi ubicación"`, tamaño táctil ≥40px (`size-40` o mayor).
- Posicionamiento: fijo dentro de la capa de controles (§2.2), con `bottom` suficiente para no superponerse con la hoja inferior en estado `peek` en móvil (usar la altura fija de `peek` para calcular el offset, ej. `bottom-[104px] md:bottom-24`).

## 5. Card flotante de detalle

- Se usa el popup nativo de Leaflet (`marker.bindPopup(html, { closeButton: false, autoPan: true, maxWidth: 320, className: 'taller-popover' })`) para heredar gratis el anclaje al marcador y el auto-pan al abrir — no se reimplementa tracking de posición a mano. El HTML del popup se genera en JS a partir del taller (mismo objeto ya presente en `$store.search.results`), incluyendo: nombre, categorías (máx. 2-3 + "+N"), `★ calificacion_promedio (cantidad_resenas)`, badge abierto/cerrado, `direccion`, `distancia_km` si aplica, y un `<a href="/talleres/{slug}">Ver perfil completo</a>`. Botón de cierre propio (no el `closeButton` nativo de Leaflet, para controlarlo desde `$store.search.selected = null` y mantener un solo punto de verdad).
- `resources/css/app.css`: clase `.taller-popover` que sobreescribe el `.leaflet-popup-content-wrapper`/`.leaflet-popup-tip` por defecto (fondo blanco, `rounded-cards`, `shadow-lg`, sin el padding/estilo genérico de Leaflet) para que se vea como una card del design system, no como un popup de Leaflet sin estilizar.
- En móvil (`<768px`), el popup de Leaflet se **suprime** (`el.closePopup()` inmediato tras abrir, o gate condicional antes de `bindPopup`/`openPopup` según `window.matchMedia('(max-width: 767px)')`) y en su lugar se muestra la card como una hoja inferior propia (mismo contenido, mismo componente Blade reutilizado con slots/props, `x-show="$store.search.selected"` + `x-transition`), consistente con el patrón de Google Maps mobile.
- El contenido de la card (nombre, categorías, calificación, badge, dirección, distancia, CTA) se extrae a un partial Blade `components/marketplace/taller-popover-content.blade.php` reutilizado tanto por el HTML string del popup de Leaflet (renderizado server-side una vez como plantilla, o construido en JS con los mismos datos — a decidir en implementación cuál genera menos duplicación) como por la hoja inferior móvil, para no mantener el marcado dos veces.

### Backend: campos adicionales en la respuesta de búsqueda

`app/Models/Taller.php::toSearchJsonResponse()` agrega `'logo_url' => $this->logo_url` y `'direccion' => $this->direccion` (ambas columnas ya existentes en la tabla `talleres` desde `003-gestion-talleres`, ya usadas en otras vistas como `workshop-card.blade.php`). No hay migración ni lógica nueva — es exactamente el tipo de cambio que `018-modernizacion-ui/plan.md` ya autorizó ("pasar datos ya calculados, nunca lógica nueva"). Se actualiza el ejemplo de payload en `005-marketplace-busqueda-perfil/spec.md` si counts como cambio de contrato (evaluar en implementación si amerita nota de spec o alcanza con el comentario en el modelo).

## 6. Atribución Leaflet / OpenStreetMap

- Verificar con Context7 (`leaflet`) la API vigente de `L.control.attribution` antes de escribir código (mismo hábito que `018-modernizacion-ui/plan.md` para Filament).
- Enfoque previsto: `attributionControl: false` en `L.map(...)`, luego `L.control.attribution({ prefix: false, position: 'bottomright' }).addTo(map)` — `prefix: false` quita el autocrédito "Leaflet"; el control resultante solo contiene el `© OpenStreetMap contributors` que Leaflet agrega automáticamente por el `tileLayer.attribution` ya configurado en `addTileLayer()`.
- CSS: reducir tamaño de fuente y opacidad del control resultante (`.leaflet-control-attribution`) a algo discreto (ej. `font-size: 10px`, `opacity: .55` con `hover:opacity-100`), sin `display:none` ni `visibility:hidden` — debe seguir siendo legible/clickeable y alcanzable por teclado/lector de pantalla.
- Este cambio vive en `addTileLayer()` (compartida por `map()` y `singleMap()` en `map.js`), por lo que el mapa de un solo marcador del perfil público (`workshops/show.blade.php`) hereda la misma corrección sin tocar esa vista — consistente con "No regresión funcional" del `spec.md`.

## 7. Componentes reutilizados sin cambios de API

- `<x-skeleton>`, `<x-empty-state>`, `<x-badge>`, `<x-marketplace.star-rating>`: mismas props, se insertan dentro del drawer/hoja inferior en vez de la columna 35% actual.
- `search-filters.blade.php`: sin cambios de `@props` ni de `x-model`, solo el contenedor que lo envuelve cambia (pasa de vivir en una `<x-card>` estática a vivir dentro del drawer/hoja scrollable).
- Se evalúa reemplazar el markup inline de cada resultado en `search-experience.blade.php` (líneas 38-64) por `<x-marketplace.workshop-card>` (ya existe, hoy sin consumidores) dentro del nuevo partial, para no duplicar el patrón de card — variante compacta si el ancho del drawer (`360px`) lo requiere.

## Verificación

- Suite completa de Pest en verde (ningún test de `tests/Feature/` depende de la estructura HTML de `search/index.blade.php` más allá de la presencia de resultados — se revisa antes de tocar la vista).
- `npm run build` sin errores.
- Verificación manual en los 3 breakpoints de referencia (375px, 768px, 1280px): header nunca tapado, drawer/hoja inferior funcional, card flotante se abre/cierra/reemplaza correctamente, controles de mapa alcanzables y no tapados, atribución OSM visible pero discreta y sin el texto "Leaflet".
- Verificación de accesibilidad básica: navegación por teclado hasta los controles del mapa y el botón de cierre de la card, `aria-label` en cada control, contraste AA en el texto sobre el mapa.
- `vendor/bin/pint --dirty` antes de cerrar la feature.
