<?php

namespace App\Providers;

use App\Auth\UsuarioSistemaProvider;
use App\Livewire\NotificacionesBell;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('usuarios_sistema_eloquent', function ($app, array $config) {
            return new UsuarioSistemaProvider($app['hash'], $config['model']);
        });

        // El marketplace no tiene formulario de login propio (guard `web`, solo Google OAuth) —
        // sin esto, `Authenticate` intenta redirigir a `route('login')` (inexistente) y explota
        // con `RouteNotFoundException` en vez de un redirect limpio (006-resenas-favoritos,
        // primera ruta GET del marketplace protegida con `auth:web`: `/dashboard`).
        Authenticate::redirectUsing(fn () => route('home'));

        // Segundo bug real, mismo síntoma, encontrado en 014: `Illuminate\Auth\Middleware\Authenticate`
        // y `Illuminate\Auth\AuthenticationException` tienen CADA UNA su propio `redirectUsing()`
        // estático. El de arriba solo cubre el caso `!$request->expectsJson()` (páginas normales,
        // como `/dashboard`) — el middleware pasa `null` explícito cuando la request SÍ espera JSON
        // (cualquier fetch() con `Accept: application/json`, como el endpoint de
        // `/notificaciones/{id}/marcar-leida`), y `bootstrap/app.php` limita `shouldRenderJsonWhen`
        // a rutas `api/*`, así que ese `null` cae en el fallback de la EXCEPCIÓN, no del middleware,
        // que sin este segundo override intenta `route('login')` (inexistente) igual. Sin esto,
        // cualquier fetch() no autenticado a una ruta fuera de `/api/*` explota con 500 en vez de
        // un 401 limpio.
        AuthenticationException::redirectUsing(fn () => route('home'));

        // 014-notificaciones: campana del topbar, montada vía renderHook(TOPBAR_END) en ambos
        // PanelProvider (admin/erp). Se registra por nombre (no por FQCN directo en `@livewire`)
        // siguiendo el mismo patrón documentado por Filament para integrar Livewire de terceros
        // en un render hook.
        Livewire::component('notificaciones-bell', NotificacionesBell::class);

        $this->configurarRateLimiters();

        // 020-seguridad-produccion §C: en producción, cualquier request que llegue por HTTP
        // se sirve/enlaza como HTTPS. No se toca en local/testing para no romper `php artisan serve`
        // sin certificado.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }

    /**
     * 020-seguridad-produccion §A: límites de tasa nombrados y centralizados — reemplaza los
     * literales `throttle:N,1` dispersos por `routes/web.php`/`routes/api.php` (mismos valores,
     * ahora en un solo sitio) y agrega límites a rutas públicas que hoy no tenían ninguno
     * (`solicitudes-taller`, `talleres.buscar`/`talleres.show`).
     */
    private function configurarRateLimiters(): void
    {
        RateLimiter::for('publico-lectura', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        RateLimiter::for('publico-escritura', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('busqueda-api', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        RateLimiter::for(
            'marketplace-escritura',
            fn (Request $request) => Limit::perMinute(10)->by($request->user('web')?->id ?: $request->ip())
        );

        RateLimiter::for(
            'notificaciones',
            fn (Request $request) => Limit::perMinute(30)->by(
                $request->user('web')?->id ?: $request->user('sistema')?->id ?: $request->ip()
            )
        );
    }
}
