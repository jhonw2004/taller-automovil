# Plan — Seguridad y Hardening de Producción

Sin paquetes nuevos salvo uno explícitamente justificado (`spatie/laravel-backup`, ver §E) — mismo criterio minimalista de `constitution.md §1`: todo lo demás se resuelve con primitivas nativas de Laravel (`RateLimiter`, middleware propio, config) para no ampliar la superficie de dependencias de un spec cuyo propósito es justamente reducir superficie de ataque.

## A. Rate limiting centralizado

### `App\Providers\AppServiceProvider::boot()` — nuevos `RateLimiter::for(...)`

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('publico-lectura', fn ($request) =>
    Limit::perMinute(60)->by($request->ip()));

RateLimiter::for('publico-escritura', fn ($request) =>
    Limit::perMinute(5)->by($request->ip()));

RateLimiter::for('busqueda-api', fn ($request) =>
    Limit::perMinute(30)->by($request->ip()));

RateLimiter::for('marketplace-escritura', fn ($request) =>
    Limit::perMinute(10)->by(optional($request->user('web'))->id ?: $request->ip()));

RateLimiter::for('notificaciones', fn ($request) =>
    Limit::perMinute(30)->by($request->user('web')?->id ?: $request->user('sistema')?->id ?: $request->ip()));
```

`busqueda-api` y `marketplace-escritura` reemplazan los literales `throttle:30,1`/`throttle:10,1` ya existentes en `routes/api.php` (mismos valores, ahora nombrados y centralizados — sin cambio de comportamiento, solo de mantenibilidad). El login de Filament no se toca: su rate limit nativo (`WithRateLimiting`, 5/60s) sigue siendo el mecanismo, este plan no lo duplica.

### `routes/web.php` — aplicar los limiters nuevos

```php
Route::prefix('solicitudes-taller')->name('solicitudes.')->group(function () {
    Route::get('/nueva', [SolicitudTallerController::class, 'create'])->name('create')
        ->middleware('throttle:publico-lectura');
    Route::post('/', [SolicitudTallerController::class, 'store'])->name('store')
        ->middleware('throttle:publico-escritura');
    Route::get('/{token}', [SolicitudTallerController::class, 'seguimiento'])->name('seguimiento')
        ->middleware('throttle:publico-lectura');
    Route::post('/{token}/cancelar', [SolicitudTallerController::class, 'cancelar'])->name('cancelar')
        ->middleware('throttle:publico-escritura');
});

Route::get('/talleres/buscar', [TallerBusquedaController::class, 'index'])
    ->name('talleres.buscar')->middleware('throttle:publico-lectura');
Route::get('/talleres/{slug}', [TallerPerfilController::class, 'show'])
    ->name('talleres.show')->middleware('throttle:publico-lectura');

Route::middleware('auth:web,sistema')
    ->post('/notificaciones/{notificacion}/marcar-leida', [NotificacionController::class, 'marcarLeida'])
    ->name('notificaciones.marcar-leida')
    ->middleware('throttle:notificaciones');
```

`GET /` (home) queda deliberadamente **sin** `publico-lectura` explícito de ruta individual — se cubre por un throttle de "catch-all" más permisivo aplicado como middleware global de grupo `web` (ver abajo), para no tener que enumerar cada ruta futura.

### Middleware global de piso mínimo

`bootstrap/app.php` agrega un throttle de piso (muy permisivo, red de seguridad) a **todo** el grupo `web`, independiente de los throttles específicos por ruta:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->web(append: [
        \App\Http\Middleware\SecurityHeaders::class,
    ]);
    $middleware->throttleWithRedis(); // si Redis está disponible en producción; si no, se omite (ver nota)
})
```

**Nota**: el proyecto usa `CACHE_STORE=database` (no Redis) según `resume.md` — `RateLimiter` funciona igual sobre el store de caché configurado, no requiere Redis. `throttleWithRedis()` solo aplica si en producción se decide instalar Redis para caché/colas (fuera de alcance de este spec); mientras tanto el rate limiting usa el store por defecto sin cambios.

## B. Límites de tamaño y agotamiento de recursos

### `TallerResource.php` — `FileUpload` con límites explícitos

```php
FileUpload::make('logo_url')
    ->label('Logo')
    ->disk('public')
    ->directory('talleres/logos')
    ->image()
    ->imageResizeMode('cover')
    ->maxSize(2048) // KB — 2 MB
    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
    ->columnSpanFull(),
```

Mismo patrón a aplicar a cualquier `FileUpload` futuro del proyecto (avatares de usuario, si se agregan) — se documenta como convención en `constitution.md §1` al cerrar este spec (no se edita `constitution.md` en este documento, se deja como tarea de cierre en `tasks.md`).

### Paginación con tope

Filament ya limita `per_page` a las opciones declaradas en `->paginated([10, 25, 50])` de cada tabla — se audita que ningún Resource declare una opción de página no acotada (ej. `->paginated([10, 25, 50, 'all'])`, que Filament sí permite y que aquí se prohíbe explícitamente). El endpoint `GET /api/talleres/search` ya no acepta `per_page` como parámetro del cliente (hardcodeado en el controller) — se documenta esa decisión existente como intencional, no como brecha.

### Timeouts a nivel de infraestructura (no de código Laravel)

Documentado como runbook de despliegue, no como código de la aplicación:

- **PHP-FPM**: `request_terminate_timeout = 30` (segundos) en el pool de producción.
- **Nginx/Apache**: `proxy_read_timeout`/`Timeout` en 35s (ligeramente por encima del de PHP-FPM, para que sea PHP quien corte primero con un error controlado).
- **PostgreSQL**: `statement_timeout` seteado a nivel del **rol de aplicación**, no de sesión individual (persiste sin depender de que el código Laravel lo configure en cada conexión):
  ```sql
  ALTER ROLE taller_app SET statement_timeout = '30s';
  ```
  Esto evita que una query mal formada (o un intento deliberado de query costosa) agote el pool de conexiones de Postgres — el propio motor mata la query a los 30s en vez de dejarla correr indefinidamente.

### Almacenamiento no ejecutable

`Storage::putFile()` (disco `public`, ya en uso) genera nombres aleatorios por defecto — se agrega un test que sube dos archivos con el mismo nombre original y confirma que no colisionan ni sobreescriben. A nivel de servidor web (Nginx), se documenta la directiva que impide ejecutar PHP dentro de `public/storage/`:

```nginx
location ^~ /storage/ {
    location ~ \.php$ { deny all; }
}
```

## C. Cabeceras de seguridad HTTP

### `App\Http\Middleware\SecurityHeaders` (nuevo)

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'geolocation=(self), camera=(), microphone=(), payment=()'
        );

        if (app()->environment('production')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // Report-Only primero (ver spec.md §C y "Decisiones pendientes") — no bloquea nada,
        // solo recolecta violaciones reales antes de decidir la política final.
        $response->headers->set(
            'Content-Security-Policy-Report-Only',
            "default-src 'self'; img-src 'self' data: *.tile.openstreetmap.org; "
            ."style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; "
            ."connect-src 'self'"
        );

        return $response;
    }
}
```

`geolocation=(self)` explícito porque `navigator.geolocation` (botón "usar mi ubicación", `004`/`005`) lo necesita. `'unsafe-inline'` en `script-src`/`style-src` es una concesión documentada: Alpine.js y Filament/Livewire dependen de atributos/estilos inline; endurecerlo a nonces es trabajo posterior informado por los reportes de la política `Report-Only` (ver "Decisiones pendientes" en `spec.md`).

### Forzar HTTPS en producción

```php
// AppServiceProvider::boot()
if ($this->app->environment('production')) {
    URL::forceScheme('https');
}
```

Registrado junto al middleware `SecurityHeaders` en el grupo global (`bootstrap/app.php`), antes de cualquier ruta.

### `config/session.php`

```php
'secure' => env('SESSION_SECURE_COOKIE') ?? app()->environment('production'),
```

Cambia el default de `null` (sin flag `Secure` si el operador olvida la variable) a `true` automático en producción, sin requerir que `.env` de producción lo declare explícitamente para estar seguro por default. `SESSION_LIFETIME` de `.env.example` se reduce de `10080` (7 días) a `480` (8 horas) como default más razonable para un panel operativo (ERP/Admin); el marketplace puede mantener sesiones más largas si se decide diferenciar guards, pero eso es una extensión futura fuera de este spec — por ahora un único valor conservador para ambos guards.

## D. Base de datos: rol de mínimo privilegio

Runbook de producción (no migración Laravel — se ejecuta una vez, como superusuario, al aprovisionar la base de datos):

```sql
CREATE ROLE taller_app WITH LOGIN PASSWORD '...' NOSUPERUSER NOCREATEDB NOCREATEROLE;
GRANT CONNECT ON DATABASE taller_produccion TO taller_app;
GRANT USAGE, CREATE ON SCHEMA public TO taller_app;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO taller_app;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO taller_app;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO taller_app;
ALTER ROLE taller_app SET statement_timeout = '30s';
```

`CREATE EXTENSION postgis` (única operación que sí requiere superusuario) se ejecuta **una vez**, manualmente o con un rol distinto, antes de que la aplicación se conecte con `taller_app` — `017-infraestructura-sistema` ya documenta esa migración como la primera en correr; este spec agrega la nota de que en producción no debe correr con el mismo rol que la aplicación usa después.

`.env.example` se actualiza: `DB_USERNAME=taller_app` (en vez de `root`) con un comentario explicando por qué.

## E. Backups

**Decisión propuesta (pendiente de confirmación del usuario, ver `spec.md`):** `spatie/laravel-backup` — mismo fabricante que `spatie/laravel-sluggable`/`spatie/simple-excel` ya en uso, soporta disco local y S3 sin código adicional, notifica por email/Slack en fallo, e incluye `backup:monitor` para alertar si un backup está desactualizado o es demasiado pequeño (detecta un dump corrupto/vacío, no solo que el comando corrió).

```bash
composer require spatie/laravel-backup
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

`config/backup.php`: incluir solo `pg_dump` de la base de datos (no todo `storage/app`, que puede tener archivos grandes de solicitudes/logos ya respaldados por el disco/S3 de producción por separado).

`routes/console.php`:

```php
Schedule::command('backup:clean')->daily()->at('01:30');
Schedule::command('backup:run')->daily()->at('02:00');
Schedule::command('backup:monitor')->daily()->at('03:00');
```

**Alternativa sin paquete nuevo** (si la decisión pendiente resuelve "no instalar nada nuevo"): script `pg_dump` + cron del sistema operativo, documentado en `plan.md` como comando exacto (`pg_dump -Fc taller_produccion > backup-$(date +%F).dump`) con retención vía `find storage/backups -mtime +14 -delete`. Ambas opciones quedan documentadas; se implementa la que el usuario confirme.

## F. Auditoría de dependencias

Sin nuevo paquete — `composer` y `npm` ya traen `audit` nativo:

```bash
composer audit
npm audit --audit-level=high
```

Documentado en `README.md` como paso obligatorio antes de cada release (no hay pipeline de CI en el repo todavía — `resume.md`, decimonovena sesión — así que por ahora es un paso manual, no automatizado; si en el futuro se agrega CI, este es el primer chequeo a automatizar).

## G. Comando de verificación pre-despliegue

`app/Console/Commands/SecurityCheckProduccion.php`:

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class SecurityCheckProduccion extends Command
{
    protected $signature = 'security:check-produccion';
    protected $description = 'Verifica que la configuración de producción cumple el checklist de seguridad de 020-seguridad-produccion';

    public function handle(): int
    {
        $checks = [
            'APP_DEBUG=false' => config('app.debug') === false,
            'APP_ENV=production' => app()->environment('production'),
            'APP_URL usa https' => str_starts_with(config('app.url'), 'https://'),
            'SESSION_SECURE_COOKIE=true' => config('session.secure') === true,
            'LOG_LEVEL distinto de debug' => config('logging.channels.stack.level') !== 'debug',
            'DB_USERNAME no es root/postgres' => ! in_array(config('database.connections.pgsql.username'), ['root', 'postgres'], true),
        ];

        $fallos = array_filter($checks, fn ($ok) => ! $ok);

        foreach ($checks as $descripcion => $ok) {
            $this->line(($ok ? '<fg=green>✓</>' : '<fg=red>✗</>')." {$descripcion}");
        }

        if ($fallos !== []) {
            $this->error(count($fallos).' verificación(es) fallida(s). No desplegar hasta corregir.');

            return self::FAILURE;
        }

        $this->info('Checklist de seguridad de producción: todo correcto.');

        return self::SUCCESS;
    }
}
```

Se corre como último paso del runbook de despliegue, antes de poner tráfico real sobre la instancia.

## H'. Verificación de carga real y de mínimo privilegio (decidido con el usuario, 2026-07-31)

- **Rate limiting bajo concurrencia real**: además de los tests Pest (verifican que el `RateLimiter` dispara, secuencial), se ejecuta un smoke test manual con `php artisan serve` + un loop de `curl` lanzado en paralelo desde Bash (`for i in $(seq 1 N); do curl ... & done; wait`) contra `POST /solicitudes-taller`/`GET /talleres/buscar`, confirmando que bajo ráfaga concurrente real la mayoría de las respuestas por encima del límite son `429`, no `500`/timeout. Decisión explícita: sin instalar `ab`/`wrk`/`k6`/`autocannon` — coherente con el criterio minimalista del proyecto; una herramienta de carga dedicada queda como candidata solo si se necesita un benchmark riguroso antes de un lanzamiento público real.
- **Rol de base de datos de mínimo privilegio**: el `CREATE ROLE taller_app ...`/`GRANT`/`ALTER ROLE ... SET statement_timeout` de §D se ejecuta de verdad contra el PostgreSQL local (vía `psql`), y `.env.testing` se apunta temporalmente a ese rol para correr la suite completa de Pest. Si algún test falla por falta de un permiso (ej. una operación que asumía privilegios de superusuario sin necesitarlos), es un hallazgo real que se corrige antes de cerrar esta feature — no una suposición documentada sin probar.

## H. Tests nuevos (`tests/Feature/Seguridad/`)

- `RateLimitingTest.php`: cada endpoint listado en `spec.md §A` devuelve `429` al superar su límite (usa `Carbon::setTestNow()`/múltiples requests seguidas, no depende de tiempo real).
- `CabecerasSeguridadTest.php`: una request a `/` trae las cabeceras de `SecurityHeaders`; en `testing` no trae HSTS (solo `production`).
- `MassAssignmentTest.php`: reflexión sobre todas las clases en `app/Models/`, ninguna con `$guarded === []`.
- `SubidaArchivosTest.php`: `FileUpload` de `TallerResource` rechaza >2MB y rechaza mime type fuera de whitelist (Livewire test real, `UploadedFile::fake()`).
- `AislamientoMultiTenantTest.php`: suite consolidada de IDOR descrita en `spec.md §G`, cross-taller 404 para cada Resource operativo.
- `SecurityCheckProduccionCommandTest.php`: el comando de `§G` devuelve `FAILURE` con config insegura y `SUCCESS` con config correcta (usa `config()->set(...)` dentro del test, no un entorno real).
