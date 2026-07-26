# Plan — UI Design System

## Tokens compartidos (Tailwind v4 + CSS)

### `resources/css/app.css`

```css
@import 'tailwindcss';

@theme {
  --color-obsidian: #09090b;
  --color-graphite: #18181b;
  --color-slate: #27272a;
  --color-iron: #3f3f46;
  --color-steel: #52525b;
  --color-fog: #71717a;
  --color-ash: #a1a1aa;
  --color-mist: #d4d4d8;
  --color-cloud: #ececee;
  --color-paper: #f4f4f5;
  --color-snow: #ffffff;
  --color-ember: #ff5a00;
  --color-magenta-spark: #fe45e2;

  --font-cosmica: 'DM Sans', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;

  --text-caption: 12px;
  --leading-caption: 1.64;
  --text-body: 15px;
  --leading-body: 1.45;
  --text-body-lg: 18px;
  --leading-body-lg: 1.45;
  --text-subheading: 20px;
  --leading-subheading: 1.5;
  --text-heading-sm: 32px;
  --leading-heading-sm: 1.5;
  --text-heading: 40px;
  --leading-heading: 1.28;
  --text-heading-lg: 56px;
  --leading-heading-lg: 1.28;
  --text-display: 64px;
  --leading-display: 1.12;

  --spacing-4: 4px;
  --spacing-8: 8px;
  --spacing-12: 12px;
  --spacing-16: 16px;
  --spacing-20: 20px;
  --spacing-24: 24px;
  --spacing-28: 28px;
  --spacing-32: 32px;
  --spacing-36: 36px;
  --spacing-40: 40px;
  --spacing-48: 48px;
  --spacing-64: 64px;
  --spacing-68: 68px;
  --spacing-80: 80px;
  --spacing-120: 120px;

  --radius-cards: 36px;
  --radius-icons: 40px;
  --radius-pills: 10000px;
  --radius-badges: 12px;
  --radius-inputs: 14px;
  --radius-buttons: 14px;

  --shadow-primary-dark: rgba(255, 255, 255, 0.5) 0px 0.5px 0px 0px inset, rgba(117, 123, 133, 0.4) 0px 9px 14px -5px inset, rgb(44, 46, 52) 0px 0px 0px 1.5px, rgba(0, 0, 0, 0.14) 0px 4px 6px 0px;
  --shadow-md: rgba(0, 0, 0, 0.04) 0px 4px 12px 0px;
}

/* Badge colors — shared semantic mapping */
.badge-success { @apply border-emerald-500 text-emerald-700 bg-emerald-50; }
.badge-warning { @apply border-amber-400 text-amber-700 bg-amber-50; }
.badge-danger  { @apply border-ember text-white bg-ember; }
.badge-info    { @apply border-steel text-steel bg-paper; }
.badge-neutral { @apply border-cloud text-iron bg-paper; }
```

### Filament theme (`resources/css/filament/`)

Dos archivos CSS separados para cada panel, ambos importando la paleta Awesomic mapeada:

```css
/* resources/css/filament/admin/theme.css */
@import '/vendor/filament/filament/resources/css/theme.css';

@theme {
  --color-primary: #09090b;    /* obsidian */
  --color-secondary: #18181b;  /* graphite */
  --color-gray-50: #f4f4f5;   /* paper */
  --color-gray-100: #ececee;   /* cloud */
  --color-gray-200: #d4d4d8;  /* mist */
  --color-gray-300: #a1a1aa;  /* ash */
  --color-gray-400: #71717a;  /* fog */
  --color-gray-500: #52525b;  /* steel */
  --color-gray-600: #3f3f46;  /* iron */
  --color-gray-700: #27272a;  /* slate */
  --color-gray-800: #18181b;  /* graphite */
  --color-gray-900: #09090b;  /* obsidian */
  --color-danger: #ff5a00;    /* ember — usado para estados críticos */
  --color-warning: #ff5a00;   /* ember */
  --color-success: #22c55e;   /* green estándar (Awesomic no define verde) */
  --color-info: #52525b;      /* steel */
}

/* resources/css/filament/erp/theme.css — misma paleta que admin */
```

Registro en `config/filament.php`:

```php
'panels' => [
    'admin' => [
        'path' => 'admin',
        'theme' => \Filament\Support\Assets\Css::make('filament-admin', resource_path('css/filament/admin/theme.css')),
        // ...
    ],
    'erp' => [
        'path' => 'erp',
        'theme' => \Filament\Support\Assets\Css::make('filament-erp', resource_path('css/filament/erp/theme.css')),
        // ...
    ],
],
```

## Layouts del marketplace

### `resources/views/marketplace/layouts/app.blade.php`

```blade
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'TallerAutomóviles') — Encuentra tu taller</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-paper font-cosmica text-graphite antialiased">
    <x-marketplace.nav />

    <main class="mx-auto max-w-[1200px] px-4">
        {{ $slot }}
    </main>

    <x-marketplace.footer />
</body>
</html>
```

### `resources/views/marketplace/layouts/guest.blade.php`

Sin nav, sin footer. Solo contenedor centrado vertical y horizontalmente. Para pantallas de login OAuth, 404, etc.

```blade
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'TallerAutomóviles')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-paper font-cosmica text-graphite antialiased min-h-screen flex items-center justify-center">
    {{ $slot }}
</body>
</html>
```

### `resources/views/marketplace/layouts/auth.blade.php`

Igual que `app` pero con elementos adicionales: avatar del usuario, enlace a "Mis favoritos", "Mis reseñas", botón de cerrar sesión.

## Páginas del sistema

### Mapa de rutas

| Ruta | Vista | Layout | Permiso | Tecnología |
|---|---|---|---|---|
| `GET /` | `marketplace/home` | `guest` | público | Blade + Alpine |
| `GET /search` | `marketplace/search/index` | `app` | público | Blade + Alpine + Leaflet |
| `GET /talleres/{slug}` | `marketplace/workshops/show` | `app` | público | Blade + Alpine |
| `GET /auth/google/redirect` | controller redirect | `guest` | público | Socialite |
| `GET /auth/google/callback` | controller callback | `guest` | público | Socialite |
| `GET /dashboard` | `marketplace/dashboard/index` | `auth` | `auth:web` | Blade + Alpine |
| `GET /erp/*` | Filament panel | Filament | `auth:sistema` | Filament v3 |
| `GET /admin/*` | Filament panel | Filament | SUPER_ADMIN | Filament v3 |

## Catálogo de componentes Blade

### `resources/views/components/` (globales)

| Componente | Props | Variantes |
|---|---|---|
| `badge` | `type: 'success'|'warning'|'danger'|'info'|'neutral'`, `size: 'sm'|'md'` | outlined (defecto), filled (accent usa `ember`) |
| `button` | `variant: 'primary'|'ghost'|'neutral'`, `disabled: bool`, `loading: bool` | primary (#09090b), ghost (white + borde), neutral (#fafafa) |
| `card` | `padding: 'sm'|'md'|'lg'`, `border: bool`, `shadow: bool` | por defecto padding 28px, borde 1px cloud, radius 36px |
| `input` | `label, name, type, value, error, required, placeholder` | renderiza label + input + `@error` |
| `select` | `label, name, options, value, error, required` | renderiza select + label + `@error` |
| `empty-state` | `icon, title, description, actionLabel, actionUrl` | icono SVG inline, título bold, descripción muted |
| `alert` | `type: 'success'|'error'|'warning'|'info'`, `dismissible: bool` | icono + texto + botón de cerrar |
| `modal` | `id, title, width: 'sm'|'md'|'lg'` | Alpine `x-show` + backdrop + contenido slot |
| `pagination` | `paginator` | wrapper del `$paginator->links()` de Laravel con diseño Awesomic |

### `resources/views/components/marketplace/` (específicos del marketplace)

| Componente | Props | Descripción |
|---|---|---|
| `nav` | — | Nav sticky: logo izquierda, links centro, login + CTA derecha |
| `footer` | — | Footer con enlaces |
| `workshop-card` | `taller`, `showDistance: bool`, `showRating: bool` | Card 36px radius, 1px border, 28px padding. Imagen/logo, nombre, categorías (badges), dirección, rating, distancia |
| `search-filters` | `filters, onFilterChange` | Panel de filtros: búsqueda por texto, categoría, radio, calif. mínima, "abierto ahora" |
| `map` | `markers, center, zoom, onMarkerClick` | Wrapper Leaflet. Renderiza mapa en `#map-container` con Alpine |
| `star-rating` | `value, max: 5, readonly: bool, wire:input` | Estrellas SVG interactivas (Alpine) o estáticas |
| `review-card` | `resena` | Avatar + nombre + rating + fecha + comentario |
| `stats-block` | `number, label` | Número grande 56px weight 600 + label 14px weight 400 |

## Alpine stores y data

### `resources/js/alpine/store.js`

```js
document.addEventListener('alpine:init', () => {
  Alpine.store('search', {
    query: '',
    category: null,
    lat: null,
    lon: null,
    radius: 10, // km
    minRating: 0,
    openNow: false,
    results: [],
    loading: false,
    async search() { /* fetch /api/talleres/search?... */ }
  })

  Alpine.store('ui', {
    sidebarOpen: false,
    modalOpen: null,
    toasts: [],
    addToast(msg, type) { /* ... */ }
  })
})
```

### `resources/js/alpine/components/map.js`

```js
Alpine.data('map', () => ({
  map: null,
  markers: [],
  init() {
    this.map = L.map(this.$refs.container).setView([this.center.lat, this.center.lon], this.zoom)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map)
  },
  updateMarkers(markers) { /* ... */ }
}))
```

## Adaptación del spec original Awesomic a este proyecto

| Elemento Awesomic | En marketplace (Blade) | En Filament (ERP/Admin) |
|---|---|---|
| Color `obsidian` #09090b | Botones primarios, headlines | `primary` del panel |
| Color `ember` #ff5a00 | Badges de acento únicamente | `danger` + `warning` (stock crítico, anulación) |
| Color `cloud` #ececee | Bordes de 1px en tarjetas | No se fuerza (Filament usa sus bordes) |
| Tipografía Cosmica/DM Sans | `font-cosmica` global | `customTheme` con misma familia |
| Radius 36px en cards | Clase `rounded-[--radius-cards]` | No se fuerza |
| Radius 14px en botones | Clase `rounded-[--radius-buttons]` | No se fuerza (Filament usa sus radii) |
| 1px border en vez de sombras | `border border-cloud` en cards | No se fuerza |
| Badges 12px radius outlined | `<x-badge>` component | `->badge()->color(...)` nativo |
| Hero headline 64px | Home page únicamente | No aplica (Filament no tiene hero) |
| Email input field | Home page únicamente | No aplica |
| Logo strip (clientes) | Si aplica, en home | No aplica |
| Stats block | Si aplica, en home | Dashboard widgets de Filament |
| Breakthrough image | Si aplica, en home | No aplica |
| Dark feature card | Si aplica, en home / secciones | No aplica (Filament usa su diseño) |
| Navigation bar | `<x-marketplace.nav>` | Sidebar + topbar nativos de Filament |
| Category card (image top) | Si aplica, en home / catálogo | No aplica |

## Consistencia semántica de badges (compartido marketplace + Filament)

Mapeo unificado basado en `memory/constitution.md` §5:

| Estado | Color CSS | Clase Blade | Filament `->badge()->color()` |
|---|---|---|---|
| ACTIVO / PAGADO / COMPLETADO / ENTREGADO / ÉXITO | verde | `badge-success` | `color('success')` |
| PENDIENTE / EN_REVISION / PAUSADO / ESPERANDO_APROBACION | amarillo | `badge-warning` | `color('warning')` |
| EN_PROGRESO / EN_DIAGNOSTICO / EMITIDA | azul | `badge-info` | `color('info')` |
| ANULADO / RECHAZADO / SUSPENDIDO / STOCK_CRÍTICO / ERROR | rojo/ember | `badge-danger` | `color('danger')` |
| INACTIVO / CANCELADO | gris | `badge-neutral` | `color('gray')` |

## Dependencias con otras features

| Feature | Relación |
|---|---|
| `001-identidad-autenticacion` | Los layouts `auth` y `guest` dependen de los guards definidos allí |
| `003-gestion-talleres` | `<x-workshop-card>` usa datos del modelo `Taller` |
| `005-marketplace-busqueda-perfil` | Home page, search, perfil público usan componentes de este spec |
| `marketplace/home` | Vista concreta que implementa el hero Awesomic completo |
| `006-resenas-favoritos` | `<x-star-rating>`, `<x-review-card>` y estados de reseña |
| `007-clientes-vehiculos` a `015-auditoria` | Todas las features del ERP se renderizan con Filament; la UI se configura en los Resources de cada una, no en este spec |
