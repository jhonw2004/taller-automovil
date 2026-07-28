<?php

namespace App\Notifications;

use App\Actions\Notificaciones\CrearNotificacionAction;
use App\Models\UsuarioSistema;

/**
 * Destinatario: el propio usuario (014-plan.md, trigger de 001-identidad-autenticacion, 7 días
 * antes de `password_expires_at`). Disparada por el comando programado
 * `notificaciones:passwords-por-vencer` (App\Console\Commands), no por un evento de modelo — es el
 * único de los 10 tipos que depende del paso del tiempo, no de una acción de usuario.
 */
class PasswordExpiradaNotification
{
    public static function enviar(UsuarioSistema $usuario): void
    {
        app(CrearNotificacionAction::class)->execute(
            tipo: 'password.expirada',
            titulo: 'Tu contraseña expirará en 7 días',
            mensaje: 'Tu contraseña del sistema expirará en 7 días. Cámbiala antes de esa fecha para no perder acceso.',
            usuarioSistemaId: $usuario->id,
        );
    }
}
