# Resume — Estado del proyecto y trabajo realizado

Última actualización: 2026-07-26 (segunda sesión). Este archivo existe para que cualquier agente (o persona) pueda retomar el trabajo sin releer toda la conversación anterior.

## Qué se hizo el 2026-07-26 (segunda sesión): commit, correcciones post-instalación y push

- Se corrigieron `AGENTS.md`, `017-plan.md` y `017-tasks.md`:
  - **AGENTS.md**: reescrito con tabla de orden de implementación obligatorio (paso 0→7) + notas sobre specs transversales.
  - **017-plan.md**: `maatwebsite/laravel-excel:^3` → `maatwebsite/excel` (v4.x, compatible Laravel 11+; si falla, fallback `spatie/simple-excel`). Eliminado `@tailwindcss/forms` — **Tailwind v4 no lo necesita**, Preflight + utility classes manejan forms nativamente.
  - **017-tasks.md**: refleja los mismos cambios con nota de verificación.
- Se creó la rama `specs/planificacion`, se agregaron todos los specs y documentación, y se subió a `origin/specs/planificacion` (commit `7e7f074`).
- El código de `017` (infraestructura: paquetes, PostGIS, BelongsToTaller, GeometryCast, etc.) y `001` (backend + tests de identidad y autenticación) está **sin commitear aún** en `specs/planificacion` — está pendiente agregarlo y pushearlo.

## Qué se hizo el 2026-07-26 (primera sesión): instalación de paquetes (`017-infraestructura-sistema`, fase 0)

Se instalaron los paquetes Composer/NPM de `017-infraestructura-sistema`. El proyecto Laravel ya no es un esqueleto vacío: corre Laravel 13.8 / PHP 8.5.4, y sobre eso se instalaron Filament v5.7.3, spatie/laravel-permission 7.4.2, laravel/socialite 5.29, spatie/laravel-sluggable 4.0.2, spatie/laravel-activitylog 5.0, spatie/simple-excel 3.10, guzzlehttp/guzzle 7, y (dev) laravel/breeze 2.4, barryvdh/laravel-debugbar 4.4. Pest v4 ya venía instalado con el skeleton.

**Todo esto se verificó con Context7 + Packagist contra el estado real de PHP 8.5.4/Laravel 13.8 antes de instalar**, porque `017-plan.md` había quedado desactualizado en varios puntos. El detalle completo (qué cambió y por qué) está documentado en `specs/017-infraestructura-sistema/plan.md` §1 y `tasks.md` — léelos antes de asumir que el plan original es fiel a lo instalado. Resumen de las decisiones que importan para retomar:

1. **Filament v5, no v3.2** (decisión confirmada con el usuario). v3 no tiene compatibilidad garantizada con Laravel 13/PHP 8.5 y plugins clave ya no la soportan.
2. **`maatwebsite/excel` no se pudo instalar** (conflicto de versión de PHP con `phpoffice/phpspreadsheet`). Se sustituyó por `spatie/simple-excel` + `pxlrbt/filament-excel` fue removido del plan. **Los exports en Resources de Filament (specs 007-015) se implementan con acciones custom sobre `SimpleExcelWriter`, no con `ExportBulkAction`.**
3. **Se detectaron y corrigieron dos APIs de Filament inventadas** en el plan original (`->tenantOwnership(Ownership::new(...))` y `->spatiePermission()` — ninguna existe en ninguna versión real de Filament). La autorización real se integra vía Laravel Model Policies; la tenancy nativa real es `->tenant(Model::class, slugAttribute: ...)`. **Al implementar los PanelProviders y cualquier Resource de Filament, no confiar en la sintaxis literal de versiones previas de `017-plan.md` — verificar contra la documentación real de Filament v5 (Context7) primero.**
4. Se habilitó la extensión PHP `intl` en `php.ini` (requerida por Filament v5, estaba deshabilitada en el sistema).
5. `php artisan filament:install --panels` + `make:filament-panel erp` ejecutados: `AdminPanelProvider` y `ErpPanelProvider` creados y registrados en `bootstrap/providers.php`. **Aún no tienen la configuración de colores/middleware/tenancy del plan** — son el scaffold default de Filament, falta personalizarlos.
 6. Config y migraciones de `spatie/laravel-permission` y `spatie/laravel-activitylog` publicadas (tablas aún no migradas — falta correr `php artisan migrate` una vez esté lista la migración de PostGIS que debe ir primero).

### Qué más se hizo el 2026-07-26: resto de infra autocontenida de `017`

Después de la instalación de paquetes se completó todo lo de `017-infraestructura-sistema` que **no depende de modelos de otras specs**:
- Migración PostGIS (`0000_00_00_000001_create_extension_postgis.php`), migrada. PostgreSQL 18.3 + PostGIS 3.6.2 ya estaban en el servidor.
- `config/app.php` timezone `America/La_Paz`.
- Queue: `QUEUE_CONNECTION=database` ya estaba en `.env`, y la tabla `jobs` ya viene por defecto en el skeleton de Laravel 13 — no hizo falta nada.
- `config/permission.php`: `teams=true` + `team_foreign_key=taller_id`, seteado **antes** de migrar las tablas de permisos (importante: si se migra con `teams=false` y se cambia después, hay que dropear y re-migrar esas tablas — spatie/laravel-permission no las actualiza retroactivamente).
- `app/Traits/BelongsToTaller.php` + `app/Models/Scopes/BelongsToTallerScope.php` + `sinScope()`.
- `app/Actions/SequentialCodeGenerator.php` (con un bug del plan corregido: el parámetro `$retries` no se usaba).
- `app/Traits/HasGeolocation.php` + `app/Casts/GeometryCast.php` — **verificados contra PostGIS real** (round-trip `get()`/`set()` con `ST_AsHexEWKB` da el resultado exacto, no solo revisado a ojo). El plan original no traía código para `GeometryCast`, se implementó desde cero decodificando EWKB hexadecimal a mano.

### Por qué se pausó `017` ahí y se sigue con `001`

Lo que queda de `017-infraestructura-sistema` (seeding de permisos/roles/super admin/catálogos, personalización real de los PanelProviders, middleware `SetTallerActivo`, rutas API/web, migración de reemplazo de `talleres`) **depende de modelos que no existen hasta `001`/`002`**: `usuarios_sistema`, `Identidad`, `Rol`/`Permiso` con su relación `asignacionesRol()`, controladores de `005`/`006` para las rutas. Esto es una inconsistencia estructural del plan original de `017` (fue escrito asumiendo que esos modelos ya existirían), documentada en detalle en `specs/017-infraestructura-sistema/tasks.md`.

**Decisión (2026-07-26, confirmada con el usuario):** en vez de forzar un workaround (ej. crear la migración de reemplazo de `talleres` sin la FK a `usuarios_sistema`, o inventar el shape de `asignacionesRol()`), se pausa `017` en su punto autocontenido y se pasa a implementar `001-identidad-autenticacion`. Cuando existan esos modelos, retomar `017-infraestructura-sistema/tasks.md` para terminar: seeding, `SetTallerActivo`, paneles Filament personalizados (paleta, middleware, guard `sistema`), rutas, y la migración de reemplazo de `talleres` (con la FK correcta esta vez).

## Qué se hizo después: 001-identidad-autenticacion (backend completo, sin UI)

Implementado y **verificado funcionalmente contra la BD real** (transacciones con rollback en cada prueba, no solo `class_exists`):

- 6 migraciones (`identidades`, `usuarios_marketplace`, `identidades_oauth`, `usuarios_sistema`, `credenciales_sistema`, `historial_passwords`) + 6 modelos Eloquent con relaciones probadas de punta a punta.
- Se eliminó la tabla/modelo `users` default de Laravel (no es parte de esta arquitectura) — `DatabaseSeeder` limpiado.
- Guards `web`/`sistema` en `config/auth.php`. `UsuarioMarketplace`/`UsuarioSistema` extienden `Illuminate\Foundation\Auth\User`, sin remember-me (`$rememberTokenName = ''`).
- OAuth Google: `GoogleAuthController`, `LoginOrRegisterMarketplaceUserAction` (idempotencia por `provider_subject` verificada: 2 logins seguidos no duplican nada).
- `App\Auth\UsuarioSistemaProvider` (provider custom): rechaza login si `activo=false` o `bloqueado_hasta` en el futuro — un provider `eloquent` estándar no puede validarlo porque esos datos viven en `CredencialSistema`, no en `UsuarioSistema`. Probado: cuenta bloqueada con password correcta → rechazada; desbloqueada → aceptada.
- Listeners `RegistrarIntentoFallidoListener`/`ReiniciarIntentosFallidosListener` (auto-descubiertos, sin registro manual): 5 fallos → bloqueo 15 min; login exitoso resetea el contador. Probado end-to-end con `Auth::attempt()` real.
- `CambiarPasswordAction`: rechaza reutilizar la actual o cualquiera de las últimas 5, recorta el historial a 5. Probado con 7 cambios sucesivos.
- `GenerarCredencialInicialAction` / `CrearUsuarioSistemaAction`: generan password temporal de 16 caracteres que cumple la política (`Password::min(12)->mixedCase()->numbers()->symbols()`), verificado contra el validador real.
- `App\Exceptions\BusinessException` (transversal, constitution.md §4) creada porque hacía falta para `CambiarPasswordAction`.
- `CheckSessionExpiration` (30 min de inactividad, guard `sistema`) registrado en ambos PanelProviders de Filament.
- Hallazgo importante: Filament v5 **ya trae rate limiting nativo** en su página de Login (`WithRateLimiting`, 5 intentos/60s por IP) — cubre el `throttle:5,1` del spec sin código adicional.
- Decisión: **no se corrió `laravel/breeze:install`** — genera scaffolding de login/registro con contraseña atado a una tabla `users` que ya no existe en esta arquitectura. Se implementó el flujo OAuth a mano en su lugar. El paquete queda instalado por si sirve de referencia.

### Qué falta de `001`

- **UI**: pantalla/Livewire component de cambio de contraseña obligatorio (primer login + expirada) — el backend (`CambiarPasswordAction`) ya está listo para que la consuma.
- OWASP: `APP_DEBUG=false`/`composer audit` en CI (no hay pipeline de CI configurado todavía en este repo), signed routes para desbloqueo de usuario (no hay pantalla que las emita aún, es de `002`/`008`).
- La asignación de rol `OWNER`/rol de empleado al crear un usuario sistema queda con una nota explícita en el código (`GenerarCredencialInicialAction`, `CrearUsuarioSistemaAction`) — depende del catálogo de roles de `002-roles-permisos`, que no existe todavía.

## Qué se hizo después: suite de tests Pest de 001 (26/26 verdes)

Se provisionó infraestructura de test real (no se podía usar antes de ahora):
- BD dedicada `taller_test` en el PostgreSQL local, `.env.testing`, `RefreshDatabase` habilitado en `tests/Pest.php`.
- **`phpunit.xml` forzaba SQLite en memoria** (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`), lo que tiene prioridad sobre `.env.testing` y violaba `constitution.md` §6 explícitamente. Se quitaron esas líneas.
- Factories nuevas: `IdentidadFactory`, `UsuarioSistemaFactory`, `CredencialSistemaFactory`, `UsuarioMarketplaceFactory`.
- 9 archivos de test en `tests/Feature/Identidad/` cubriendo los 9 criterios de `001-identidad-autenticacion/tasks.md` (idempotencia OAuth, separación de guards, bloqueo por intentos, rate limiting nativo de Filament vía `Livewire::test()`, política de password incluyendo `uncompromised()` con `Http::fake()`, expiración de password, reutilización de historial, expiración de sesión, password nunca expuesto en serialización).
- **26/26 tests pasan**, 55 assertions. `laravel/pint` sin pendientes.

### Tres bugs reales que la suite encontró (no solo bugs de los tests) — habrían llegado a producción

1. **`UsuarioSistema` no implementaba `Filament\Models\Contracts\FilamentUser`** → Filament v5 deniega el acceso a **todos** los paneles (403) por defecto sin ese contrato. Ningún usuario sistema habría podido entrar a `/admin` ni `/erp`. Corregido con `canAccessPanel()` (por ahora solo exige `activo`; restricción real por rol pendiente de `002`).
2. **Tampoco implementaba `Filament\Models\Contracts\HasName`** → Filament intenta leer un atributo `name` inexistente (el modelo usa `nombre`/`apellido`) y explota con `TypeError` al renderizar cualquier página. Corregido con `getFilamentName()`.
3. **Bug real en `CheckSessionExpiration`**: `now()->diffInMinutes($ultimaActividad) > 30` — en la versión de Carbon de este proyecto `diffInMinutes()` devuelve un valor **con signo** (negativo para fechas pasadas), no absoluto. La sesión nunca habría expirado por inactividad pese a que el código "se veía" correcto. Corregido comparando fechas directamente (`->lt(now()->subMinutes(30))`).

Estos tres son la razón de peso para no saltarse la suite de tests aunque el backend ya estuviera "funcionalmente verificado a mano" — las pruebas manuales por tinker no pasan por la pila real de Filament (paneles, Livewire) ni ejercitan el paso del tiempo, así que no los habrían atrapado.

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

## Estado real del código

El proyecto Laravel ya no es un esqueleto:
- **Stack instalado**: Filament v5.7.3, spatie/laravel-permission 7.4.2, laravel/socialite 5.29, spatie/laravel-sluggable 4.0.2, spatie/laravel-activitylog 5.0, spatie/simple-excel 3.10, guzzlehttp/guzzle 7, laravel/breeze 2.4, barryvdh/laravel-debugbar 4.4 (dev). Pest v4 viene con el skeleton.
- **017-infraestructura-sistema**: migración PostGIS ejecutada, timezone configurado, `BelongsToTaller` trait + scope, `HasGeolocation` trait + `GeometryCast`, `SequentialCodeGenerator`. Pendiente: seeding, `SetTallerActivo`, paneles Filament personalizados, rutas API/web (dependen de modelos 001/002).
- **001-identidad-autenticacion (backend)**: 6 migraciones + modelos, OAuth Google, guards `web`/`sistema`, provider custom, bloqueo por 5 intentos, expiración de sesión 30 min, historial de 5 contraseñas. **26/26 tests verdes** en Pest.
- **Prototipo Taller** existente pero incompatible con spec `003` — requiere migración de reemplazo (anotado en tasks).
- Git: repositorio en rama `specs/planificacion`, specs/documentación commiteados y pusheados. Código de implementación pendiente de commit.

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

1. **Commitear y pushear** el código de implementación de `017` y `001` (pendiente).
2. Implementar `002-roles-permisos` — catálogo de permisos, roles de sistema, asignaciones. Esto desbloquea el seeding de `017`.
3. Retomar `017-infraestructura-sistema` seeding + `SetTallerActivo` + paneles personalizados + rutas.
4. Seguir en orden de dependencia: `003` → `004` → `005` + `016` → `006` → `007`…`015`.
5. Al completar una feature con código + tests que cubran sus criterios de aceptación, actualizar `status: implemented` en el frontmatter de su `spec.md` (no antes).

## Cómo navegar si eres un agente retomando esto

Lee `AGENTS.md` primero — tiene las reglas de navegación completas. Resumen rápido: `memory/constitution.md` una vez (convenciones globales, no se repiten por feature) → `specs/<feature>/spec.md` → `plan.md` → `tasks.md` de la feature que vayas a trabajar. No leas features no relacionadas salvo que aparezcan en `depends_on`.
