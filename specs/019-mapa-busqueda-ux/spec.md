---
id: 019-mapa-busqueda-ux
status: draft
depends_on: [005-marketplace-busqueda-perfil, 018-modernizacion-ui]
resumen: "Rediseño de /talleres/buscar como experiencia 'mapa primero' (estilo Google Maps adaptado a Leaflet/OpenStreetMap): mapa a pantalla completa sin solaparse con el header, card flotante de taller al hacer click en un marcador, marcadores y controles propios, atribución Leaflet/OSM minimizada, totalmente responsivo."
---

# Búsqueda de talleres — experiencia de mapa

## Propósito

`005-marketplace-busqueda-perfil` definió el comportamiento funcional de la búsqueda (filtros, API, resultados). `016-ui-design-system` dejó el layout actual como panel 35% (filtros + lista) / 65% (mapa, `h-[420px] lg:min-h-[560px]`) dentro del flujo normal de la página. `018-modernizacion-ui` dejó pendiente, sin especificar, "auditar y ajustar `search/index.blade.php` en los 3 breakpoints" — **esta feature reemplaza ese ítem pendiente** con un rediseño completo y específico de esa vista, no solo una auditoría de espaciado.

El mapa deja de ser un bloque más dentro de la página y pasa a ser el elemento principal de la experiencia: ocupa el espacio disponible bajo el header, con los filtros, la lista de resultados y el detalle de un taller flotando **sobre** él, de forma similar a Google Maps, pero construida sobre Leaflet + OpenStreetMap (el stack ya definido en `memory/constitution.md` §1 no cambia) y con el foco puesto en talleres, no en direcciones genéricas.

Es una feature **puramente de presentación e interacción de UI**, igual que `018`: no cambia la lógica de filtrado, el modelo `Taller`, las rutas ni `Alpine.store('search')` salvo la extensión de datos estrictamente necesaria para la card flotante (ver "Fuera de alcance"). El buscador (`search-experience.blade.php`) usado por el Home queda **fuera** de esta spec (`018` ya decidió que el Home no embebe el mapa) — esta feature solo toca la página dedicada `/talleres/buscar`.

## Principios

- **El mapa es el protagonista, no un bloque más:** ocupa todo el espacio vertical disponible bajo el header (`100dvh` menos la altura real del header), en los 3 breakpoints. Ningún otro elemento reduce el mapa a una caja de 420px dentro de una página que además scrollea.
- **El header nunca se tapa ni se lo tapa:** el mapa vive en un contenedor que respeta el alto real del header (`sticky`, `z-40`) mediante layout flexbox (`header` + `main` como hijos de un contenedor `h-dvh`), no offsets en píxeles hardcodeados que se desincronizan si el header cambia de alto.
- **Filtros, lista y detalle flotan sobre el mapa, no lo empujan:** siguiendo el patrón de Google Maps, el panel de filtros/resultados es un drawer superpuesto (con su propio scroll interno) y el detalle de un taller es una card flotante — el mapa como `<canvas>` visual nunca cambia de tamaño cuando el usuario abre/cierra estos paneles.
- **Con énfasis en talleres, no en el mapa genérico:** marcadores propios (no el pin azul por defecto de Leaflet), estado visual distinto para el marcador seleccionado, y la card flotante prioriza los datos que un usuario necesita para decidir (calificación, si está abierto, categoría, distancia) antes que datos genéricos de ubicación.
- **Cero regresión funcional:** mismos filtros, mismo `Alpine.store('search')`, mismo endpoint `GET /api/talleres/search`, mismas rutas. Si una vista pierde un elemento de layout, la funcionalidad sigue accesible desde otro punto de la misma pantalla.
- **Mobile-first real:** los 3 breakpoints de `memory/constitution.md` §5 se prueban explícitamente, no se asume que "colapsar a una columna" alcanza — en mapas, colapsar mal significa un mapa inutilizable o tapado por controles.
- **La atribución de OpenStreetMap no se elimina, se minimiza:** es un requisito de la licencia de los tiles (no es una preferencia visual descartable). Se oculta la marca propia de "Leaflet" (autocrédito de la librería, no obligatorio) y se reduce la atribución de OSM a un control pequeño, discreto y de bajo contraste — visible y accesible, nunca eliminado del DOM ni ocultado con `display: none`/`visibility: hidden` real.
- **Animación consistente con `018`:** transiciones de apertura/cierre de la card flotante y el drawer usan las mismas convenciones ya definidas (CSS/Alpine `x-transition`, sin librerías nuevas, `prefers-reduced-motion` respetado, sin bloquear contenido si JS falla — degrada al layout de lista simple).

## Actores

Los mismos de `005-marketplace-busqueda-perfil`: visitante público y usuario marketplace autenticado (misma capacidad de búsqueda; favoritear/reseñar siguen fuera de esta vista, ver `006-resenas-favoritos`).

## Criterios de aceptación

### Layout general — mapa a pantalla completa

- Dado un visitante en `/talleres/buscar`, cuando la página carga en cualquier breakpoint, entonces el mapa ocupa el 100% del ancho y el 100% del alto disponible bajo el header, sin scroll de página (el scroll ocurre dentro del panel de filtros/lista o dentro del mapa mismo, nunca en `<body>`).
- Dado el header `sticky` del marketplace, cuando el mapa está visible, entonces nunca lo tapa ni queda tapado por él, en ningún breakpoint ni durante el scroll del panel flotante — verificado con el header en su altura real renderizada, no con un valor fijo asumido.
- Dado `/talleres/buscar`, entonces el footer del marketplace no se muestra en esta vista (layout tipo "app" de una sola pantalla, igual que el patrón de Google Maps) — el footer sigue existiendo y mostrándose sin cambios en el resto de vistas del marketplace.
- Dado el contenedor del mapa, cuando la ventana cambia de tamaño (resize) o el teclado virtual aparece en móvil, entonces el mapa se reajusta a la nueva altura disponible sin quedar cortado ni dejar espacio en blanco.

### Panel de filtros y resultados (flotante sobre el mapa)

- Dado un visitante en escritorio (`>1024px`), entonces el panel de filtros + lista de resultados aparece como un drawer flotante sobre el borde izquierdo del mapa (con su propia sombra/borde, fondo sólido, scroll interno independiente del mapa), y puede colapsarse/expandirse con un control visible sin recargar la página ni perder el estado de la búsqueda.
- Dado el panel de filtros/resultados colapsado, entonces el mapa ocupa el ancho completo y un control persistente permite volver a expandirlo.
- Dado un visitante en tablet (`768–1024px`), entonces el panel se comporta igual que en escritorio pero con un ancho reducido, sin recortar controles ni forzar scroll horizontal.
- Dado un visitante en móvil (`<768px`), entonces el panel de filtros/resultados se presenta como una hoja inferior ("bottom sheet") que por defecto muestra una franja reducida (con al menos el conteo de resultados y un control para expandir) y puede expandirse a pantalla completa o volver a colapsarse mediante un control táctil, sin que el mapa deje de ser interactivo cuando la hoja está colapsada.
- Dado el buscador por nombre, el selector de categoría, el radio, la calificación mínima, "abierto ahora" y "usar mi ubicación" ya existentes (`005-marketplace-busqueda-perfil`), entonces siguen funcionando exactamente igual (mismos campos, mismos eventos, mismo store) — solo cambia dónde y cómo se presentan visualmente.
- Dado el listado de resultados dentro del panel, cuando el usuario pasa el cursor o hace click/tap sobre un taller de la lista, entonces el marcador correspondiente en el mapa se resalta y el mapa se centra en él; el flujo inverso también aplica (ver siguiente sección).

### Marcadores propios

- Dado un resultado de búsqueda con `lat`/`lon` no nulos, entonces se representa en el mapa con un marcador propio del design system (no el pin azul por defecto de Leaflet), coherente con la paleta de `016-ui-design-system`.
- Dado un marcador correspondiente al taller actualmente seleccionado (por click en el mapa o en la lista), entonces se distingue visualmente del resto (color/tamaño/elevación distintos), y vuelve a su estado normal cuando se deselecciona.
- Dado que cambian los filtros o resultados, entonces los marcadores se actualizan (agregan/quitan) sin recargar la página, igual que hoy.

### Card flotante de detalle (click en un marcador)

- Dado un visitante que hace click/tap sobre un marcador en el mapa, entonces aparece una card flotante anclada a ese marcador (o, en móvil, una hoja inferior) con al menos: nombre del taller, categorías, calificación promedio y cantidad de reseñas, estado "Abierto ahora"/"Cerrado", dirección, distancia (si se buscó con ubicación), y un botón/enlace para ver el perfil completo (`route('talleres.show', $slug)`).
- Dado la card flotante abierta, entonces tiene un control explícito para cerrarla (botón "×" o equivalente accesible) además de cerrarse al hacer click en un área vacía del mapa o al seleccionar otro marcador.
- Dado dos marcadores distintos, cuando el usuario hace click en el segundo mientras la card del primero está abierta, entonces la card anterior se reemplaza por la del nuevo taller (nunca hay más de una card flotante abierta a la vez).
- Dado el mapa hace pan/zoom mientras la card está abierta, entonces la card se mantiene anclada visualmente a su marcador (o se cierra de forma predecible si el marcador sale del viewport — nunca queda flotando en una posición incorrecta).
- Dado un click/tap en un ítem de la lista de resultados del panel, entonces se comporta igual que hacer click en su marcador: el mapa centra ese taller y abre su card flotante.
- Dado la card flotante en móvil (hoja inferior), entonces no oculta por completo el mapa ni los controles de zoom/ubicación, y puede cerrarse deslizando hacia abajo o con el control de cierre explícito.

### Controles del mapa

- Dado el mapa, entonces expone controles propios para zoom in/out y para "centrar en mi ubicación", posicionados de forma que nunca quedan tapados por el header, el panel de filtros/lista ni la card flotante, en ningún breakpoint.
- Dado el control "centrar en mi ubicación", cuando se activa, entonces reutiliza el mismo flujo de geolocalización ya implementado (`$store.search.useMyLocation()`), sin duplicar lógica de negocio nueva.
- Dado los controles del mapa, entonces son operables por teclado (foco visible, `Enter`/`Space` activan) y tienen `aria-label` descriptivo, según `memory/constitution.md` §5.

### Atribución Leaflet / OpenStreetMap

- Dado el mapa cargado, entonces el texto de autocrédito de "Leaflet" no es visible en la interfaz.
- Dado el mapa cargado, entonces la atribución de OpenStreetMap (`© OpenStreetMap contributors`, con su enlace) sigue presente y accesible en el DOM, pero visualmente reducida a un control pequeño y de bajo contraste que no compite con el resto de la UI (p. ej. colapsado en una esquina, similar a los enlaces legales pequeños de Google Maps).
- Dado un lector de pantalla o navegación por teclado, entonces el enlace de atribución de OpenStreetMap sigue siendo alcanzable (no se usan técnicas que lo oculten también de tecnología de asistencia).

### Estados (carga, vacío, error)

- Dado que la búsqueda está en curso, entonces el panel de resultados muestra el mismo patrón de `<x-skeleton>` ya usado hoy, sin bloquear la interacción con el mapa.
- Dado cero resultados para los filtros aplicados, entonces se muestra el mismo `<x-empty-state>` ya usado hoy, dentro del panel/hoja inferior, con el mapa visible detrás.
- Dado un error de red o de la API, entonces se muestra el mismo mensaje de error ya usado hoy, sin romper el mapa ni dejarlo en blanco.

### Responsividad

- Dado el mapa y sus paneles en `<768px`, entonces no hay overflow horizontal en ningún elemento, los controles táctiles tienen un tamaño mínimo adecuado (≥40px), y la hoja inferior no cubre más del necesario para su estado (colapsada/expandida).
- Dado el mapa y sus paneles en `768–1024px`, entonces no hay elementos cortados, superpuestos de forma no intencional, ni scroll horizontal.
- Dado el mapa y sus paneles en `>1024px`, entonces el drawer flotante y la card de detalle tienen un ancho máximo legible (no se estiran a todo el ancho del panel en pantallas muy anchas).

### No regresión funcional

- Dado cualquier criterio de aceptación de `005-marketplace-busqueda-perfil` (filtros, API, "abierto ahora", orden de resultados), entonces sigue cumpliéndose sin cambios de comportamiento.
- Dado el perfil público de un taller (`workshops/show.blade.php`) y su mapa de un solo marcador, entonces no se rediseña en esta feature — solo hereda automáticamente la corrección de atribución Leaflet/OSM si esa corrección vive en el código compartido del componente de mapa (ver `plan.md`).

## Fuera de alcance (MVP de esta feature)

- Autocompletado o geocodificación de direcciones en texto libre (Nominatim u otro proveedor): no existe hoy en el proyecto; la única forma de ubicar al usuario sigue siendo "usar mi ubicación" (Geolocation API del navegador).
- Agrupamiento de marcadores (clustering) para grandes volúmenes de resultados.
- Modo oscuro del mapa o de la interfaz.
- Dibujo/edición de zonas o polígonos en el mapa.
- Rediseño del Home o de `search-experience.blade.php` tal como se usa fuera de `/talleres/buscar` — ya resuelto en `018-modernizacion-ui`.
- Cambios al endpoint `GET /api/talleres/search` o a `Alpine.store('search')` más allá de agregar campos ya existentes en la tabla `talleres` (`logo_url`, `direccion`) al payload de respuesta, necesarios para poblar la card flotante — no se agrega lógica de negocio nueva ni columnas nuevas.
- Rediseño visual del mapa de un solo marcador del perfil público (`workshops/show.blade.php`): sigue con su layout actual salvo por lo indicado en "No regresión funcional".
