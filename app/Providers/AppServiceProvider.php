<?php

namespace App\Providers;

use App\Auth\UsuarioSistemaProvider;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

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
    }
}
