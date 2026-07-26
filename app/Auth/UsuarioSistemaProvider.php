<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

/**
 * Provider custom para el guard `sistema`: además de validar el hash de password, rechaza
 * el login si el usuario está inactivo o bloqueado por intentos fallidos (spec 001).
 *
 * No se puede lograr esto con el `EloquentUserProvider` estándar porque `bloqueado_hasta`
 * vive en `CredencialSistema` (relación 1:1), no en el propio modelo `UsuarioSistema`.
 */
class UsuarioSistemaProvider extends EloquentUserProvider
{
    public function validateCredentials(UserContract $user, #[\SensitiveParameter] array $credentials)
    {
        if (! $user->activo) {
            return false;
        }

        $credencial = $user->credencialSistema;

        if ($credencial?->bloqueado_hasta?->isFuture()) {
            return false;
        }

        return parent::validateCredentials($user, $credentials);
    }
}
