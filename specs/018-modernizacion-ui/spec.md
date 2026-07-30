---
id: 018-modernizacion-ui
status: implemented
depends_on: [016-ui-design-system]
resumen: "Modernización visual transversal de todas las vistas (marketplace, ERP, Super Admin) sin alterar lógica de negocio ni funcionalidad: Home rediseñado como landing page persuasiva (sin mapa/buscador embebido), formularios y componentes menos genéricos, tema Filament personalizado, y responsividad real en los 3 breakpoints en cada vista existente."
---

# UI — Modernización visual (marketplace + Filament)

## Propósito

`016-ui-design-system` definió la paleta, la tipografía y los componentes base (Awesomic zinc-gray + ember). Esta feature **no reemplaza esa base** — la usa como fundamento y la lleva un paso más allá: refina la composición visual de cada vista ya construida, personaliza Filament para que deje de verse como un panel administrativo genérico, y audita/corrige la responsividad real de cada componente en móvil, tablet y escritorio.

Es una feature **puramente de presentación**: cambia plantillas Blade, CSS, tema de Filament y composición de componentes. **No cambia** rutas, controladores, Actions, migraciones, permisos ni ningún criterio de aceptación funcional de las specs 001-017. Cualquier ajuste que parezca "funcional" (por ejemplo, quitar el buscador embebido del Home) es una reorganización de dónde vive una funcionalidad ya existente, nunca su eliminación — la funcionalidad sigue accesible desde otra ruta/vista ya implementada.

## Principios

- **Cero regresión funcional:** ningún test existente de `tests/Feature/` debe romperse. Si una vista pierde un elemento (ej. el mapa del Home), la función que cumplía ese elemento debe seguir alcanzable desde la misma página mediante un enlace/CTA a la vista que ya la implementa.
- **El design system de `016` es la base, no el techo:** se reutilizan paleta, tipografía Cosmica/DM Sans y radii ya definidos. Esta feature agrega jerarquía visual, composición y personalización — no inventa una paleta nueva.
- **Mobile-first real:** cada sección tocada se verifica en los 3 breakpoints de `memory/constitution.md` §5 (`<768px`, `768–1024px`, `>1024px`), no solo se le agregan clases `sm:`/`lg:` sin probarlas.
- **Filament deja de verse "de fábrica":** tema personalizado (tipografía, densidad, tarjetas de dashboard, login) dentro de lo que Filament v5 permite vía `viteTheme` + `colors()`, sin forkear componentes React/Livewire internos de Filament.
- **Minimalismo:** menos ruido visual, más espacio en blanco, jerarquía tipográfica clara.
- **Animación sutil, no ruido (revisado 2026-07-28, pedido explícito del usuario — reemplaza la exclusión original que heredaba de `016`):** se permiten micro-interacciones ligeras (fade/slide-up al entrar en viewport, hover-lift en tarjetas, un elemento decorativo con movimiento lento) implementadas con CSS/`IntersectionObserver` nativo, sin librerías nuevas, siempre respetando `prefers-reduced-motion`. Nunca animación que bloquee contenido si JS falla, ni transiciones de más de ~600ms.

## Actores

Los mismos cuatro de `016-ui-design-system`: visitante público, usuario marketplace autenticado, usuario sistema en ERP, super admin.

## Criterios de aceptación

### Home (`/`) — landing page persuasiva

- Dado un visitante en `/`, cuando la página carga, entonces **no** contiene el mapa Leaflet ni el formulario de búsqueda embebido (`x-marketplace.map`, `search-experience`) — esos siguen existiendo y funcionando sin cambios en `/talleres/buscar`.
- Dado el Home, entonces tiene múltiples secciones apiladas verticalmente, como mínimo: (1) hero con propuesta de valor y dos CTAs, (2) cómo funciona / propuesta de valor para quien busca un taller, (3) categorías destacadas (ya existente, se conserva y se restyla), (4) estadísticas de confianza (ya existente: talleres publicados, categorías, calificación promedio), (5) sección dedicada a dueños de talleres, (6) sección de cotización ("habla con nuestro equipo") explicando cómo un dueño de taller llega a un contacto humano, (7) preguntas frecuentes, (8) CTA final.
- Dado la sección de cotización, entonces explica el proceso (completar el formulario → revisión → contacto del equipo) y su único CTA es `route('solicitudes.create')` — no se agrega un canal de contacto nuevo (email/teléfono/WhatsApp) porque ninguno existe hoy en el proyecto; si se agrega uno real en el futuro, se documenta explícitamente antes de mostrarlo.
- Dado el hero, entonces incluye un botón "Buscar talleres" que enlaza a `route('talleres.buscar')` y un botón "Registra tu taller" que enlaza a `route('solicitudes.create')` — ambas rutas ya existentes, sin nuevas rutas.
- Dado la sección para dueños de taller, entonces comunica de forma persuasiva (título, 3-4 beneficios concretos: gestión de clientes y vehículos, control de inventario, órdenes de trabajo, notificaciones) que el sistema ofrece un ERP para administrar su taller, y tiene su propio CTA hacia `route('solicitudes.create')`.
- Dado el Home en móvil (`<768px`), entonces todas las secciones son de una sola columna, sin overflow horizontal, y los CTAs son de ancho completo o centrados (no recortados).
- Dado el Home en escritorio (`>1024px`), entonces el hero y las secciones usan el ancho disponible con composición de múltiples columnas donde el contenido lo permita (ej. beneficios en grid, no todo en una sola columna larga).
- Dado el Home, entonces sigue usando `marketplace.layouts.app` y los componentes de `016` (`x-button`, `x-badge`, `x-marketplace.stats-block`), sin introducir un layout paralelo.
- Dado el footer (`components/marketplace/footer.blade.php`), entonces está organizado en columnas (marca/descripción, navegación del marketplace, enlaces para dueños de taller) en vez de una sola fila de enlaces, mantiene los mismos destinos (`home`, `talleres.buscar`, `solicitudes.create`) y colapsa a una columna en móvil sin overflow.

### Resto de vistas del marketplace

- Dado `/talleres/buscar` (búsqueda), `/talleres/{slug}` (perfil público), `/dashboard` (autenticado), `nav`/`footer`, cuando se revisan, entonces mantienen exactamente la misma funcionalidad (filtros, mapa, favoritos, reseñas, notificaciones) y solo cambian en composición visual, espaciado y responsividad.
- Dado cualquier vista del marketplace en `768–1024px` (tablet), entonces no hay elementos que se corten, se superpongan o requieran scroll horizontal no intencional.

### Filament — ERP (`/erp`) y Super Admin (`/admin`)

- Dado un panel Filament, cuando carga, entonces el tema (`resources/css/filament/{erp,admin}/theme.css`) incluye personalización propia más allá de la paleta de colores ya mapeada en `017-infraestructura-sistema` (tipografía consistente con el marketplace, densidad de espaciado, estilo de tarjetas del dashboard) — no es el tema por defecto de Filament sin tocar.
- Dado la página de login de ambos paneles, entonces tiene una composición visual propia (no el formulario centrado genérico de Filament sin personalizar), coherente con la identidad TallerPro.
- Dado cualquier Resource existente (tablas, formularios, modales), entonces conserva el 100% de sus acciones, columnas, filtros y permisos actuales — el cambio es solo de tema/densidad/tipografía, nunca de estructura de datos o de campos mostrados/ocultos.
- Dado el ERP o Super Admin en móvil (`<768px`), entonces usa el modo colapsado nativo de Filament (sidebar oculto, tablas con scroll horizontal) ya exigido por `memory/constitution.md` §5, verificado en al menos los Resources más usados (Dashboard, un listado, un formulario de creación).

### Formularios y componentes compartidos

- Dado los componentes `<x-input>`, `<x-select>`, `<x-button>`, `<x-card>` del marketplace, cuando se revisan, entonces mantienen sus mismas props/API (no rompen ningún uso existente en el resto de vistas) pero su composición visual deja de verse genérica (foco visible, agrupación label+input+error consistente, estados hover/focus/disabled definidos).
- Dado cualquier formulario existente (login, registro de solicitud de taller, edición de perfil, etc.), cuando se usa en móvil, entonces los campos ocupan el ancho disponible y los botones de acción no quedan cortados ni fuera de la vista sin scroll.

## Fuera de alcance (MVP de esta feature)

- Cualquier cambio de lógica de negocio, validaciones de servidor, permisos, rutas nuevas o modelos — si una vista necesita datos que su controlador no provee hoy, se documenta como pendiente y no se implementa en esta feature.
- Modo oscuro, i18n, SEO — mismos ítems ya fuera de alcance en `016-ui-design-system`.
- Animación **compleja** (librerías de animación, animaciones encadenadas/orquestadas, parallax): la animación permitida es la sutil descrita en "Principios" arriba (revisado 2026-07-28).
- Canales de contacto reales (email/teléfono/WhatsApp del negocio): no existen en el proyecto hoy: cualquier sección de "contacto"/"cotización" del Home usa `route('solicitudes.create')` como único mecanismo, nunca datos de contacto inventados.
- Testimonios/reseñas destacadas en el Home con datos reales: si se agrega una sección de prueba social más allá de las estadísticas agregadas ya existentes, se hace con los datos que el `HomeController` ya expone; no se crean nuevas consultas agregadas salvo que se documenten explícitamente en `plan.md`.
- Rediseño de la lógica del mapa Leaflet o del buscador Alpine (`search-experience`): se reubican, no se reescriben.
