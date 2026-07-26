# Tasks — Infraestructura del Sistema

## Prerrequisitos
- [ ] Migración `0000_00_00_000001_create_extension_postgis.php` (primera en ejecutarse).
- [ ] Verificar PostGIS en PostgreSQL: `SELECT PostGIS_Version();` no debe fallar.
- [ ] `config/app.php`: `'timezone' => 'America/La_Paz'`.
- [ ] `.env`: `QUEUE_CONNECTION=database` + migración de tabla `jobs`.

## Seeding
- [ ] Migración del catálogo de permisos (60 permisos desde `002-roles-permisos/plan.md`).
- [ ] Seeder `UnidadMedidaSeeder` (unidad, litro, metro, kilo).
- [ ] Seeder `MetodoPagoSeeder` (efectivo, tarjeta, QR, transferencia).
- [ ] Seeder `CategoriaTallerSeeder` (mecánica general, electricidad, neumáticos, diagnóstico, tuning, hojalatería).
- [ ] Seeder `RolSistemaSeeder` (8 roles + mapping de permisos según tabla en plan.md).
- [ ] Seeder `SuperAdminSeeder` (username `superadmin`, password temporal, solo visible en consola).
- [ ] Todos los seeds son idempotentes (`firstOrCreate`).

## Instalación de paquetes
- [ ] `composer require filament/filament:^3.2 spatie/laravel-permission:^6 laravel/socialite:^5 spatie/laravel-sluggable:^4 spatie/laravel-activitylog:^4 maatwebsite/excel pxlrbt/filament-excel:^2 guzzlehttp/guzzle:^7`
- [ ] Verificar `maatwebsite/excel` se instaló correctamente (v4.x). Si falla, reemplazar por `spatie/simple-excel` y quitar `pxlrbt/filament-excel`.
- [ ] `composer require --dev laravel/breeze:^2 pestphp/pest:^3 pestphp/pest-plugin-laravel:^3 barryvdh/laravel-debugbar:^3`
- [ ] `composer remove phpunit/phpunit --dev && php artisan pest:install --no-interaction`
- [ ] `npm install leaflet @fontsource/dm-sans` (Tailwind v4 no necesita `@tailwindcss/forms` — Preflight lo maneja)
- [ ] `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`
- [ ] `php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"`

## Configuración spatie/laravel-permission
- [ ] En `config/permission.php`: setear `'teams' => true` y `'team_foreign_key' => 'taller_id'`
- [ ] Publicar y personalizar migración si se requiere naming exacto en español
- [ ] Listener que ejecuta `setPermissionsTeamId()` en cada request del guard `sistema`

## Paneles Filament
- [ ] `php artisan filament:install --panels`
- [ ] `php artisan make:filament-panel admin`
- [ ] `php artisan make:filament-panel erp`
- [ ] Configurar `AdminPanelProvider` con paleta Awesomic, middleware, auth guard `sistema`
- [ ] Configurar `ErpPanelProvider` con paleta Awesomic, middleware, auth guard `sistema`
- [ ] Crear recursos base para cada panel (dashboard, sidebar skeleton)
- [ ] Widget `TenantSwitcher` en panel ERP
- [ ] Middleware `SetTallerActivo` registrado en Kernel para guard `sistema`

## Multi-tenant: BelongsToTaller
- [ ] Trait `App\Traits\BelongsToTaller` con global scope + auto-fill `taller_id` en creating
- [ ] Scope `App\Models\Scopes\BelongsToTallerScope`
- [ ] Método `sinScope()` con auditoría de `withoutGlobalScope`
- [ ] Middleware `SetTallerActivo` que setea `taller_activo_id` en sesión y configura `setPermissionsTeamId()`
- [ ] Redirección con mensaje si usuario no tiene acceso a ningún taller
- [ ] Tests Pest: usuario taller A no ve datos del taller B, creación auto-asigna `taller_id`, sinScope queda auditado

## Generación concurrente de códigos
- [ ] Action `App\Actions\SequentialCodeGenerator` con `FOR UPDATE` + reintentos
- [ ] Tests Pest: concurrencia simulada (2 procesos simultáneos) genera códigos distintos, deadlock se reintenta

## Rutas API y Web
- [ ] Crear `routes/api.php` con grupos de rutas para búsqueda, reseñas, favoritos
- [ ] Crear `routes/web.php` con rutas del marketplace (home, búsqueda, perfil, OAuth, dashboard, logout)
- [ ] Verificar que `RouteServiceProvider` o `bootstrap/app.php` cargan `routes/api.php`

## Helpers PostGIS
- [ ] Trait `App\Traits\HasGeolocation` con: boot (sincroniza geom en saving), `scopeCercanoA`, `scopeConDistanciaA`
- [ ] Cast `App\Casts\GeometryCast` (WKB/WKT ↔ lat/lon)

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
