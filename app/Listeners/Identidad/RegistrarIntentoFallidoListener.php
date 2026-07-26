<?php

namespace App\Listeners\Identidad;

use App\Models\UsuarioSistema;
use Illuminate\Auth\Events\Failed;

/**
 * 5 intentos fallidos consecutivos bloquean el usuario sistema 15 minutos (spec 001,
 * constitution.md §7). Solo aplica al guard `sistema`: el marketplace no tiene contraseña,
 * por lo que `Failed` nunca se dispara para el guard `web` en el flujo normal.
 */
class RegistrarIntentoFallidoListener
{
    private const MAX_INTENTOS = 5;

    private const MINUTOS_BLOQUEO = 15;

    public function handle(Failed $event): void
    {
        if ($event->guard !== 'sistema' || ! $event->user instanceof UsuarioSistema) {
            return;
        }

        $credencial = $event->user->credencialSistema;

        if (! $credencial) {
            return;
        }

        $intentos = $credencial->intentos_fallidos + 1;

        $credencial->update([
            'intentos_fallidos' => $intentos,
            'bloqueado_hasta' => $intentos >= self::MAX_INTENTOS
                ? now()->addMinutes(self::MINUTOS_BLOQUEO)
                : $credencial->bloqueado_hasta,
        ]);
    }
}
