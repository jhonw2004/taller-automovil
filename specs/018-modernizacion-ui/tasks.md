# Tareas — 018-modernizacion-ui

## Home (`/`) — landing page persuasiva

- [x] Revisar tests existentes que toquen `marketplace.home` o la ruta `/` antes de reescribir la vista (evitar romper aserciones sobre el HTML actual). `tests/Feature/Marketplace/PaginasPublicasTest.php` exige `assertSee('Encuentra el taller mecánico ideal')`, el nombre de una categoría y `'Talleres publicados'` — los tres se conservan literalmente en la vista nueva.
- [x] Reescribir `resources/views/marketplace/home.blade.php`: quitar mapa/buscador embebido, agregar secciones (hero, cómo funciona, categorías, estadísticas, trust band, para dueños de taller, CTA final).
- [x] Verificar responsividad en `<768px`, `768–1024px`, `>1024px` (sin overflow horizontal, CTAs usables) — clases mobile-first (`base` → `sm:`/`md:`/`lg:`), sin la inversión de padding que tenía el Home anterior (`px-16 sm:px-4`).
- [x] Confirmar que `route('talleres.buscar')` y `route('solicitudes.create')` siguen siendo los únicos destinos de los CTAs (sin rutas nuevas).
- [x] Correr suite completa de Pest + `npm run build` + smoke test con `curl`.

**Nota de implementación (no listada originalmente en `plan.md`):** `components/marketplace/stats-block.blade.php` no mergeaba `$attributes` en su elemento raíz, a diferencia del resto de componentes compartidos (`card`, `button`) — se corrigió agregando `$attributes->merge(...)`, cambio aditivo que no altera ningún uso existente (nadie pasaba `class` antes). Se descartó poner las estadísticas dentro de la banda oscura de confianza porque el componente usa `text-obsidian`/`text-fog` fijos (ilegible sobre `bg-graphite`); en vez de forzar colores vía props, se mantuvo la sección de estadísticas en fondo claro (mismo criterio de la vista original) y la banda oscura de confianza quedó como texto puro, sin números.

## Home — ampliación 2026-07-28 (pedido explícito del usuario)

- [x] Agregar sección de cotización ("habla con nuestro equipo"): 3 pasos + CTA único a `solicitudes.create` (sin datos de contacto inventados, ver `spec.md`/`plan.md`).
- [x] Agregar sección de preguntas frecuentes (`<details>/<summary>` nativo, 4 preguntas basadas en comportamiento real).
- [x] Animación sutil: `resources/js/reveal.js` (scroll-reveal vía `IntersectionObserver`) + `.reveal`/`.animate-float-slow` en `app.css`, aplicada a las secciones del Home y al blob decorativo del hero; hover-lift en tarjetas. Respeta `prefers-reduced-motion`.
- [x] Correr suite completa de Pest + `npm run build` + smoke test tras los cambios.

## Footer — rediseño 2026-07-28

- [x] Rediseñar `components/marketplace/footer.blade.php` en columnas (marca/descripción, Marketplace, Para tu taller), responsive, y corregir el padding invertido (`px-16 sm:px-4` → progresión mobile-first correcta) en ese archivo.
- [x] `nav.blade.php` — mismo bug de padding invertido corregido (2026-07-29): `px-16 sm:px-24 lg:px-16`, mismo patrón que `footer.blade.php`/`home.blade.php`. Se encontró y corrigió el mismo bug además en `workshops/show.blade.php`, `dashboard/index.blade.php`, `layouts/app.blade.php` (banners de sesión) y `layouts/auth.blade.php` (banners + accesos de usuario autenticado) — confirmado con grep que no queda ningún `sm:px-4` en `resources/views/`.

## Formularios — hallazgo y migración 2026-07-28

- [x] `resources/views/solicitudes/{crear,seguimiento}.blade.php` usaban un layout legado (`layouts/marketplace.blade.php`, sin nav/footer/design system) — migradas a `marketplace.layouts.app` + `<x-card>`/`<x-input>`/`<x-select>`/`<x-button>`/`<x-alert>`/`<x-badge>`, mismos `id`/`name`/rutas (JS del stepper y tests de `SolicitudPublicaTest.php` verificados sin cambios de comportamiento).
- [x] Eliminado `resources/views/layouts/marketplace.blade.php` (sin consumidores tras la migración, verificado con grep).

## Resto de vistas marketplace

- [x] ~~Auditar y ajustar `search/index.blade.php` en los 3 breakpoints~~ — absorbido y resuelto por `019-mapa-busqueda-ux` (rediseño completo de esa vista, no solo auditoría de breakpoints), spec marcada `implemented` 2026-07-29.
- [x] Auditar y ajustar `workshops/show.blade.php` en los 3 breakpoints (2026-07-29): ya usaba grid/flex mobile-first correcto (`flex-col lg:flex-row`, `sm:grid-cols-2`); se corrigió el bug de padding invertido del contenedor (`sm:px-4` → `sm:px-24 lg:px-16`).
- [x] Auditar y ajustar `dashboard/index.blade.php` en los 3 breakpoints (2026-07-29): grids ya usaban progresión mobile-first correcta (`sm:grid-cols-2 lg:grid-cols-3`); mismo fix de padding invertido que arriba.
- [x] Auditar y ajustar `components/marketplace/nav.blade.php` en los 3 breakpoints (2026-07-29): bug de padding invertido corregido, ver bloque "Footer" arriba.

## Filament — ERP y Super Admin

- [x] Eliminar la marca propia de Filament de la UI (2026-07-29): `FilamentInfoWidget` (widget de dashboard con el logo/versión de Filament y enlaces a filamentphp.com/GitHub) quitado de `widgets()` en `AdminPanelProvider` y `ErpPanelProvider`. `brandName`/`brandLogo`/`favicon` ya apuntaban a "TallerPro" desde la vigesimosegunda sesión — no había otro punto de la UI (login, vistas, tema) que mostrara el logo/nombre de Filament, verificado por grep contra las vistas publicadas del vendor.
- [x] Verificado con Context7 (`/websites/filamentphp_5_x`, 2026-07-29): el mecanismo soportado es CSS hooks (clases `.fi-*` documentadas en `docs/5.x/styling/css-hooks`, ej. `.fi-btn`, `.fi-sidebar`) con `@apply` dentro del propio `theme.css` — no existe un mecanismo alternativo de "CSS custom adicional". La página de login usa el layout `fi-simple-layout`/`fi-simple-main-ctn`/`fi-simple-main` (confirmado leyendo `vendor/filament/filament/resources/views/components/layout/simple.blade.php`), y el heading/subheading son overrideables vía `getHeading()`/`getSubheading()` en la Page.
- [x] Personalizado `resources/css/filament/erp/theme.css` y `resources/css/filament/admin/theme.css`: radios de `.fi-btn`/`.fi-input`/`.fi-select-input`/`.fi-fo-textarea` (14px, token `--radius-inputs`/`--radius-buttons` de `016`), `.fi-section`/`.fi-modal-window`/`.fi-wi-stats-overview-stat` (20px) y `.fi-badge` (12px) alineados a los radios del design system; página de login (`.fi-simple-layout`) con fondo oscuro consistente con el hero del marketplace y card (`.fi-simple-main`) con sombra y radio.
- [x] Personalizada la composición visual de la página de login de ambos paneles: `App\Filament\Auth\Pages\Login` ahora sobreescribe `getHeading()`/`getSubheading()` diferenciando por panel activo (`Filament::getCurrentPanel()->getId()`) — ERP: "Panel de tu taller"/"Ingresa con tu usuario del sistema para gestionar tu taller."; Admin: "Super administración"/"Acceso exclusivo para el equipo de TallerPro." Verificado con `php artisan serve` + `curl`: ambos textos aparecen en `/erp/login` y `/admin/login` respectivamente.
- [x] Verificado que ningún Resource pierde columnas/acciones/filtros tras el cambio de tema: suite completa de Pest en verde tras los cambios (los cambios son solo CSS + heading/subheading de la página de Login, sin tocar ningún Resource/Action).
- [x] Modo colapsado en móvil (`<768px`): comportamiento nativo de Filament v5 (sidebar colapsable/topbar responsive), no se tocó ningún componente de layout de Filament que pudiera romperlo — sin verificación visual real en navegador (misma limitación estructural de siempre).

## Formularios y componentes compartidos

- [x] Refinados `components/{input,select,button,card}.blade.php` (2026-07-29) sin cambiar `@props`: `input`/`select` ganaron `transition`, `hover:border-fog` (estado sin error), `disabled:cursor-not-allowed disabled:bg-paper disabled:text-fog`, y `aria-invalid`/`aria-describedby` enlazando el mensaje de error (`id="{name}-error"`) para accesibilidad; `button` ganó `focus-visible:ring-2` (antes no tenía foco visible por teclado) y estados `active:` por variante. `card.blade.php` no requería cambios (contenedor estático, ya mergea `$attributes` desde `018` sesión anterior). También alineado el `<textarea>` de `resena-form.blade.php` (no usa `<x-input>` porque necesita `x-model` de Alpine) con `transition hover:border-fog` para consistencia visual.
- [x] Revisados en móvil los formularios más usados: login (Filament, ver arriba), solicitud de alta de taller (`solicitudes/crear.blade.php`, ya migrada a `<x-input>`/`<x-select>` en sesión anterior — hereda los refinamientos automáticamente), edición de perfil de usuario marketplace (`dashboard/index.blade.php`, grids `sm:grid-cols-2 lg:grid-cols-3` ya correctos).

## Cierre de feature

- [x] Suite completa de Pest en verde (568/568, verificado 2026-07-29 tras todos los cambios de esta sesión).
- [x] `vendor/bin/pint --dirty` sin pendientes.
- [x] `npm run build` sin errores (ambos temas Filament + `app.css`/`app.js` compilan).
- [x] `status` de `specs/018-modernizacion-ui/spec.md` actualizado a `implemented` (2026-07-29) — todos los bloques marcados y verificados; la única limitación es la verificación visual real en navegador, no disponible en este entorno (mismo criterio que el resto de features `implemented` del proyecto).
