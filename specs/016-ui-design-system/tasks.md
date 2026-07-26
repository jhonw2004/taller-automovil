# Tasks — UI Design System

## Tokens y configuración base
- [ ] Definir tokens `@theme` en `resources/css/app.css` (colores, tipografía, spacing, radii, shadows).
- [ ] Instalar fuente DM Sans vía Vite (`@fontsource/dm-sans` o Bunny Fonts, ya configurado en `vite.config.js`).
- [ ] Crear `resources/css/filament/admin/theme.css` con paleta Awesomic mapeada a colores de Filament.
- [ ] Crear `resources/css/filament/erp/theme.css` (misma paleta que admin).
- [ ] Registrar ambos temas CSS en `config/filament.php` (`panels.admin.theme`, `panels.erp.theme`).
- [ ] Configurar `Filament\Panel::colors()` con el mapeo Awesomic (primary → obsidian, danger → ember, etc.).

## Layouts del marketplace (Blade)
- [ ] Crear `resources/views/marketplace/layouts/app.blade.php` (nav sticky + main slot + footer).
- [ ] Crear `resources/views/marketplace/layouts/guest.blade.php` (centrado vertical, sin nav/footer).
- [ ] Crear `resources/views/marketplace/layouts/auth.blade.php` (igual que app + avatar + menú usuario).

## Componentes Blade globales (`resources/views/components/`)
- [ ] `badge.blade.php` — variantes outlined/filled, tipos success/warning/danger/info/neutral.
- [ ] `button.blade.php` — variantes primary/ghost/neutral, soporte disabled + loading (spinner).
- [ ] `card.blade.php` — padding variable, border toggle, slot para contenido.
- [ ] `input.blade.php` — label + input + `@error` mensaje + Alpine `x-model` soporte.
- [ ] `select.blade.php` — label + select + `@error`.
- [ ] `empty-state.blade.php` — icono SVG inline + título + descripción + acción opcional.
- [ ] `alert.blade.php` — tipos success/error/warning/info, dismissible.
- [ ] `modal.blade.php` — Alpine `x-show` + backdrop + slot.
- [ ] `pagination.blade.php` — wrapper de `$paginator->links()` con estilo Awesomic.

## Componentes Blade del marketplace (`resources/views/components/marketplace/`)
- [ ] `nav.blade.php` — sticky, logo + links + login/CTA.
- [ ] `footer.blade.php`.
- [ ] `workshop-card.blade.php` — card 36px radius, 1px cloud border, 28px padding.
- [ ] `search-filters.blade.php` — panel lateral de filtros con Alpine.
- [ ] `map.blade.php` — wrapper Leaflet con Alpine `x-data="map"`.
- [ ] `star-rating.blade.php` — SVG estrellas interactivas/estáticas.
- [ ] `review-card.blade.php` — avatar + nombre + rating + fecha + texto.
- [ ] `stats-block.blade.php` — número grande + label.

## Alpine stores y data
- [ ] Crear `resources/js/alpine/store.js` — `Alpine.store('search', ...)`, `Alpine.store('ui', ...)`.
- [ ] Crear `resources/js/alpine/components/map.js` — `Alpine.data('map', ...)` con Leaflet.
- [ ] Crear `resources/js/alpine/components/search.js` — `Alpine.data('searchForm', ...)`.
- [ ] Crear `resources/js/alpine/components/rating.js` — `Alpine.data('starRating', ...)`.
- [ ] Importar stores y data components en `resources/js/app.js`.

## Página Home (marketplace)
- [ ] Crear `resources/views/marketplace/home.blade.php`.
- [ ] Hero: headline 64px Cosmica weight 600 #09090b + párrafo 15px #52525b + email input + CTA oscuro.
- [ ] Banda de categorías: cards 36px radius con imagen + título + badges.
- [ ] Sección de resultados de búsqueda (o placeholder "busca tu taller").
- [ ] Mapa Leaflet integrado con Alpine store.
- [ ] Stats row: 3 bloques número + etiqueta.
- [ ] Sección de "Talleres cerca de ti" con `<x-workshop-card>`.
- [ ] Breakthrough image full-bleed (separador visual).
- [ ] Responsive: `<768px` sin mapa, lista ocupa ancho completo.
- [ ] Responsive: `>1024px` split filters+lista (35%) | mapa (65%).

## Página de búsqueda (marketplace)
- [ ] Crear `resources/views/marketplace/search/index.blade.php`.
- [ ] Reutiliza `<x-marketplace.search-filters>`, `<x-marketplace.map>`, `<x-workshop-card>`.
- [ ] Integración con `Alpine.store('search')` para fetch de resultados vía API.

## Página de perfil público del taller
- [ ] Crear `resources/views/marketplace/workshops/show.blade.php`.
- [ ] Datos del taller + mapa + horarios + reseñas + botón de favorito.

## Vistas del dashboard del usuario marketplace
- [ ] Crear `resources/views/marketplace/dashboard/index.blade.php`.
- [ ] Lista de favoritos + lista de reseñas del usuario.

## Badge consistency (Blade + Filament)
- [ ] Verificar que los colores de badge en componentes Blade coinciden semánticamente con `->badge()->color()` en Filament (tabla de mapeo en `plan.md`).

## Tests de UI
- [ ] Cada componente Blade se renderiza sin errores con sus props mínimas.
- [ ] Home page carga con layout `guest`, contiene hero, mapa, categorías.
- [ ] Badge success muestra verde, badge danger muestra ember/rojo.
- [ ] Botón primary tiene clase `bg-obsidian`.
- [ ] Card tiene borde `border-cloud` y `rounded-[--radius-cards]`.
- [ ] Filament panels (`/admin`, `/erp`) cargan con colores Awesomic sin errores de tema.
- [ ] Sidebar del ERP solo muestra módulos con permiso.
