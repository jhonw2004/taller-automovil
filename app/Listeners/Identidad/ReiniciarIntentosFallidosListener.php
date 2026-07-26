<?php

namespace App\Listeners\Identidad;

use App\Models\UsuarioSistema;
use Illuminate\Auth\Events\Login;

/**
 * Login exitoso reinicia el contador de intentos fallidos (spec 001) y fuerza el cambio
 * de contraseña si expiró (`NOW() > password_expires_at`).
 */
class ReiniciarIntentosFallidosListener
{
    public function handle(Login $event): void
    {
        if ($event->guard !== 'sistema' || ! $event->user instanceof UsuarioSistema) {
            return;
        }

        $event->user->update(['ultimo_acceso_at' => now()]);

        $credencial = $event->user->credencialSistema;

        if (! $credencial) {
            return;
        }

        $credencial->update([
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'debe_cambiar_password' => $credencial->debe_cambiar_password
                || $credencial->password_expires_at->isPast(),
        ]);
    }
}
