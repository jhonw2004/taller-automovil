# Resume — Estado del proyecto y trabajo realizado

Última actualización: 2026-07-20. Este archivo existe para que cualquier agente (o persona) pueda retomar el trabajo sin releer toda la conversación anterior.

## Qué es este proyecto

Plataforma de talleres mecánicos en Santa Cruz, Bolivia: marketplace público (conductores buscan talleres, con reseñas/favoritos, login Google) + mini-ERP por taller (clientes, vehículos, empleados, inventario, órdenes de trabajo, notas de venta, pagos), multi-tenant por `taller_id`. Stack objetivo: Laravel 13, PHP 8.4+, PostgreSQL + PostGIS, Blade+Tailwind+Alpine (marketplace), FilamentPHP v3 (ERP + Super Admin).

## Qué se hizo en esta sesión

Se ejecutó una reestructuración completa de `/specs`, que originalmente eran 4 archivos en bruto sin frontmatter, mezclando QUÉ y CÓMO, sin descomponer por feature:
- `MacroSpec.md` (monolito de ~17 dominios de negocio)
- `Baterias.md` (decisiones de paquetes Composer)
- `UI_Spec.md` (pantallas/UI de los 3 productos)
- `DataBase/database.md` (39 tablas del esquema físico)

**Esos 4 archivos ya no existen** — su contenido se migró y se eliminaron (nunca estuvieron en git, no se perdió historial).

### Resultado

```
AGENTS.md                    <- guía de navegación para agentes (leer primero)
memory/constitution.md       <- convenciones no-negociables (arquitectura, datos, reglas transversales, errores, UI, testing, seguridad)
resume.md                    <- este archivo
specs/
  001-identidad-autenticacion/
  002-roles-permisos/
  003-gestion-talleres/
  004-solicitud-alta-taller/
  005-marketplace-busqueda-perfil/
  006-resenas-favoritos/
  007-clientes-vehiculos/
  008-empleados-usuarios-erp/
  009-catalogo-servicios/
  010-inventario-repuestos/
  011-ordenes-trabajo/
  012-notas-venta/
   013-pagos/
   014-notificaciones/
   015-auditoria/
   016-ui-design-system/
   017-infraestructura-sistema/
```

Cada carpeta de feature tiene:
- `spec.md` — QUÉ (frontmatter YAML `id`/`status`/`depends_on`/`resumen` + criterios de aceptación Given/When/Then). Sin detalles de implementación.
- `plan.md` — CÓMO (tablas, columnas, constraints, modelos Eloquent, Actions, paquetes, Filament Resources).
- `tasks.md` — checklist de tareas atómicas y verificables derivadas del plan.

Las 17 features están numeradas por orden de dependencia (`depends_on` en el frontmatter). **Todas están en `status: draft`** — nada de esto está implementado todavía en código (ver más abajo).

## Estado real del código (verificado, no asumido)

El proyecto Laravel es prácticamente un esqueleto recién iniciado:
- `composer.json` es el default de Laravel; **ninguno** de los paquetes de la arquitectura objetivo (Filament, spatie/laravel-permission, socialite, activitylog, etc.) está instalado todavía.
- Solo existe un prototipo mínimo: modelo/migración/controlador de `Taller` (`app/Models/Taller.php`, `database/migrations/2026_07_19_174015_create_talleres_table.php`, `app/Http/Controllers/TallerController.php`).
- **Ese prototipo no coincide con el spec de `003-gestion-talleres`**: le faltan `slug`, `estado`, `visible_en_mapa`, `calificacion_promedio`, `cantidad_resenas`; tiene una columna `horario` string en vez de la tabla `talleres_horarios`. Cuando se implemente `003-gestion-talleres`, la tarea ya está anotada en su `tasks.md`: requiere una migración de **reemplazo**, no incremental.
- No hay tests reales (solo los `ExampleTest.php` default de Laravel).
- Es un repo git local (`taller-automoviles/.git`), rama `main`, un solo commit ("Commit inicial del proyecto"). Nada de lo hecho en esta sesión está commiteado todavía.

## Decisiones resueltas (2026-07-25)

1. **`002-roles-permisos` — Roles operativos**: son roles de sistema fijos (`es_sistema = true`). El admin de taller puede crear roles personalizados adicionales pero no eliminar/modificar los de sistema.
2. **`002-roles-permisos` — Catálogo de permisos**: lista final aprobada (60 permisos en 16 módulos, definida en `plan.md`).
3. **`012-notas-venta` — Múltiples notas por orden**: permitido (regla permisiva), respetando ACID.
4. **`011-ordenes-trabajo` / `012-notas-venta` — Motivo de anulación**: **obligatorio** en ambos casos. Reglas ACID definidas: bloqueo si hay pagos, auto-anulación de nota EMITIDA al anular orden, reposición automática de stock.
5. **`016-ui-design-system`**: aprobado. Awesomic en marketplace (Blade), paleta mapeada en Filament. Sin modo oscuro en marketplace. Alpine stores + data components.

## Brechas críticas cubiertas (2026-07-25)

1. **`017-infraestructura-sistema`** — paquetes, paneles Filament, BelongsToTaller, concurrencia en códigos, rutas API, helpers PostGIS, migración de reemplazo de talleres.
2. **`005`** — endpoint JSON `GET /api/talleres/search` para Alpine.fetch().
3. **`006`** — endpoints `POST /api/resenas`, `DELETE /api/resenas/{id}`, `POST /api/favoritos`, `POST /api/favoritos/delete`.
4. **`001`** — ruta `POST /auth/logout` para marketplace.
5. **`003`** — política de soft delete en cascada (sin bloqueo, advertencia al Super Admin).
6. **`003`** — migración de reemplazo del prototipo `talleres` documentada en `017-plan.md`.

## Brechas de infraestructura y calidad cubiertas (2026-07-25)

| # | Omisión | Solución |
|---|---|---|
| 1 | Seeding inicial | `017-plan.md` §8: Super Admin, permisos, roles + mapping, métodos pago, unidades, categorías |
| 2 | PostGIS requisito | `017-plan.md` §0: migración `CREATE EXTENSION postgis` |
| 3 | Timezone | `017-plan.md` §0: `config/app.php: timezone => America/La_Paz` |
| 4 | Queue | `017-plan.md` §0: `QUEUE_CONNECTION=database`, tabla jobs migrada, recálculo síncrono |
| 5 | "Abierto ahora" vía SQL | `005-plan.md`: LEFT JOIN a `talleres_horarios` con `whereTime`, no post-query |
| 6 | Soft delete bloqueante | `constitution.md` §3.13: bloquea si hay hijos activos (excepción: taller) |
| 7 | Error handling pattern | `constitution.md` §4: `BusinessException` → 422/redirect, toast Filament, JSON error |
| 8 | Autocomplete en ERP | `017-plan.md` §9: patrón `->searchable()->getSearchResultsUsing()` con global scope |
| 9 | File uploads | `constitution.md` §1: `Storage::putFile()` (disco `public`), no medialibrary |
| 10 | Naming convention | `constitution.md` §1: `app/Actions/{Feature}/{Nombre}Action.php` |
| 11 | Test DB | `constitution.md` §6: PostgreSQL dedicado (`.env.testing`), no SQLite |
| 12 | Exportaciones | `017-plan.md` §9: Resources que exportan listados |
| 13 | Loading states | `constitution.md` §5: skeleton en búsqueda, spinner en mapa |
| 14 | Notification triggers | `014-plan.md`: tabla con 10 eventos, feature origen, destinatario |

## Próximos pasos sugeridos

1. Implementar `017-infraestructura-sistema` primero (paquetes, PostGIS, timezone, queue, paneles, BelongsToTaller, seeds, rutas, helpers).
2. Luego implementar en orden de dependencia: `001` → `002` → `003` → `004` → `005` + `016` → resto.
2. Empezar implementación en orden de dependencia: `001-identidad-autenticacion` → `002-roles-permisos` → `003-gestion-talleres` (con la migración de reemplazo del prototipo) → resto según `depends_on`.
3. Instalar el stack base descrito en `memory/constitution.md` §1 antes de escribir código de cualquier feature (Filament, spatie/laravel-permission con `teams=true`, laravel/socialite, laravel/breeze).
4. Al completar una feature con código + tests que cubran sus criterios de aceptación, actualizar `status: implemented` en el frontmatter de su `spec.md` (no antes).

## Cómo navegar si eres un agente retomando esto

Lee `AGENTS.md` primero — tiene las reglas de navegación completas. Resumen rápido: `memory/constitution.md` una vez (convenciones globales, no se repiten por feature) → `specs/<feature>/spec.md` → `plan.md` → `tasks.md` de la feature que vayas a trabajar. No leas features no relacionadas salvo que aparezcan en `depends_on`.
