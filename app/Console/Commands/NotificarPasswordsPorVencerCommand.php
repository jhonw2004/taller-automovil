<?php

namespace App\Console\Commands;

use App\Models\CredencialSistema;
use App\Models\Notificacion;
use App\Notifications\PasswordExpiradaNotification;
use Illuminate\Console\Command;

/**
 * Único de los 10 triggers de 014-plan.md que depende del paso del tiempo, no de una acción de
 * usuario ("NOW() > password_expires_at - 7 days"): corre diario vía `routes/console.php`
 * (`Schedule::command(...)->daily()`). Evita duplicar la notificación cada día durante toda la
 * ventana de 7 días comprobando que no exista ya una `password.expirada` creada desde el último
 * cambio de contraseña (si el usuario la cambia, `password_changed_at` avanza y el ciclo se
 * reinicia de forma natural).
 */
class NotificarPasswordsPorVencerCommand extends Command
{
    protected $signature = 'notificaciones:passwords-por-vencer';

    protected $description = 'Notifica a los usuarios sistema cuya contraseña expira en los próximos 7 días.';

    public function handle(): int
    {
        $notificados = 0;

        CredencialSistema::query()
            ->with('usuarioSistema')
            ->whereNotNull('password_expires_at')
            ->whereBetween('password_expires_at', [now(), now()->addDays(7)])
            ->each(function (CredencialSistema $credencial) use (&$notificados) {
                $usuario = $credencial->usuarioSistema;

                if (! $usuario || ! $usuario->activo) {
                    return;
                }

                $desde = $credencial->password_changed_at ?? $credencial->created_at;

                $yaNotificado = Notificacion::query()
                    ->where('usuario_sistema_id', $usuario->id)
                    ->where('tipo', 'password.expirada')
                    ->where('created_at', '>=', $desde)
                    ->exists();

                if ($yaNotificado) {
                    return;
                }

                PasswordExpiradaNotification::enviar($usuario);
                $notificados++;
            });

        $this->info("Notificaciones de password por vencer enviadas: {$notificados}.");

        return self::SUCCESS;
    }
}
