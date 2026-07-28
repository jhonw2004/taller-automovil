# Tasks — UI Design System

## Tokens y configuración base
- [x] Definir tokens `@theme` en `resources/css/app.css` (colores, tipografía, spacing, radii, shadows).
- [x] Instalar fuente DM Sans vía Vite (`@fontsource/dm-sans` o Bunny Fonts, ya configurado en `vite.config.js`).
- [x] Crear `resources/css/filament/admin/theme.css` con paleta Awesomic mapeada a colores de Filament.
- [x] Crear `resources/css/filament/erp/theme.css` (misma paleta que admin).
- [x] Registrar ambos temas CSS en `config/filament.php` (`panels.admin.theme`, `panels.erp.theme`).
- [x] Configurar `Filament\Panel::colors()` con el mapeo Awesomic (primary → obsidian, danger → ember, etc.).

## Layouts del marketplace (Blade)
- [x] Crear `resources/views/marketplace/layouts/app.blade.php` (nav sticky + main slot + footer).
- [x] Crear `resources/views/marketplace/layouts/guest.blade.php` (centrado vertical, sin nav/footer).
- [x] Crear `resources/views/marketplace/layouts/auth.blade.php` (igual que app + avatar + menú usuario).

## Componentes Blade globales (`resources/views/components/`)
- [x] `badge.blade.php` — variantes outlined/filled, tipos success/warning/danger/info/neutral.
- [x] `button.blade.php` — variantes primary/ghost/neutral, soporte disabled + loading (spinner).
- [x] `card.blade.php` — padding variable, border toggle, slot para contenido.
- [x] `input.blade.php` — label + input + `@error` mensaje + Alpine `x-model` soporte.
- [x] `select.blade.php` — label + select + `@error`.
- [x] `empty-state.blade.php` — icono SVG inline + título + descripción + acción opcional.
- [x] `alert.blade.php` — tipos success/error/warning/info, dismissible.
- [x] `modal.blade.php` — Alpine `x-show` + backdrop + slot.
- [x] `pagination.blade.php` — wrapper de `$paginator->links()` con estilo Awesomic.

## Componentes Blade del marketplace (`resources/views/components/marketplace/`)
- [x] `nav.blade.php` — sticky, logo + links + login/CTA.
- [x] `footer.blade.php`.
- [x] `workshop-card.blade.php` — card 36px radius, 1px cloud border, 28px padding.
- [x] `search-filters.blade.php` — panel lateral de filtros con Alpine.
- [x] `map.blade.php` — wrapper Leaflet con Alpine `x-data="map"`.
- [x] `star-rating.blade.php` — SVG estrellas interactivas/estáticas.
- [x] `review-card.blade.php` — avatar + nombre + rating + fecha + texto.
- [x] `stats-block.blade.php` — número grande + label.

## Alpine stores y data
- [x] Crear `resources/js/alpine/store.js` — `Alpine.store('search', ...)`, `Alpine.store('ui', ...)`.
- [x] Crear `resources/js/alpine/components/map.js` — `Alpine.data('map', ...)` con Leaflet.
- [x] Crear `resources/js/alpine/components/search.js` — `Alpine.data('searchForm', ...)`.
- [x] Crear `resources/js/alpine/components/rating.js` — `Alpine.data('starRating', ...)`.
- [x] Importar stores y data components en `resources/js/app.js`.

## Página Home (marketplace)
- [x] Crear `resources/views/marketplace/home.blade.php`.
- [x] Hero: headline 64px Cosmica weight 600 #09090b + párrafo 15px #52525b + email input + CTA oscuro.
- [x] Banda de categorías: cards 36px radius con imagen + título + badges.
- [x] Sección de resultados de búsqueda (o placeholder "busca tu taller").
- [x] Mapa Leaflet integrado con Alpine store.
- [x] Stats row: 3 bloques número + etiqueta.
- [x] Sección de "Talleres cerca de ti" con `<x-workshop-card>`.
- [x] Breakthrough image full-bleed (separador visual).
- [x] Responsive: `<768px` sin mapa, lista ocupa ancho completo.
- [x] Responsive: `>1024px` split filters+lista (35%) | mapa (65%).

## Página de búsqueda (marketplace)
- [x] Crear `resources/views/marketplace/search/index.blade.php`.
- [x] Reutiliza `<x-marketplace.search-filters>`, `<x-marketplace.map>`, `<x-workshop-card>`.
- [x] Integración con `Alpine.store('search')` para fetch de resultados vía API.

## Página de perfil público del taller
- [x] Crear `resources/views/marketplace/workshops/show.blade.php`.
- [x] Datos del taller + mapa + horarios + reseñas + botón de favorito.

## Vistas del dashboard del usuario marketplace
- [x] Crear `resources/views/marketplace/dashboard/index.blade.php`.
- [x] Lista de favoritos + lista de reseñas del usuario.

## Badge consistency (Blade + Filament)
- [x] Verificar que los colores de badge en componentes Blade coinciden semánticamente con `->badge()->color()` en Filament (tabla de mapeo en `plan.md`).

## Tests de UI
- [x] Cada componente Blade se renderiza sin errores con sus props mínimas.
- [x] Home page carga con layout `guest`, contiene hero, mapa, categorías.
- [x] Badge success muestra verde, badge danger muestra ember/rojo.
- [x] Botón primary tiene clase `bg-obsidian`.
- [x] Card tiene borde `border-cloud` y `rounded-[--radius-cards]`.
- [x] Filament panels (`/admin`, `/erp`) cargan con colores Awesomic sin errores de tema.
- [x] Sidebar del ERP solo muestra módulos con permiso.
