---
id: 016-ui-design-system
status: implemented
depends_on: [001-identidad-autenticacion, 003-gestion-talleres]
resumen: "Design system único con dos caras: marketplace editorial (Awesomic zinc-gray + ember) y Filament thematic (mismos colores, componentes nativos). Componentes Blade compartidos, layouts, páginas del sistema y consistencia cross-guard."
---

# UI — Design System

## Propósito

Unificar la apariencia de toda la plataforma bajo un mismo lenguaje visual sin forzar a Filament a adoptar una geometría que no le es natural. El sistema tiene **dos caras** que comparten la misma paleta y tipografía pero respetan la naturaleza de cada tecnología:

- **Marketplace (Blade + Tailwind + Alpine):** diseño editorial Awesomic — zinc-gray, tarjetas 36px, bordes 1px, tipografía Cosmica/DM Sans bold, acento ember solo en badges.
- **ERP y Super Admin (Filament v3):** componentes nativos de Filament con la paleta Awesomic mapeada a sus colores de estado. Sin forzar radii ni estructuras ajenas.

## Principios

- **Neutral-first:** la interfaz es 99% acromática (zinc-gray). El color solo aparece como puntuación funcional.
- **Geometría generosa:** ningún contenedor tiene radius 0. Los componentes Blade usan 36px (cards), 14px (botones/inputs), 12px (badges). Filament conserva sus radii nativos pero sin sharp corners.
- **Sin sombras en tarjetas:** la elevación se logra con bordes de 1px `#ececee` en lugar de drop shadows.
- **Tipografía única:** Cosmica (DM Sans como fallback) para todo: desde badges de 10px hasta displays de 64px.
- **Responsive mobile-first:** marketplace colapsa a una columna en `<768px`; ERP usa el responsive nativo de Filament.
- **Server-rendered + Alpine:** el marketplace no usa Livewire. La interactividad se maneja con Alpine stores y `x-data`.

## Actores

- Visitante público (home, búsqueda, perfil público).
- Usuario marketplace autenticado (dashboard, favoritos, reseñas).
- Usuario sistema en ERP (todos los recursos Filament).
- Super admin en panel global (todos los recursos Filament).

## Criterios de aceptación

### Paleta compartida entre marketplace y Filament

- Los 13 colores Awesomic (`obsidian` a `magenta-spark`) están definidos como custom properties de CSS y como tokens `@theme` de Tailwind v4.
- Los colores son accesibles desde cualquier contexto: clases Tailwind en Blade y `Filament\Panel::colors()` en los paneles.
- `ember` (`#ff5a00`) se usa únicamente como badge de acento en el marketplace y como `danger`/`warning` en Filament (alertas de stock crítico, anulación, errores). Nunca como color de link, botón general o texto.

### Marketplace (Blade + Tailwind + Alpine)

- Dado cualquier página del marketplace, cuando se renderiza, entonces usa el layout `marketplace/layouts/app` (o `guest`/`auth` según corresponda), no un layout genérico.
- Dado el layout `app`, entonces contiene: nav sticky (logo + links + login/CTA), slot de contenido principal y footer.
- Dado el layout `guest`, entonces no hay nav ni footer — solo contenido centrado (login OAuth, 404).
- Dado un componente `<x-workshop-card>`, cuando se renderiza, entonces tiene border-radius 36px, borde 1px `#ececee`, padding 28px, sin sombra.
- Dado un botón primario (`<x-button variant="primary">`), entonces tiene fondo `#09090b`, texto blanco, border-radius 14px, padding 12px/16px.
- Dado un badge de estado (`<x-badge type="success">`), entonces tiene background transparente, borde 1px, border-radius 12px, padding 4px/8px, texto 12-13px.
- Dado un badge de acento (`<x-badge type="accent">`), entonces tiene fondo `#ff5a00`, texto blanco, border-radius 12px, padding 4px/8px.
- Dado el home, cuando carga, entonces usa el layout `app`, contiene hero (headline 64px + email input + CTA oscuro), banda de categorías (cards 36px), sección de resultados/mapa, stats row, breakthrough image.
- Dado el home en móvil `<768px`, entonces el mapa se oculta y la lista de talleres ocupa el ancho completo.
- Dado el home en escritorio `>1024px`, entonces la búsqueda muestra panel izquierdo (filtros + lista) y panel derecho (mapa Leaflet).

### ERP y Super Admin (Filament v3)

- Dado un panel Filament (`/erp` o `/admin`), cuando carga, entonces usa la paleta Awesomic mapeada a los colores de Filament:
  - `primary` → `obsidian` (`#09090b`)
  - `secondary` → `graphite` (`#18181b`)
  - `gray` → escala zinc (`paper` a `obsidian`)
  - `danger` → `ember` (`#ff5a00`)
  - `warning` → `ember` con opacidad
  - `success` → verde estándar de Filament (el sistema Awesomic no define verde; se usa el default de Filament)
  - `info` → `steel` (`#52525b`)
- Dado un panel Filament, cuando se renderiza, entonces los componentes (botones, inputs, tabs, tablas) mantienen su geometría nativa de Filament — no se sobreescriben radii, paddings ni estructuras.
- Dado un badge de estado en Filament (`->badge()->color(...)`), cuando se renderiza, entonces sigue el mapa semántico de `memory/constitution.md` §5 (verde=éxito, azul=en_proceso, amarillo=pendiente, rojo=error/anulado, gris=inactivo). El color `ember` se usa para `danger` y estados críticos.
- Dado el sidebar del ERP, cuando un usuario no tiene permiso `modulo.ver` para un módulo, entonces ese ítem no aparece.
- Dado un acceso denegado en Filament, entonces se muestra la pantalla 403 custom con botón "Volver al dashboard".

### Componentes Blade compartidos (marketplace)

- Dado el directorio `resources/views/components/`, cuando existe un componente Blade, entonces sigue el catálogo definido en `plan.md` con props tipadas y variantes.
- Dado cualquier formulario en el marketplace, cuando se renderiza, entonces incluye `@csrf` y muestra errores de validación con `@error`.
- Dado un estado vacío (sin resultados de búsqueda, sin favoritos), entonces se renderiza `<x-empty-state>` con icono, título, descripción y acción opcional.

### Páginas del sistema

El sistema reconoce tres grupos de rutas, cada uno con su propio layout y comportamientos:

| Grupo | Guard | Layout base | Tecnología |
|---|---|---|---|
| Marketplace público | `web` (guest) | `marketplace/layouts/guest` | Blade + Tailwind |
| Marketplace autenticado | `web` (auth) | `marketplace/layouts/auth` | Blade + Tailwind + Alpine |
| ERP (taller) | `sistema` | Filament panel `/erp` | Filament v3 |
| Super Admin | `sistema` (rol SUPER_ADMIN) | Filament panel `/admin` | Filament v3 |

- Dado un visitante sin sesión, cuando accede a `/`, entonces ve el home con layout `guest` y componentes Awesomic.
- Dado un usuario sistema autenticado, cuando accede a `/erp`, entonces ve el panel Filament con la paleta mapeada.
- Dado el home (`/`), es la única página que NO usa Filament ni requiere sesión — es puro Blade con el diseño editorial Awesomic completo.
- Dado cualquier otra página del marketplace (búsqueda, perfil, dashboard), usan el mismo design system marketplace (Blade + componentes Awesomic), no Filament.

### Consistencia cross-guard

- Un usuario puede navegar entre marketplace y ERP, pero nunca en la misma sesión (guards separados). La UI de cada uno es visualmente distinta pero reconocible como parte del mismo sistema por la paleta de colores y la tipografía compartidas.
- Los badges de estado usan la misma semántica de color en ambos entornos (verde=ok, rojo=error, etc.), aunque la implementación técnica difiera (Blade component vs Filament `->badge()->color()`).
- La tipografía Cosmica/DM Sans es la misma en toda la plataforma, incluyendo Filament (vía `customTheme`).

## Fuera de alcance (MVP)

- Modo oscuro en el marketplace (Filament lo trae nativo y se deja activado).
- Traducciones / i18n.
- SEO (meta tags, Open Graph, sitemap).
- Componentes de pago animados (transiciones, micro-interacciones complejas).
- Storybook o catálogo visual de componentes fuera del código.
