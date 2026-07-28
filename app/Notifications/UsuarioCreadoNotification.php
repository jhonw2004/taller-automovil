<?php

namespace App\Notifications;

use App\Actions\Notificaciones\CrearNotificacionAction;
use App\Models\Taller;
use App\Models\UsuarioSistema;

/**
 * Destinatario: el propio usuario recién creado (014-plan.md, trigger de 008-empleados-usuarios-erp).
 */
class UsuarioCreadoNotification
{
    public static function enviar(UsuarioSistema $usuario, Taller $taller): void
    {
        app(CrearNotificacionAction::class)->execute(
            tipo: 'usuario.creado',
            titulo: "Tu cuenta en {$taller->nombre} fue creada. Cambia tu contraseña.",
            mensaje: "Se creó tu cuenta de acceso al ERP de {$taller->nombre}. Debes cambiar tu contraseña temporal en el primer inicio de sesión.",
            usuarioSistemaId: $usuario->id,
            tallerId: $taller->id,
        );
    }
}
