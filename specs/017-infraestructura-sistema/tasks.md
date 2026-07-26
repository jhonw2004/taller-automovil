# Tasks — Infraestructura del Sistema

## Prerrequisitos
- [x] Migración `0000_00_00_000001_create_extension_postgis.php` (primera en ejecutarse). Migrada correctamente.
- [x] Verificar PostGIS en PostgreSQL: `SELECT PostGIS_Version();` → `3.6 USE_GEOS=1 USE_PROJ=1 USE_STATS=1`. PostgreSQL 18.3, PostGIS 3.6.2 ya estaban disponibles en el servidor.
- [x] `config/app.php`: `'timezone' => 'America/La_Paz'`. Verificado con `now()` (UTC-4).
- [x] `.env`: `QUEUE_CONNECTION=database` — ya estaba seteado. Tabla `jobs`/`job_batches`/`failed_jobs` — **ya viene migrada por defecto en el skeleton de Laravel 13** (`0001_01_01_000002_create_jobs_table.php`), no hizo falta `php artisan queue:table`.

## Seeding
- [ ] Migración del catálogo de permisos (60 permisos desde `002-roles-permisos/plan.md`).
- [ ] Seeder `UnidadMedidaSeeder` (unidad, litro, metro, kilo).
- [ ] Seeder `MetodoPagoSeeder` (efectivo, tarjeta, QR, transferencia).
- [ ] Seeder `CategoriaTallerSeeder` (mecánica general, electricidad, neumáticos, diagnóstico, tuning, hojalatería).
- [ ] Seeder `RolSistemaSeeder` (8 roles + mapping de permisos según tabla en plan.md).
- [ ] Seeder `SuperAdminSeeder` (username `superadmin`, password temporal, solo visible en consola).
- [ ] Todos los seeds son idempotentes (`firstOrCreate`).

## Instalación de paquetes

**Nota (2026-07-26):** las versiones abajo fueron corregidas contra el estado real de los paquetes (verificado con Context7 + Packagist) al momento de instalar. El proyecto ya corría Laravel 13.8 / PHP 8.5.4, lo cual invalidó varias versiones que este plan asumía originalmente. Ver `plan.md` §1 para el detalle de cada cambio.

- [x] `composer require filament/filament:^5.0 spatie/laravel-permission:^7.0 laravel/socialite:^5 spatie/laravel-sluggable:^4 spatie/laravel-activitylog:^5.0 guzzlehttp/guzzle:^7` — **Filament v5** (no v3.2: v3 no está garantizada contra Laravel 13/PHP 8.5 y sus plugins clave ya no la soportan). **spatie/laravel-permission v7** (v6 es para Laravel 8-12; v7 es la línea recomendada para Laravel 12/13, PHP 8.3+). **spatie/laravel-activitylog v5** (requiere PHP 8.4+, cumplido).
- [x] `maatwebsite/excel` **no se pudo instalar**: `phpoffice/phpspreadsheet` exige `php <8.5.0` y el proyecto corre PHP 8.5.4. Se aplicó el fallback ya previsto en este plan: **`spatie/simple-excel:^3.10`** en vez de `maatwebsite/excel` + `pxlrbt/filament-excel`. Los exports de Filament (Resources en specs 007-015) deben implementarse con acciones custom sobre `SimpleExcelWriter`, no con `ExportBulkAction` de `pxlrbt/filament-excel`.
- [x] `composer require --dev laravel/breeze:^2.4 barryvdh/laravel-debugbar:^4.4` — Pest **ya estaba instalado** (`pestphp/pest:^4.7`, `pestphp/pest-plugin-laravel:^4.1`) en el skeleton de Laravel 13; no había `phpunit/phpunit` que remover. Se omitieron esos pasos.
- [x] `npm install leaflet @fontsource/dm-sans` (Tailwind v4 no necesita `@tailwindcss/forms` — Preflight lo maneja)
- [x] `php artisan vendor:publish --tag=permission-config --force` + `--tag=permission-migrations --force` (spatie/laravel-permission v7 usa `spatie/laravel-package-tools`; el tag real es `permission-*`, no el nombre del provider)
- [x] `php artisan vendor:publish --tag=activitylog-config --force` + `--tag=activitylog-migrations --force` (mismo motivo: tag `activitylog-*`)
- [x] Extensión PHP `intl` habilitada en `php.ini` (requerida por Filament v5, estaba deshabilitada en el sistema)
- [x] `php artisan filament:install --panels` + `php artisan make:filament-panel erp` — paneles `AdminPanelProvider` y `ErpPanelProvider` creados y registrados en `bootstrap/providers.php`
- [x] Smoke test manual (`class_exists`) de las 9 clases críticas de los paquetes instalados: todas presentes

## Configuración de roles/permisos
- [x] ~~`config/permission.php`~~ **Obsoleto**: `spatie/laravel-permission` fue removido por completo en la sesión de `002-roles-permisos` (decisión 2026-07-26, ver `constitution.md` §1). Roles/permisos son modelos 100% custom (`Rol`/`Permiso`/`AsignacionRol`), sin `teams`/`team_foreign_key` de Spatie — el "team activo" es directamente `session('taller_activo_id')`.
- [x] ~~Listener que ejecuta `setPermissionsTeamId()`~~ **Obsoleto**, no aplica (no hay `PermissionRegistrar`). Reemplazado por el middleware `SetTallerActivo` (ver abajo), que solo escribe `session('taller_activo_id')` — ningún listener adicional es necesario porque `tienePermiso()`/`BelongsToTallerScope` leen la sesión directamente.

## Paneles Filament
- [x] `php artisan filament:install --panels` (crea el panel `admin` automáticamente, no hace falta `make:filament-panel admin` aparte)
- [x] `php artisan make:filament-panel erp`
- [x] `AdminPanelProvider` personalizado: paleta Awesomic (`->colors([...])`), `discoverResources`/`discoverPages`/`discoverWidgets` movidos a `Filament/Admin/...`, `ForzarCambioPasswordMiddleware` en `authMiddleware()`. `SetTallerActivo` deliberadamente NO se agregó aquí (ver plan.md §2, decisión: Super Admin no opera dentro de un taller).
- [x] `ErpPanelProvider` personalizado: paleta Awesomic, `SetTallerActivo` + `ForzarCambioPasswordMiddleware` en `authMiddleware()`. Se decidió no usar `->tenant()` nativo de Filament (ver plan.md §2) para no duplicar la fuente de verdad del tenant activo (ya resuelta por `BelongsToTaller` + sesión).
- [x] Widget `TenantSwitcher` en panel ERP (`canView()` solo si el usuario tiene >1 taller vigente).
- [x] Middleware `SetTallerActivo` (`app/Http/Middleware/SetTallerActivo.php`), registrado solo en `ErpPanelProvider`.
- [x] Middleware `ForzarCambioPasswordMiddleware` (nuevo, no estaba en el plan original) + páginas `CambiarPassword` en ambos paneles — resuelve el pendiente de `001-identidad-autenticacion` ("pantalla de cambio de contraseña obligatorio").
- [ ] "Crear recursos base para cada panel (dashboard, sidebar skeleton)" — el dashboard default de Filament se deja tal cual; los Resources reales de cada feature (002/003 en esta sesión, 007+ después) son los que pueblan el sidebar.

## Multi-tenant: BelongsToTaller
- [x] Trait `App\Traits\BelongsToTaller` con global scope + auto-fill `taller_id` en creating (`app/Traits/BelongsToTaller.php`)
- [x] Scope `App\Models\Scopes\BelongsToTallerScope` (`app/Models/Scopes/BelongsToTallerScope.php`)
- [x] Método `sinScope()` con auditoría de `withoutGlobalScope` (usa el helper `activity()` de spatie/laravel-activitylog)
- [x] Middleware `SetTallerActivo` que setea `taller_activo_id` en sesión (usa `asignacionesVigentes()`, no `setPermissionsTeamId()` — ver nota arriba)
- [x] ~~Redirección con mensaje si usuario no tiene acceso a ningún taller~~ **No hizo falta código nuevo**: `Filament\Http\Middleware\Authenticate` (registrado antes en `authMiddleware()`) ya deniega con 403 vía `canAccessPanel()` antes de que `SetTallerActivo` se ejecute — ver comentario en el middleware.
- [ ] Tests Pest: usuario taller A no ve datos del taller B, creación auto-asigna `taller_id`, sinScope queda auditado — requieren un modelo real con el trait aplicado; se escribirán junto con la primera feature que lo use (`007-clientes-vehiculos` en adelante, ningún modelo real lo usa todavía)

## Generación concurrente de códigos
- [x] Action `App\Actions\SequentialCodeGenerator` con `FOR UPDATE` + reintentos (`app/Actions/SequentialCodeGenerator.php`). Se corrigió un bug del plan original: el parámetro `$retries` no se usaba (la llamada a `DB::transaction()` tenía `5` hardcodeado); ahora sí se respeta.
- [ ] Tests Pest: concurrencia simulada (2 procesos simultáneos) genera códigos distintos, deadlock se reintenta — requiere una tabla real con columna `codigo`/`taller_id`; se escribirá junto con la primera feature que lo consuma (`011-ordenes-trabajo` o `012-notas-venta`)

## Rutas API y Web
- [ ] Crear `routes/api.php` con grupos de rutas para búsqueda, reseñas, favoritos — los controladores referenciados (`TallerBusquedaApiController`, etc.) pertenecen a `005`/`006`, no existen todavía. No tiene sentido crear las rutas antes que los controllers.
- [ ] Crear `routes/web.php` con rutas del marketplace — mismo motivo, depende de `001`/`005`.
- [ ] Verificar que `RouteServiceProvider` o `bootstrap/app.php` cargan `routes/api.php`

## Helpers PostGIS
- [x] Trait `App\Traits\HasGeolocation` con: boot (sincroniza geom en saving), `scopeCercanoA`, `scopeConDistanciaA` (`app/Traits/HasGeolocation.php`). Corregido el plan original: usa `sprintf('%F', ...)` en vez de interpolar `{$model->lon}` directamente (evita problemas de locale con separador decimal).
- [x] Cast `App\Casts\GeometryCast` (WKB/WKT ↔ lat/lon) (`app/Casts/GeometryCast.php`). El plan original no traía código para este cast — se implementó decodificando EWKB hexadecimal (little-endian, Point con SRID) manualmente. **Verificado contra PostGIS real**, no solo revisado a ojo: round-trip `get()`/`set()` con `ST_AsHexEWKB`/`ST_SetSRID(ST_MakePoint(...))` da el resultado exacto.

## Migración de reemplazo de talleres
- [ ] Crear migración `2026_08_01_000001_replace_talleres_table.php` que dropea la tabla prototipo y crea la nueva con todas las columnas del spec 003
- [ ] Crear migraciones para `categorias`, `talleres_categorias`, `talleres_horarios`
- [ ] Verificar que `php artisan migrate:fresh` ejecuta sin errores

## Tests Pest generales
- [ ] Instalación: todos los paquetes están disponibles (test de humo: `assertTrue(class_exists(...))` para cada paquete crítico)
- [ ] Panel `/admin` carga para SUPER_ADMIN, da 403 para otros roles
- [ ] Panel `/erp` carga para usuario con rol activo en un taller
- [ ] Tenant Switcher aparece si usuario tiene múltiples talleres
- [ ] Código secuencial no colisiona bajo concurrencia simulada
