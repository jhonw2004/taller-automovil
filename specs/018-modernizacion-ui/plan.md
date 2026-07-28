---
id: 018-modernizacion-ui
---

# Plan — Modernización visual

## Alcance técnico

Solo se tocan: vistas Blade (`resources/views/`), componentes Blade (`resources/views/components/`), CSS (`resources/css/`, incluidos los temas de Filament), y configuración de tema de los `PanelProvider` (`->font()`, `->colors()`, `->viteTheme()` ya existentes). **No se toca**: `app/Actions/`, `app/Http/Controllers/` (salvo pasar datos ya calculados, nunca lógica nueva), migraciones, `routes/`, Resources de Filament (estructura de campos/columnas/acciones).

## 1. Home (`/`) — landing page

Reescritura de `resources/views/marketplace/home.blade.php`. `HomeController` se mantiene igual (ya expone `categorias` y `stats`, suficiente para las secciones del spec). Estructura de secciones (todas dentro del layout `marketplace.layouts.app` existente):

1. **Hero**: fondo `bg-obsidian` (ya usado), headline + subheadline, dos `<x-button>` (primary "Buscar talleres" → `talleres.buscar`, ghost "Registra tu taller" → `solicitudes.create`). Se quita el `<input>` de búsqueda inline y el logo grande duplicado (el logo ya vive en el nav).
2. **Cómo funciona**: 3 pasos en grid (`grid-cols-1 sm:grid-cols-3`), solo contenido estático (buscar → comparar → contactar), sin datos de servidor nuevos.
3. **Categorías destacadas**: se conserva el bloque existente (`$categorias`), se restyla la tarjeta (icono/color por categoría no es necesario, mismo `<a>` con mejor padding/hover).
4. **Estadísticas**: se conserva `<x-marketplace.stats-block>` con `$stats`, se integra visualmente a la sección "cómo funciona" o queda como franja separada.
5. **Para dueños de taller**: sección nueva, contenido 100% estático (no requiere datos de `$stats` distintos a los ya cargados), lista de beneficios + CTA a `solicitudes.create`. Fondo diferenciado (ej. `bg-graphite` o `bg-paper`) para separarla visualmente del resto.
6. **Cotización / "habla con nuestro equipo"** (agregada 2026-07-28, pedido explícito del usuario): 3 pasos estáticos (completa el formulario → lo revisamos → te contactamos) + un único CTA a `solicitudes.create`. Sin datos de contacto reales (no existen en el proyecto) — ver "Fuera de alcance" en `spec.md`.
7. **Preguntas frecuentes** (agregada 2026-07-28): 4 preguntas con respuestas basadas en comportamiento real ya implementado (búsqueda pública, flujo de solicitud, contenido del ERP), usando `<details>/<summary>` nativo (sin JS adicional, funciona sin JavaScript).
8. **CTA final**: banda corta, mismo estilo que el "breakthrough" ya existente, con un único CTA (buscar o registrar, a definir en implementación).

El `partial` `search-experience.blade.php` no se borra ni se modifica — sigue siendo incluido únicamente por `marketplace/search/index.blade.php` (`/talleres/buscar`).

### Animación (agregada 2026-07-28)

- `resources/js/reveal.js`: `IntersectionObserver` que agrega `.is-visible` a cualquier elemento `.reveal` la primera vez que entra en viewport (`threshold: 0.15`) y deja de observarlo — animación de una sola vez, no se repite al hacer scroll hacia arriba. Fallback sin `IntersectionObserver`: marca todo visible de inmediato (progressive enhancement, nunca oculta contenido si el navegador no lo soporta o si el script no carga después del render inicial del HTML, que ya es visible por defecto salvo por la clase `.reveal` — ver nota en `app.css`).
- `resources/css/app.css`: clases `.reveal`/`.reveal.is-visible` (fade + slide-up 20px, 600ms) y `.animate-float-slow` (keyframe de flotación lenta para el blob decorativo del hero), ambas anuladas bajo `@media (prefers-reduced-motion: reduce)`.
- Registrado globalmente en `resources/js/app.js` (`DOMContentLoaded` → `initScrollReveal()`), disponible en cualquier vista que cargue `resources/js/app.js` (todo el marketplace vía `marketplace.layouts.app`), no solo el Home.
- Hover-lift (`hover:-translate-y-*` + `hover:shadow-md`, ya con `transition duration-300`) en tarjetas de "Cómo funciona", categorías, beneficios de "Para dueños de taller" y pasos de "Cotización".

### Footer (rediseñado 2026-07-28)

`components/marketplace/footer.blade.php`: de una fila simple (logo + enlaces en línea) a grid de columnas (marca + descripción / enlaces "Marketplace" / enlaces "Para tu taller"), `grid-cols-1` en móvil, `sm:grid-cols-2 lg:grid-cols-4`. Aprovechado para corregir el mismo bug de padding invertido (`px-16 ... sm:px-4`) ya documentado como pendiente en `resume.md` — el footer ahora usa progresión mobile-first correcta (`px-16 sm:px-24 ... lg:px-16`). El `nav.blade.php` conserva el mismo bug, todavía sin corregir (fuera del alcance de esta sesión, que solo tocó el footer).

### Formularios (2026-07-28): hallazgo — layout legado

`resources/views/solicitudes/{crear,seguimiento}.blade.php` no usaban `marketplace.layouts.app` sino un layout distinto y más antiguo, `resources/views/layouts/marketplace.blade.php` (sin nav, sin footer, sin `resources/js/app.js`, con clases Tailwind `zinc-*` crudas en vez de los tokens de `016`) — la causa real de que el formulario de registro de taller se viera "genérico". Ambas vistas se migraron a `marketplace.layouts.app` + `<x-card>`/`<x-input>`/`<x-select>`/`<x-button>`/`<x-alert>`/`<x-badge>`, preservando exactamente los mismos `id`/`name` de campos (el JS del stepper de `crear.blade.php` depende de `getElementById`) y las mismas rutas de `action`. El layout legado `layouts/marketplace.blade.php` quedó sin ningún consumidor tras la migración — se eliminó (verificado con grep antes de borrar).

## 2. Resto de vistas marketplace

Ajustes de composición/espaciado/breakpoints en `search/index.blade.php`, `workshops/show.blade.php`, `dashboard/index.blade.php`, `components/marketplace/{nav,footer}.blade.php` — sin tocar el `x-data`/Alpine ni los endpoints que consumen. Se revisa cada uno en 375px/768px/1280px (breakpoints de referencia para los 3 rangos de `constitution.md` §5).

## 3. Filament — tema

- `resources/css/filament/{erp,admin}/theme.css`: se agregan overrides CSS propios (tipografía ya seteada por `->font('DM Sans')` en el `PanelProvider`, pero el CSS del tema puede afinar tracking/pesos, radii de tarjetas del dashboard, densidad de tablas) dentro de las capacidades de `viteTheme` de Filament v5 (custom CSS que se compila junto al tema base de Filament, verificar sintaxis actual con Context7 antes de escribir).
- Página de login (`App\Filament\Auth\Pages\Login`, ya existe en `app/Filament/Auth/Pages/Login.php` desde `001`): se le agrega una vista/slot personalizado si Filament v5 lo permite sin reescribir el flujo de autenticación (verificar con Context7 el mecanismo soportado — `LoginResponse`, slots, o `getFormSchema()` no se tocan, solo el envoltorio visual).
- Nada de esto cambia `->colors()` (ya correcto desde `017`) ni el guard/middleware de los `PanelProvider`.

## 4. Componentes compartidos

`components/{input,select,button,card}.blade.php`: se mantienen las mismas `@props`, solo cambian clases Tailwind (estados focus/hover/disabled, agrupación visual). Cualquier vista que ya usa estos componentes se beneficia sin cambios de código en esa vista.

## Verificación

- Suite completa de Pest debe seguir en verde (ningún test de `tests/Feature/` referencia clases CSS o estructura de HTML del Home más allá de, como máximo, la presencia de un link/formulario — se revisa antes de tocar la vista).
- `npm run build` sin errores.
- `php artisan serve` + `curl` de smoke test en las rutas tocadas (200/302 según sesión, nunca 500).
- Verificación visual en navegador real: sujeta a disponibilidad de `claude-in-chrome` en la sesión (mismo condicionante estructural que el resto del proyecto, ver `resume.md`).
