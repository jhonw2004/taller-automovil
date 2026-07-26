<?php

use App\Models\UsuarioMarketplace;
use App\Models\UsuarioSistema;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | Dos identidades no intercambiables (spec 001-identidad-autenticacion):
    | guard `web` = usuario marketplace (login solo Google), guard `sistema` =
    | usuario sistema (username/contraseña, personal de taller + super admin).
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'usuarios_sistema'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'usuarios_marketplace',
        ],

        'sistema' => [
            'driver' => 'session',
            'provider' => 'usuarios_sistema',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'usuarios_marketplace' => [
            'driver' => 'eloquent',
            'model' => UsuarioMarketplace::class,
        ],

        'usuarios_sistema' => [
            // Driver custom (registrado en AppServiceProvider): valida además `activo` y
            // `bloqueado_hasta`, que un `eloquent` provider estándar no puede comprobar
            // porque viven en la relación CredencialSistema, no en UsuarioSistema.
            'driver' => 'usuarios_sistema_eloquent',
            'model' => UsuarioSistema::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | Fuera de alcance del MVP: no hay recuperación de contraseña por email para
    | usuario sistema (ver spec 001, "Fuera de alcance"), y el marketplace no
    | tiene contraseña. No se usa el broker nativo de Laravel (no existe tabla
    | `password_reset_tokens`); se deja vacío para no referenciar una tabla
    | inexistente si algún paquete de terceros intenta resolverlo.
    |
    */

    'passwords' => [],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
