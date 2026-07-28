# Tareas — 018-modernizacion-ui

## Home (`/`) — landing page persuasiva

- [x] Revisar tests existentes que toquen `marketplace.home` o la ruta `/` antes de reescribir la vista (evitar romper aserciones sobre el HTML actual). `tests/Feature/Marketplace/PaginasPublicasTest.php` exige `assertSee('Encuentra el taller mecánico ideal')`, el nombre de una categoría y `'Talleres publicados'` — los tres se conservan literalmente en la vista nueva.
- [x] Reescribir `resources/views/marketplace/home.blade.php`: quitar mapa/buscador embebido, agregar secciones (hero, cómo funciona, categorías, estadísticas, trust band, para dueños de taller, CTA final).
- [x] Verificar responsividad en `<768px`, `768–1024px`, `>1024px` (sin overflow horizontal, CTAs usables) — clases mobile-first (`base` → `sm:`/`md:`/`lg:`), sin la inversión de padding que tenía el Home anterior (`px-16 sm:px-4`).
- [x] Confirmar que `route('talleres.buscar')` y `route('solicitudes.create')` siguen siendo los únicos destinos de los CTAs (sin rutas nuevas).
- [x] Correr suite completa de Pest + `npm run build` + smoke test con `curl`.

**Nota de implementación (no listada originalmente en `plan.md`):** `components/marketplace/stats-block.blade.php` no mergeaba `$attributes` en su elemento raíz, a diferencia del resto de componentes compartidos (`card`, `button`) — se corrigió agregando `$attributes->merge(...)`, cambio aditivo que no altera ningún uso existente (nadie pasaba `class` antes). Se descartó poner las estadísticas dentro de la banda oscura de confianza porque el componente usa `text-obsidian`/`text-fog` fijos (ilegible sobre `bg-graphite`); en vez de forzar colores vía props, se mantuvo la sección de estadísticas en fondo claro (mismo criterio de la vista original) y la banda oscura de confianza quedó como texto puro, sin números.

## Resto de vistas marketplace

- [ ] Auditar y ajustar `search/index.blade.php` en los 3 breakpoints.
- [ ] Auditar y ajustar `workshops/show.blade.php` en los 3 breakpoints.
- [ ] Auditar y ajustar `dashboard/index.blade.php` en los 3 breakpoints.
- [ ] Auditar y ajustar `components/marketplace/{nav,footer}.blade.php` en los 3 breakpoints.

## Filament — ERP y Super Admin

- [ ] Verificar con Context7 el mecanismo soportado por Filament v5 para CSS custom adicional dentro de `viteTheme` y para personalizar la página de login.
- [ ] Personalizar `resources/css/filament/erp/theme.css` y `resources/css/filament/admin/theme.css` (tipografía, densidad, tarjetas de dashboard).
- [ ] Personalizar la composición visual de la página de login de ambos paneles.
- [ ] Verificar que ningún Resource pierde columnas/acciones/filtros tras el cambio de tema (correr suite de Filament existente).
- [ ] Verificar modo colapsado en móvil (`<768px`) en Dashboard + un listado + un formulario de creación por panel.

## Formularios y componentes compartidos

- [ ] Refinar `components/{input,select,button,card}.blade.php` (estados focus/hover/disabled, agrupación label+input+error) sin cambiar `@props`.
- [ ] Revisar en móvil los formularios que más se usan (login, solicitud de alta de taller, edición de perfil de usuario marketplace).

## Cierre de feature

- [ ] Suite completa de Pest en verde.
- [ ] `vendor/bin/pint --dirty` sin pendientes.
- [ ] Actualizar `status` de `specs/018-modernizacion-ui/spec.md` a `implemented` solo cuando **todos** los bloques de arriba estén marcados y verificados (esta feature se implementa en varias sesiones; no marcar `implemented` con bloques pendientes).
