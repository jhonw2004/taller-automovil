# Plan — Infraestructura del Sistema

## 0. Prerrequisitos del sistema

### PostgreSQL con PostGIS

Antes de migrar, PostgreSQL debe tener la extensión PostGIS habilitada:

```sql
CREATE EXTENSION IF NOT EXISTS postgis;
```

Verificar con: `SELECT PostGIS_Version();`. Sin esto, `php artisan migrate` falla porque no reconoce `GEOMETRY(Point,4326)`.

### Migración de extensión

```php
// database/migrations/0000_00_00_000001_create_extension_postgis.php
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
    }

    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS postgis');
    }
};
```

Esta migración debe ser la primera en ejecutarse (timestamp más bajo).

### Timezone

```php
// config/app.php
'timezone' => 'America/La_Paz',
```

Sin esto, `Carbon::now()` devuelve UTC y los cálculos de "abierto ahora" fallan por -4 horas de diferencia.

### Queue

```bash
# .env
QUEUE_CONNECTION=database

php artisan queue:table
php artisan migrate
```

La tabla `jobs` existe. No se necesita worker corriendo en el MVP (el recálculo de calificación es síncrono por regla 12 de constitution). La tabla está lista para cuando se necesiten notificaciones asíncronas en el futuro.

## 1. Paquetes — lista completa de instalación

### Composer (PHP)

```
composer require filament/filament:^3.2
composer require spatie/laravel-permission:^6
composer require laravel/socialite:^5
composer require laravel/breeze:^2 --dev
composer require spatie/laravel-sluggable:^4
composer require spatie/laravel-activitylog:^4
composer require maatwebsite/excel  # v4.x compatible Laravel 11+; si falla composer, usar spatie/simple-excel como fallback
composer require pxlrbt/filament-excel:^2  # requiere maatwebsite/excel instalado antes
composer require pestphp/pest:^3 --dev
composer require pestphp/pest-plugin-laravel:^3 --dev
composer require barryvdh/laravel-debugbar:^3 --dev

# guzzle ya viene con laravel/socialite como dependencia, pero se explicita:
composer require guzzlehttp/guzzle:^7

# Reemplazar phpunit por pest:
composer remove phpunit/phpunit --dev
composer require pestphp/pest:^3 --dev
composer require pestphp/pest-plugin-laravel:^3 --dev
php artisan pest:install --no-interaction
```

### NPM

```bash
npm install leaflet
npm install @fontsource/dm-sans
# @tailwindcss/forms no se necesita en Tailwind v4 — Preflight + utility classes manejan forms nativamente
```

### Post-instalación

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"
php artisan filament:install --panels
php artisan make:filament-panel admin
php artisan make:filament-panel erp
```

## 2. Paneles Filament

### Estructura de directorios

```
app/Filament/
  Admin/
    AdminPanelProvider.php     ← configura panel /admin
    Dashboard.php             ← widgets: total talleres, solicitudes pendientes
    Resources/
      TallerResource.php      ← global, todos los talleres
      SolicitudResource.php
      CategoriaResource.php
      MetodoPagoResource.php
      UnidadMedidaResource.php
      ResenaResource.php      ← moderación
      PermisoResource.php     ← catálogo global
      RolGlobalResource.php
      AuditoriaEventoResource.php
  Erp/
    ErpPanelProvider.php       ← configura panel /erp
    Dashboard.php             ← widgets: órdenes activas, ingresos, stock bajo
    Resources/
      TallerResource.php      ← solo datos del propio taller
      ClienteResource.php
      VehiculoResource.php
      EmpleadoResource.php
      UsuarioSistemaResource.php
      ServicioResource.php
      RepuestoResource.php
      ProveedorResource.php
      OrdenTrabajoResource.php
      NotaVentaResource.php
      PagoResource.php
      RolResource.php         ← roles personalizados del taller
```

### AdminPanelProvider.php

```php
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->login()
            ->authGuard('sistema')
            ->colors([
                'primary' => '#09090b',    // obsidian
                'secondary' => '#18181b',  // graphite
                'danger' => '#ff5a00',     // ember
                'warning' => '#ff5a00',
                'success' => '#22c55e',
                'info' => '#52525b',       // steel
                'gray' => fn () => [
                    50 => '#f4f4f5',  // paper
                    100 => '#ececee', // cloud
                    200 => '#d4d4d8', // mist
                    300 => '#a1a1aa', // ash
                    400 => '#71717a', // fog
                    500 => '#52525b', // steel
                    600 => '#3f3f46', // iron
                    700 => '#27272a', // slate
                    800 => '#18181b', // graphite
                    900 => '#09090b', // obsidian
                ],
            ])
            ->middleware([
                'auth:sistema',
                'throttle:5,1',
                SetTallerActivo::class,  // middleware multi-tenant
            ])
            ->authMiddleware([
                'auth:sistema',
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->spatiePermission()  // filament/spatie-laravel-permission-plugin
            ->viteTheme('resources/css/filament/admin/theme.css');
    }
}
```

### ErpPanelProvider.php

```php
class ErpPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('erp')
            ->path('erp')
            ->login()
            ->authGuard('sistema')
            ->colors([ /* misma paleta que admin */ ])
            ->middleware([
                'auth:sistema',
                'throttle:5,1',
                SetTallerActivo::class,
            ])
            ->authMiddleware([
                'auth:sistema',
            ])
            ->discoverResources(in: app_path('Filament/Erp/Resources'), for: 'App\\Filament\\Erp\\Resources')
            ->discoverWidgets(in: app_path('Filament/Erp/Widgets'), for: 'App\\Filament\\Erp\\Widgets')
            ->tenantOwnership(Ownership::new(
                ownerColumn: 'taller_id',
                ownerRelationship: 'taller',
            ))
            ->spatiePermission()
            ->viteTheme('resources/css/filament/erp/theme.css');
    }
}
```

### Tenant Switcher

El Tenant Switcher es un widget que aparece en el topbar del panel `/erp` si el usuario tiene más de un `taller_id` activo en sus asignaciones de rol.

```php
// App\Filament\Erp\Widgets\TenantSwitcher.php
class TenantSwitcher extends Widget
{
    public function render()
    {
        $talleres = auth()->user()
            ->asignacionesRol()
            ->with('taller')
            ->where('activo', true)
            ->get()
            ->pluck('taller.nombre', 'taller.id');

        return view('filament.erp.widgets.tenant-switcher', [
            'talleres' => $talleres,
            'activo' => session('taller_activo_id'),
        ]);
    }
}
```

Registro en `ErpPanelProvider`:

```php
$panel->widgets([
    TenantSwitcher::class,
]);
```

## 3. Multi-tenant: BelongsToTaller

### Middleware `SetTallerActivo`

```php
// App\Http\Middleware\SetTallerActivo.php
class SetTallerActivo
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (! $user) {
            return $next($request);
        }

        // Obtener taller_ids activos del usuario
        $tallerIds = $user->asignacionesRol()
            ->where('activo', true)
            ->pluck('taller_id')
            ->unique()
            ->filter()
            ->values();

        if ($tallerIds->isEmpty()) {
            auth()->logout();
            session()->invalidate();
            return redirect('/erp/login')
                ->with('error', 'No tienes acceso a ningún taller.');
        }

        if ($tallerIds->count() === 1) {
            session(['taller_activo_id' => $tallerIds[0]]);
        }
        // Si tiene varios, mantener el de la sesión o forzar selector

        // Setear team_id para spatie/laravel-permission
        app(\Spatie\Permission\PermissionRegistrar::class)
            ->setPermissionsTeamId(session('taller_activo_id'));

        return $next($request);
    }
}
```

### Trait `BelongsToTaller`

```php
// App\Traits\BelongsToTaller.php
trait BelongsToTaller
{
    protected static function bootBelongsToTaller(): void
    {
        static::addGlobalScope(new BelongsToTallerScope);

        static::creating(function ($model) {
            if (empty($model->taller_id)) {
                $model->taller_id = session('taller_activo_id');
            }
        });
    }

    public function taller(): BelongsTo
    {
        return $this->belongsTo(Taller::class);
    }
}
```

### Global Scope `BelongsToTallerScope`

```php
// App\Models\Scopes\BelongsToTallerScope.php
class BelongsToTallerScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tallerId = session('taller_activo_id');

        if ($tallerId) {
            $builder->where($model->getTable().'.taller_id', $tallerId);
        }
    }
}
```

### Auditoría de `withoutGlobalScope`

```php
// App\Traits\BelongsToTaller.php — método sinScope()
public static function sinScope(\Closure $callback): mixed
{
    $result = static::withoutGlobalScope(BelongsToTallerScope::class)
        ->when(true, fn ($q) => $callback($q));

    activity()
        ->event('sin_global_scope')
        ->causedBy(auth()->user())
        ->withProperties([
            'model' => static::class,
            'taller_id_accedido' => session('taller_activo_id'),
        ])
        ->log('Acceso sin filtro de taller');

    return $result;
}
```

## 4. Generación concurrente de códigos

### Patrón: SequentialCodeGenerator

```php
// App\Actions\SequentialCodeGenerator.php
class SequentialCodeGenerator
{
    /**
     * @param string $tallerId
     * @param string $prefix   Ej: 'OT', 'NV'
     * @param string $table    Ej: 'ordenes_trabajo', 'notas_venta'
     * @param string $column   Ej: 'codigo'
     * @param int    $retries  Reintentos ante deadlock
     */
    public static function generate(
        string $tallerId,
        string $prefix,
        string $table,
        string $column = 'codigo',
        int $retries = 3
    ): string {
        $year = now()->format('Y');

        return DB::transaction(function () use ($tallerId, $prefix, $year, $table, $column, $retries) {
            // Obtener el último código del taller para el año actual
            $last = DB::table($table)
                ->where('taller_id', $tallerId)
                ->where($column, 'LIKE', "{$prefix}-{$year}-%")
                ->orderBy($column, 'desc')
                ->lockForUpdate()    // ← FOR UPDATE bloquea la fila
                ->first();

            $nextNumber = $last
                ? (int) substr($last->$column, -3) + 1
                : 1;

            $codigo = sprintf("%s-%s-%03d", $prefix, $year, $nextNumber);

            return $codigo;
        }, 5);  // 5 reintentos por deadlock
    }
}
```

### Uso en cada feature

```php
// En CrearOrdenTrabajo::execute():
$codigo = SequentialCodeGenerator::generate(
    tallerId: $tallerId,
    prefix: 'OT',
    table: 'ordenes_trabajo',
);
```

El `CHECK (codigo UNIQUE por taller)` en la base de datos es la última barrera: si por algún motivo el generador falla, PostgreSQL rechaza el duplicado y la transacción hace rollback.

## 5. Rutas API

### `routes/api.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TallerBusquedaApiController;
use App\Http\Controllers\Api\ResenaApiController;
use App\Http\Controllers\Api\FavoritoApiController;

/*
|--------------------------------------------------------------------------
| Marketplace API (público con rate limiting)
|--------------------------------------------------------------------------
*/

Route::middleware(['throttle:30,1'])->group(function () {
    Route::get('/talleres/search', [TallerBusquedaApiController::class, 'search']);
});

/*
|--------------------------------------------------------------------------
| Marketplace API (autenticado guard web)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:web', 'throttle:10,1'])->group(function () {
    Route::post('/resenas', [ResenaApiController::class, 'storeOrUpdate']);
    Route::delete('/resenas/{id}', [ResenaApiController::class, 'destroy']);
    Route::post('/favoritos', [FavoritoApiController::class, 'store']);
    Route::post('/favoritos/delete', [FavoritoApiController::class, 'destroy']);
});
```

### `routes/web.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Marketplace\HomeController;
use App\Http\Controllers\Marketplace\TallerBusquedaController;
use App\Http\Controllers\Marketplace\TallerPerfilController;

/*
|--------------------------------------------------------------------------
| Marketplace — público
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/talleres/buscar', [TallerBusquedaController::class, 'index']);
Route::get('/talleres/{slug}', [TallerPerfilController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Marketplace — OAuth
|--------------------------------------------------------------------------
*/

Route::middleware(['guest'])->group(function () {
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect']);
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);
});

Route::middleware(['auth:web'])->group(function () {
    Route::post('/auth/logout', [LogoutController::class, 'logout']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

## 6. Helpers PostGIS

### `App\Traits\HasGeolocation`

```php
trait HasGeolocation
{
    public static function bootHasGeolocation(): void
    {
        static::saving(function ($model) {
            if ($model->lat !== null && $model->lon !== null) {
                $model->geom = DB::raw(
                    "ST_SetSRID(ST_MakePoint({$model->lon}, {$model->lat}), 4326)"
                );
            }
        });
    }

    public function scopeCercanoA(Builder $query, float $lat, float $lon, float $radioMetros): Builder
    {
        // Obtener el nombre de la tabla
        $table = $query->getModel()->getTable();
        return $query->whereRaw(
            "ST_DWithin({$table}.geom, ST_SetSRID(ST_MakePoint(?, ?), 4326), ?)",
            [$lon, $lat, $radioMetros]
        );
    }

    public function scopeConDistanciaA(Builder $query, float $lat, float $lon): Builder
    {
        $table = $query->getModel()->getTable();
        return $query->selectRaw(
            "ST_DistanceSphere({$table}.geom, ST_SetSRID(ST_MakePoint(?, ?), 4326)) as distancia",
            [$lon, $lat]
        );
    }
}
```

## 7. Migración de reemplazo de `talleres`

```
database/migrations/
  2026_07_19_174015_create_talleres_table.php   ← prototipo existente (NO usar)
  2026_08_01_000001_replace_talleres_table.php  ← migración de reemplazo
```

La migración de reemplazo debe:
1. `DROP` la tabla `talleres` existente (no hay datos valiosos — es prototipo).
2. Crear la nueva tabla `talleres` según el esquema de `003-gestion-talleres/plan.md`.
3. Crear `categorias`, `talleres_categorias`, `talleres_horarios`.

```php
// 2026_08_01_000001_replace_talleres_table.php
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('talleres');

        Schema::create('talleres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propietario_usuario_sistema_id')
                ->nullable()->constrained('usuarios_sistema');
            $table->string('nombre', 255);
            $table->string('slug', 255)->unique();
            $table->text('descripcion')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('nit', 50)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->double('lat');
            $table->double('lon');
            $table->geometry('geom', 'Point', 4326);
            $table->string('osm_id', 255)->nullable()->unique();
            $table->text('logo_url')->nullable();
            $table->string('estado', 20)->default('ACTIVO');
            $table->boolean('visible_en_mapa')->default(false);
            $table->decimal('calificacion_promedio', 3, 2)->default(0);
            $table->unsignedInteger('cantidad_resenas')->default(0);
            $table->timestampsTz();
            $table->softDeletes();

            // CHECK constraints
            $table->check('lat BETWEEN -90 AND 90');
            $table->check('lon BETWEEN -180 AND 180');
            $table->check("estado IN ('ACTIVO','INACTIVO','SUSPENDIDO')");
        });

        DB::statement('CREATE INDEX idx_talleres_geom ON talleres USING GIST (geom)');
        DB::statement('CREATE INDEX idx_talleres_estado ON talleres (estado)');
        DB::statement('CREATE INDEX idx_talleres_visible ON talleres (visible_en_mapa)');

        // Crear tablas hijas (categorias, talleres_categorias, talleres_horarios)
        // según plan.md de 003-gestion-talleres
        // ...
    }

    public function down(): void
    {
        Schema::dropIfExists('talleres_horarios');
        Schema::dropIfExists('talleres_categorias');
        Schema::dropIfExists('categorias');
        Schema::dropIfExists('talleres');
    }
};
```

## 8. Seeding inicial

### `database/seeders/DatabaseSeeder.php`

```php
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PostgisExtensionSeeder::class,       // ya ejecutado en migración
            UnidadMedidaSeeder::class,
            MetodoPagoSeeder::class,
            CategoriaTallerSeeder::class,
            PermisoSeeder::class,                // catálogo global de 60 permisos
            RolSistemaSeeder::class,             // roles de sistema + mapping de permisos
            SuperAdminSeeder::class,             // primer usuario SUPER_ADMIN
        ]);
    }
}
```

### `PermisoSeeder`

Lee la lista definitiva de `002-roles-permisos/plan.md` y crea cada permiso con `firstOrCreate(['slug' => ...])`.

```php
class PermisoSeeder extends Seeder
{
    protected array $permisos = [
        // ERP — taller
        ['modulo' => 'taller', 'nombre' => 'Ver taller', 'slug' => 'taller.ver'],
        ['modulo' => 'taller', 'nombre' => 'Editar taller', 'slug' => 'taller.editar'],
        ['modulo' => 'taller', 'nombre' => 'Configurar taller', 'slug' => 'taller.configurar'],
        ['modulo' => 'taller', 'nombre' => 'Cambiar propietario', 'slug' => 'taller.cambiar_propietario'],
        // ... todos los permisos del catálogo
    ];

    public function run(): void
    {
        foreach ($this->permisos as $p) {
            Permiso::firstOrCreate(['slug' => $p['slug']], $p);
        }
    }
}
```

### `RolSistemaSeeder` — role-permission mapping

Define qué permisos tiene cada rol de sistema. Los roles personalizados (creados por admin de taller) no tienen mapping fijo — el admin los configura manualmente desde Filament.

| Rol | Permisos incluidos |
|---|---|
| **SUPER_ADMIN** | Todos los permisos (se asigna vía `->givePermissionTo(Permiso::all())`) |
| **OWNER** | `taller.*`, `clientes.*`, `vehiculos.*`, `empleados.*`, `usuarios.gestionar`, `servicios.*`, `repuestos.*`, `inventario.*`, `proveedores.*`, `ordenes.*`, `notas.*`, `pagos.*`, `roles.*`, `auditoria.ver`, `notificaciones.ver` |
| **SHOP_ADMIN** | `taller.ver`, `taller.editar`, `taller.configurar`, `clientes.*`, `vehiculos.*`, `empleados.ver`, `empleados.crear`, `empleados.editar`, `servicios.*`, `repuestos.*`, `inventario.ver`, `inventario.ajustar`, `proveedores.*`, `ordenes.*` (excepto `ordenes.anular` y `ordenes.eliminar`), `notas.*` (excepto `notas.anular` y `notas.eliminar`), `pagos.*`, `roles.ver`, `notificaciones.ver` |
| **MECANICO** | `ordenes.ver`, `ordenes.editar` (solo cambiar estado de sus órdenes asignadas), `inventario.ver` |
| **CAJERO** | `notas.ver`, `notas.crear`, `notas.editar`, `pagos.ver`, `pagos.registrar`, `clientes.ver`, `clientes.crear` |
| **RECEPCIONISTA** | `ordenes.ver`, `ordenes.crear`, `clientes.ver`, `clientes.crear`, `vehiculos.ver`, `vehiculos.crear` |
| **SUPERVISOR** | `ordenes.*` (excepto `ordenes.eliminar`), `notas.*` (excepto `notas.eliminar`), `pagos.ver`, `empleados.ver`, `inventario.ver`, `clientes.*`, `vehiculos.*` |
| **VENDEDOR** | `notas.ver`, `notas.crear`, `pagos.registrar`, `clientes.ver`, `clientes.crear`, `repuestos.ver` |
| **MARKETPLACE_USER** | `marketplace.resenar`, `marketplace.favoritos` |

```php
class RolSistemaSeeder extends Seeder
{
    public function run(): void
    {
        // SUPER_ADMIN
        $admin = Rol::firstOrCreate([
            'slug' => 'super-admin',
            'taller_id' => null,
            'es_sistema' => true,
        ], ['nombre' => 'Super Admin']);
        $admin->syncPermissions(Permiso::all());

        // OWNER
        $owner = Rol::firstOrCreate([
            'slug' => 'owner',
            'taller_id' => null,
            'es_sistema' => true,
        ], ['nombre' => 'Propietario']);
        $owner->syncPermissions(Permiso::whereIn('slug', [
            'taller.ver','taller.editar','taller.configurar','taller.cambiar_propietario',
            'clientes.ver','clientes.crear','clientes.editar','clientes.eliminar',
            'vehiculos.ver','vehiculos.crear','vehiculos.editar','vehiculos.eliminar',
            'empleados.ver','empleados.crear','empleados.editar','empleados.eliminar',
            'usuarios.ver','usuarios.gestionar',
            'servicios.ver','servicios.crear','servicios.editar','servicios.eliminar',
            'repuestos.ver','repuestos.crear','repuestos.editar','repuestos.eliminar',
            'inventario.ver','inventario.ajustar',
            'proveedores.ver','proveedores.crear','proveedores.editar','proveedores.eliminar',
            'ordenes.ver','ordenes.crear','ordenes.editar','ordenes.anular','ordenes.eliminar',
            'notas.ver','notas.crear','notas.editar','notas.anular','notas.eliminar',
            'pagos.ver','pagos.registrar','pagos.anular',
            'roles.ver','roles.crear','roles.editar','roles.eliminar',
            'auditoria.ver',
            'notificaciones.ver',
        ])->get());

        // SHOP_ADMIN, MECANICO, CAJERO, etc. — mismo patrón con sus permisos
        // ...
    }
}
```

### `SuperAdminSeeder`

```php
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $identidad = Identidad::firstOrCreate(
            ['email' => 'superadmin@tallerautomoviles.bo'],
            ['tipo' => 'SISTEMA', 'estado' => 'ACTIVO']
        );

        $usuario = UsuarioSistema::firstOrCreate(
            ['username' => 'superadmin'],
            [
                'identidad_id' => $identidad->id,
                'nombre' => 'Super Admin',
                'activo' => true,
            ]
        );

        $passwordTemporal = Str::password(16, symbols: true);

        CredencialSistema::updateOrCreate(
            ['usuario_sistema_id' => $usuario->id],
            [
                'password_hash' => Hash::make($passwordTemporal),
                'debe_cambiar_password' => true,
                'password_expires_at' => now()->addDays(90),
            ]
        );

        $usuario->assignRole('super-admin');

        // Mostrar la contraseña temporal UNA SOLA VEZ en consola
        $this->command->warn("=== SUPER ADMIN CREADO ===");
        $this->command->warn("Username: superadmin");
        $this->command->warn("Password: {$passwordTemporal}");
        $this->command->warn("==========================");
    }
}
```

## 9. Autocomplete / Search en Filament Resources

Patrón estándar para selects con búsqueda en el ERP:

```php
// Ejemplo en OrdenTrabajoResource — campo cliente
Forms\Components\Select::make('cliente_id')
    ->label('Cliente')
    ->searchable()
    ->searchDebounce(300)
    ->getSearchResultsUsing(fn (string $search) =>
        Cliente::where('taller_id', session('taller_activo_id'))
            ->where(function ($q) use ($search) {
                $q->where('nombre', 'ilike', "%{$search}%")
                  ->orWhere('codigo', 'ilike', "%{$search}%");
            })
            ->limit(10)
            ->pluck('nombre', 'id')
    )
    ->getOptionLabelUsing(fn ($value) =>
        Cliente::find($value)?->nombre ?? '—'
    )
    ->reactive()
    ->afterStateUpdated(fn ($set) => $set('vehiculo_id', null))  // reset vehiculo al cambiar cliente
    ->required(),

// Vehículos filtrados por cliente seleccionado
Forms\Components\Select::make('vehiculo_id')
    ->label('Vehículo')
    ->searchable()
    ->options(fn (\Filament\Forms\Get $get) =>
        $get('cliente_id')
            ? Vehiculo::where('cliente_id', $get('cliente_id'))
                ->where('activo', true)
                ->pluck('placa', 'id')
            : []
    )
    ->required(),
```

## 10. Dependencias con otras features

| Feature | Relación |
|---|---|
| `001-identidad-autenticacion` | Los guards y middleware `SetTallerActivo` dependen de los modelos de usuario sistema |
| `003-gestion-talleres` | `BelongsToTaller` se aplica a `Taller` y sus tablas hijas |
| `005-marketplace-busqueda-perfil` | Las rutas API de búsqueda y los helpers PostGIS se usan aquí |
| `006-resenas-favoritos` | Las rutas API de reseñas y favoritos se definen aquí |
| `007` a `015` | Todos los modelos del ERP usan `BelongsToTaller` |
| `016-ui-design-system` | Los temas CSS de Filament se referencian desde los PanelProviders |
