---
tipo: documento de referencia de UI (no forma parte de specs/NNN-nombre numerado)
relacionado_con: [019-mapa-busqueda-ux, 005-marketplace-busqueda-perfil, 016-ui-design-system]
estado: vigente — v2, panel rediseñado como tarjeta flotante estilo Apple Maps (ver "Nota de adaptación")
resumen: "Spec de UI completa para /talleres/buscar: panel flotante estilo Apple Maps (rounded-cards, shadow-lg, margen) con buscador prominente, filtros colapsables (sin radio de búsqueda) y resultados como cards — usando el stack, el design system y los componentes REALES del proyecto. Implementado en su totalidad."
---

# Búsqueda de talleres (`/talleres/buscar`) — spec de UI completa

## Nota de adaptación

**v2 (esta revisión):** la v1 de este documento mantenía el panel como sidebar acoplado al borde,
sin esquinas redondeadas (decisión de la sesión 2026-07-30). El usuario pidió explícitamente que
la vista se sintiera como **Apple Maps real** (tarjeta flotante con margen, esquinas redondeadas,
sombra) manteniendo la paleta de colores del proyecto, que se rediseñaran los componentes del
buscador (no le gustaban los anteriores) y que se eliminara el filtro de radio de búsqueda por
innecesario. Esta revisión documenta esa dirección como la vigente — ver "Principios" y "Layout y
jerarquía" para el detalle de qué cambió respecto a la v1.

**v1 (versión original de este archivo):** era un prompt genérico de construcción de componentes
que asumía **Inertia.js** (este proyecto nunca lo usó — ver `memory/constitution.md` §1/§5) y una
app de mapa genérica con secciones "Buscar / Guías / Indicaciones" que no existen aquí. También
excluía la fila de filtros y la ficha de detalle de un lugar, dos piezas que este proyecto **ya
tenía implementadas y funcionando** (`005-marketplace-busqueda-perfil`, `019-mapa-busqueda-ux`).

Este documento reemplaza ese prompt por un spec ajustado a la realidad del proyecto: mismo
espíritu de interacción (mapa protagonista, panel lateral integrado, controles propios flotando
sobre el mapa — patrón "Apple Maps web" adaptado), pero con el vocabulario, los componentes y el
stack que el proyecto realmente usa. Sigue el estilo de `spec.md` del resto de `specs/`
(propósito, principios, criterios de aceptación Given/When/Then, fuera de alcance), sin vivir en
`specs/` como feature numerada porque documenta una vista ya construida, no una feature nueva a
implementar desde cero.

**Regla maestra de estilo (se mantiene del original, es la única parte 100% vigente sin cambios):**
la piel visual la pone el sistema de diseño ya implementado (`016-ui-design-system`: paleta
Awesomic, tokens `rounded-*`, `text-*`, espaciado). No se inventan colores, radios, sombras ni
tokens nuevos en esta vista — todo lo que no exista se agrega al sistema central, nunca de forma
local aquí.

**Trampa real encontrada en esta sesión — escala de espaciado cerrada:** `resources/css/app.css`
define `--spacing-4/8/12/16/20/24/28/32/36/40/48/64/68/80/120` como valores `@theme` explícitos
(no el `--spacing` fluido por defecto de Tailwind v4). Cualquier utilidad numérica
(`size-*`, `p*-*`, `gap-*`, `top/left/right/bottom-*`, `translate-*`) con un número **fuera** de
esa lista (ej. `size-18`, `px-14`) no falla ni se ignora: Tailwind cae al `--spacing` base
(`0.25rem`) y genera un valor completamente distinto al esperado (`size-18` → 72px en vez de
18px) — visualmente esto se ve como un ícono gigante y sin estilo aparente. Esta sesión encontró y
corrigió varios casos reales (`size-18`→`size-20`, `px-14`→`px-16`, `gap-6`→`gap-8` en
`search-filters.blade.php`, `map-experience.blade.php` y `taller-popover-content.blade.php`). Usa
**solo** números de la lista de arriba en clases de espaciado dentro de vistas del marketplace; si
hace falta un valor exacto fuera de esa escala, usa sintaxis arbitraria explícita (`size-[18px]`),
nunca un número "cercano" a mano.

## Stack real (corrige el prompt original)

- **Laravel + Blade + Alpine.js.** No Inertia.js, no componente de página del lado cliente
  independiente del servidor. El HTML lo renderiza Blade; la interactividad (input, hover,
  zoom, geolocalización, expandir/colapsar) vive en `Alpine.data(...)` y `Alpine.store(...)`.
- **Estado compartido:** `Alpine.store('search')` (`resources/js/alpine/store.js`) — ya cumple el
  rol de "useMapChrome" del prompt original (`query`, `category`, `radius`, `minRating`,
  `openNow`, `results`, `total`, `loading`, `error`, `hasSearched`, `selected`,
  `selectedTaller()`, `search()`, `useMyLocation()`, `select()`). No se crea un store nuevo para
  esta vista.
- **Resultados:** `GET /api/talleres/search` vía `fetch` (sin recargar la página), ya
  implementado — no cambia.
- **Layout persistente:** `marketplace/layouts/app.blade.php` con `@section('layout_variant',
  'app-shell')` (header `sticky` + `main` `flex-1 min-h-0`, sin banners de sesión ni footer en
  esta vista) — ya implementado, cumple el rol de "AppShell" del prompt original.
- **Estado en URL:** a diferencia del prompt original, hoy `query`/filtros/selección **no** se
  reflejan en el query string (búsqueda 100% en memoria del store). Sigue así — no se agrega
  sincronización con la URL en este documento (fuera de alcance, ver más abajo).

## Alcance real de la vista

**Se elimina del prompt original** (no aplica a este proyecto):
- El riel de navegación vertical (`SidebarRail`) con secciones "Buscar / Guías / Indicaciones".
  Este proyecto no tiene guías ni indicaciones de ruta; la única función de esta pantalla es
  buscar talleres. La marca y la navegación global ya viven en `<x-marketplace.nav>` (header
  superior, no un riel lateral) — no se duplica.
- El "conmutador de panel" como pieza del riel: el control de expandir/colapsar el panel vive
  directamente en el panel (botón `«` dentro del panel, botón `»` flotante cuando está
  colapsado), ya implementado.

**Se mantiene, contra lo que decía el prompt original:**
- La fila de filtros (nombre, categoría, calificación mínima, "abierto ahora", "usar mi
  ubicación") — el prompt original la excluía asumiendo un buscador de direcciones genérico; en
  este proyecto los filtros SON el producto (`005-marketplace-busqueda-perfil`) y deben seguir
  funcionando, aunque su presentación cambió (ver "Filtros y resultados" abajo: ahora colapsados
  detrás de un botón "Filtros", solo el campo de nombre queda siempre visible, estilo Apple Maps).
  El control de **radio de búsqueda** (slider en km) se eliminó de la UI por pedido explícito del
  usuario ("la búsqueda por radio no es necesaria") — `Alpine.store('search').radius` sigue
  existiendo internamente con un valor fijo, sin exponerse como control.
- La card/ficha flotante de detalle al seleccionar un marcador o una fila — el prompt original la
  dejaba como "gancho futuro"; aquí ya está implementada (`019-mapa-busqueda-ux`) y debe seguir
  funcionando.

## Principios (heredados del prompt original, siguen vigentes)

- El mapa es la superficie principal: ocupa el 100% del espacio bajo el header en los 3
  breakpoints, nunca se reduce a una caja fija dentro de una página que además scrollea.
- El panel de filtros/resultados y la card de detalle **flotan sobre el mapa** (capas
  superpuestas con `position: absolute` + `z-index`), no lo empujan ni lo redimensionan. Esta es
  una diferencia deliberada frente al prompt original (que pedía columnas hermanas que reflowean
  el mapa): redimensionar el `<canvas>` de Leaflet en cada apertura/cierre del panel obliga a
  llamar `invalidateSize()` y es una fuente real de glitches visuales; superponer una tarjeta
  flotante (con margen, `rounded-cards`, `shadow-lg`) logra el mismo resultado visual de Apple
  Maps sin ese costo.
- Los controles del mapa (ubicación, zoom) son independientes del panel: siempre visibles, nunca
  tapados por el panel, la hoja inferior o la card flotante.
- Nada se anima de golpe: expandir/colapsar panel, abrir/cerrar card, cambiar de estado la hoja
  inferior — todo con transición, respetando `prefers-reduced-motion`.
- **Revisión de esta sesión**: la decisión de la sesión 2026-07-30 (panel acoplado al borde, sin
  esquinas redondeadas ni sombra, "debe verse como un sidebar real") queda **superada** por el
  pedido explícito de que esta vista se sienta como Apple Maps manteniendo la paleta del proyecto.
  El panel de escritorio vuelve a usar los tokens normales del design system (`rounded-cards`,
  `shadow-lg`, `rounded-inputs`/`rounded-buttons` en los controles) — ya no es la única vista del
  marketplace con esa excepción de "sin bordes redondeados".

## Inventario de piezas — mapeo prompt original → componente real

| Pieza del prompt original | Componente/archivo real | Estado |
|---|---|---|
| AppShell | `marketplace/layouts/app.blade.php` (variante `app-shell`) | ✅ implementado |
| SidebarRail, NavButton | — (eliminado, ver "Alcance real") | N/A |
| SearchPanel | `marketplace/search/map-experience.blade.php` (sidebar desktop/tablet + hoja móvil) | ✅ implementado |
| SearchHeader | Cabecera del panel (`h1` + botón "Ocultar panel de filtros") | ⚠️ parcial, ver "Header del panel" abajo |
| SearchField | Input "Buscar por nombre" en `components/marketplace/search-filters.blade.php` | ✅ implementado (con lupa y botón de limpiar) |
| ResultsRegion | Contenedor `overflow-y-auto` del panel/hoja | ✅ implementado |
| ResultRow | Botones dentro de `marketplace/search/_results-list.blade.php` | ✅ implementado |
| MapCanvas | `components/marketplace/map.blade.php` + `Alpine.data('map')` (`resources/js/alpine/components/map.js`) | ✅ implementado |
| MapControls, LocateButton, ZoomControl | Slot `:controls="true"` de `map.blade.php` (botones `locate()`/`zoomIn()`/`zoomOut()`) | ✅ implementado, ver criterios abajo |
| useMapChrome (store) | `Alpine.store('search')` (`resources/js/alpine/store.js`) | ✅ implementado, sin renombrar |
| Ficha de detalle | Popup de Leaflet (desktop/tablet) + `components/marketplace/taller-popover-content.blade.php` (móvil) | ✅ implementado |

## Layout y jerarquía

- **Dado** un visitante en `/talleres/buscar` en cualquier breakpoint, **cuando** la página
  carga, **entonces** el mapa ocupa el 100% del ancho y el 100% del alto disponible bajo el
  header `sticky`, sin scroll de `<body>` (scroll solo dentro del panel/hoja/mapa).
- **Dado** el panel de escritorio (`≥768px`), **entonces** es una tarjeta flotante con margen
  respecto a los bordes del mapa (`left-16 top-16 bottom-16`, ancho fijo `380px`, `rounded-cards`,
  `border border-cloud`, `shadow-lg`) — estilo Apple Maps real, ya no el sidebar acoplado sin
  bordes de la sesión anterior.
- **Dado** el panel colapsado, **entonces** el mapa recupera el ancho completo y un botón
  flotante (`»`, `aria-label="Mostrar panel de filtros"`) permite volver a expandirlo sin perder
  el estado de la búsqueda (filtros y resultados se conservan en el store mientras el panel está
  oculto).
- **Dado** un visitante en móvil (`<768px`), **entonces** el panel se presenta como hoja inferior
  con 3 estados (`peek` 104px con conteo de resultados, `half` 50% de alto, `full` casi pantalla
  completa), alternando con un tap sobre el *drag handle*.
- **Dado** la card flotante de detalle abierta en móvil, **entonces** la hoja inferior se oculta
  (`x-show="!$store.search.selected"`) para no competir por el mismo espacio.

## Filtros y resultados

- **Dado** el panel/hoja, **entonces** el campo "Buscar talleres" (nombre) está siempre visible en
  la parte superior, con ícono de lupa y botón de limpiar (visible solo con texto) — es el control
  principal, estilo barra de búsqueda de Apple Maps.
- **Dado** un botón de filtro (ícono de sliders) junto al campo de búsqueda, **entonces** al
  pulsarlo se abre/cierra un panel secundario (`rounded-cards`, fondo `bg-paper`) con categoría,
  calificación mínima, "abierto ahora" y "usar mi ubicación" — estos controles ya no están
  apilados y siempre visibles, se mantienen ocultos hasta que el usuario pide verlos.
- **Dado** el control de **radio de búsqueda** (slider en km) que existía antes, **entonces** ya
  no aparece en la UI — se eliminó por pedido explícito ("la búsqueda por radio no es necesaria").
  `Alpine.store('search').radius` conserva un valor fijo (10 km) para no romper el contrato del
  endpoint `GET /api/talleres/search`, que solo lo usa cuando hay `lat`/`lon` activos.
- **Dado** categoría, calificación mínima, "abierto ahora" y "usar mi ubicación" ya implementados
  (`005-marketplace-busqueda-perfil`), **entonces** siguen funcionando exactamente igual en cuanto
  a comportamiento: mismos campos, mismo `x-model`, mismo `$store.search.search()` en cada
  cambio — solo cambió dónde viven visualmente (colapsados, no siempre expandidos).
- **Dado** una búsqueda en curso, **entonces** `ResultsRegion` muestra `<x-skeleton>` (3 bloques)
  sin bloquear la interacción con el mapa.
- **Dado** cero resultados tras una búsqueda, **entonces** se muestra `<x-empty-state>` dentro del
  panel/hoja, con el mapa visible detrás.
- **Dado** un error de red, **entonces** se muestra el mensaje de error ya implementado
  (`$store.search.error`), sin dejar el mapa en blanco.
- **Dado** el usuario hace hover (desktop) o tap (cualquier dispositivo) sobre una `ResultRow`,
  **entonces** el marcador correspondiente se resalta (`taller:hover` → `highlightMarker()`); al
  hacer click/tap, el taller queda seleccionado (`$store.search.select(id)`), el mapa centra ese
  marcador y se abre su card de detalle — mismo comportamiento que hacer click directo en el
  marcador.

## Controles del mapa — deben seguir funcionando (requisito explícito)

- **Dado** el mapa en cualquier breakpoint, **entonces** expone sus propios controles (no los
  nativos de Leaflet, deshabilitados con `zoomControl: false`): un botón "Centrar en mi
  ubicación" (`locate()` → delega en `$store.search.useMyLocation()`, misma lógica que el botón
  de filtros "Usar mi ubicación" — un solo punto de verdad de geolocalización) y un control
  segmentado de zoom con "Acercar" (`zoomIn()` → `map.zoomIn()`) y "Alejar" (`zoomOut()` →
  `map.zoomOut()`).
- **Dado** estos controles, **entonces** están posicionados en la esquina inferior derecha del
  mapa (`bottom-[104px] md:bottom-24 right-16`), de forma que nunca quedan tapados por el panel,
  la hoja inferior en estado `peek`, ni la card flotante, en ningún breakpoint.
- **Dado** estos controles, **entonces** son alcanzables por teclado (`Tab`, foco visible vía
  `focus:ring-2`), tienen `aria-label` descriptivo (`"Acercar"`, `"Alejar"`, `"Centrar en mi
  ubicación"`) y un tamaño táctil ≥40px (`size-40`).
- **Dado** que el usuario pulsa "Usar mi ubicación" (desde el mapa o desde los filtros),
  **entonces** el mapa centra la vista en la posición obtenida (`search:located` →
  `map.setView(...)`) y la búsqueda se relanza con esas coordenadas — ningún cambio de
  comportamiento respecto a lo ya implementado.
- Estos tres controles (ubicación, acercar, alejar) **no se tocan ni se renombran** al ajustar
  cualquier otra parte de esta vista: cualquier refactor de layout debe preservar exactamente
  estos `aria-label`, nombres de método (`locate`, `zoomIn`, `zoomOut`) y posición relativa.

## Card flotante de detalle

- **Dado** un click/tap sobre un marcador o una fila de resultado, **entonces** aparece el
  detalle del taller: nombre, hasta 3 categorías, calificación promedio + cantidad de reseñas,
  badge "Abierto ahora"/"Cerrado", dirección, distancia (si se buscó con ubicación) y enlace "Ver
  perfil completo" (`/talleres/{slug}`).
- **Dado** desktop/tablet, **entonces** el detalle es el popup nativo de Leaflet anclado al
  marcador (`.taller-popover`, con `border-radius: var(--radius-cards)`, consistente con el resto
  de tarjetas flotantes de esta vista).
- **Dado** móvil, **entonces** el detalle es una card con esquinas superiores redondeadas
  (`rounded-t-cards`) en la parte inferior (`taller-popover-content.blade.php`), reemplazando a la
  hoja de filtros mientras está abierta.
- **Dado** la card/popup abierta, **entonces** tiene un botón de cierre explícito
  (`data-close-popover` en desktop, botón `×` en móvil) que limpia `$store.search.selected`, y se
  reemplaza (nunca se apila) si el usuario selecciona otro taller.

## Header del panel

El prompt original pedía una cabecera contextual: control de "volver" cuando hay consulta o
resultados, control de "cerrar" cuando el campo está vacío. Hoy la cabecera del panel es fija
("Buscar talleres" + un único botón de colapsar). Esto es una decisión consciente, no un olvido:
en este proyecto el panel **no** es solo un campo de búsqueda de una sola línea (como en Apple
Maps), sino un panel de filtros con 5+ controles simultáneos — el patrón "volver/cerrar" de una
búsqueda de una sola pregunta no mapea limpiamente a "colapsar 5 filtros a la vez". Se documenta
como pendiente de decisión de producto, no se implementa en este documento:

- [ ] Evaluar si conviene un botón "Limpiar filtros" en la cabecera cuando algún filtro está
  activo (distinto de colapsar el panel entero). Sigue sin implementar — es una evaluación de
  producto abierta, no un requisito cerrado de este documento.
- [x] `aria-expanded` en el botón de colapsar/expandir el panel — implementado (`:aria-expanded="expanded"`
  en ambos botones de `map-experience.blade.php`).

## Teclado y accesibilidad

Vigente por `memory/constitution.md` §5 ("foco visible en navegación por teclado, atributos ARIA
en controles de mapa") y por el prompt original. Estado real verificado en el código:

- ✅ Controles del mapa (`locate`/`zoomIn`/`zoomOut`) y botones de colapsar panel: alcanzables por
  Tab, con `aria-label` y foco visible.
- ✅ Filtros: cada control tiene `<label>` asociado (no solo placeholder).
- ✅ **Esc**: cierra primero la card de detalle si está abierta (`$store.search.selected = null`);
  si no hay card abierta, colapsa el panel de escritorio (`expanded = false`) o vuelve la hoja
  inferior a `peek` (`sheet = 'peek'`) — `x-on:keydown.escape.window` en cada scope de
  `map-experience.blade.php`.
- ✅ **Flechas arriba/abajo entre `ResultRow`**: implementado en
  `marketplace/search/_results-list.blade.php` (`x-on:keydown.down/up.prevent`, mueve el foco al
  botón hermano siguiente/anterior dentro del contenedor de resultados).
- ✅ **`aria-expanded`**: presente en los botones de colapsar/expandir el panel de escritorio y en
  el *drag handle* de la hoja inferior (`sheet !== 'peek'`).

## Estados límite y vacíos

- Campo de búsqueda vacío sin resultados previos: el panel muestra el campo de búsqueda y el botón
  de filtros (colapsados) sin lista — no hay un estado "idle" separado, el mismo campo sirve de
  entrada tanto vacío como con texto.
- Resultados que exceden el alto disponible: scroll vertical interno del panel/hoja
  (`min-h-0 flex-1 overflow-y-auto`), cabecera y filtros permanecen fijos arriba.
- ✅ Zoom en el límite mínimo/máximo de Leaflet: el control segmentado deshabilita visualmente la
  mitad correspondiente (`zoomLevel`/`minZoom`/`maxZoom` expuestos por `Alpine.data('map')`,
  actualizados en el evento `zoomend` de Leaflet; `:disabled` + `disabled:opacity-40` en
  `components/marketplace/map.blade.php`).

## Fuera de alcance

- Autocompletado o geocodificación de direcciones en texto libre.
- Agrupamiento de marcadores (clustering).
- Modo oscuro del mapa o de la interfaz.
- Dibujo/edición de zonas o polígonos.
- Sincronizar `query`/filtros/selección con el query string de la URL.
- Cualquier sección de "Guías" o "Indicaciones" (no existen en el dominio de este proyecto).
- **Radio de búsqueda ajustable por el usuario**: eliminado deliberadamente de la UI (ver
  "Filtros y resultados"). No se reintroduce sin un pedido explícito nuevo.

## Cómo usar este documento

Todos los criterios de este documento están implementados y verificados (568/568 tests Pest,
`vendor/bin/pint --dirty` y `npm run build` sin pendientes — ver `resume.md`, sesiones
`019-mapa-busqueda-ux`, refinamientos posteriores y la sesión que cerró este spec). El único punto
abierto es una evaluación de producto explícita, no una brecha técnica: si conviene agregar un
botón "Limpiar filtros" en la cabecera del panel (ver "Header del panel"). No reabrir decisiones
ya tomadas (mapa-como-capas-superpuestas, sin riel de navegación, filtros siempre visibles, sin
sincronización con la URL).
