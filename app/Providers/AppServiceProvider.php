<?php

namespace App\Providers;

use App\Auth\UsuarioSistemaProvider;
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
    }
}
