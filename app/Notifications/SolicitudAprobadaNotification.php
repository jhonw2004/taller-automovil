<?php

namespace App\Notifications;

use App\Actions\Notificaciones\CrearNotificacionAction;
use App\Models\SolicitudTaller;
use App\Models\UsuarioSistema;

/**
 * Destinatario literal de 014-plan.md: "usuario_sistema_id del Super Admin que la procesó" — el
 * solicitante es público y sin cuenta (004-solicitud-alta-taller: seguimiento por `token_publico`,
 * sin identidad), así que no hay ningún usuario real del lado del solicitante a quien notificar
 * in-app; esta notificación es el registro/confirmación para el propio actor que aprobó.
 */
class SolicitudAprobadaNotification
{
    public static function enviar(SolicitudTaller $solicitud, UsuarioSistema $actor): void
    {
        app(CrearNotificacionAction::class)->execute(
            tipo: 'solicitud.aprobada',
            titulo: "Solicitud de {$solicitud->taller_nombre} aprobada",
            mensaje: "La solicitud de alta de \"{$solicitud->taller_nombre}\" pasó a estado {$solicitud->estado}.",
            usuarioSistemaId: $actor->id,
            tallerId: $solicitud->taller_id,
            data: ['solicitud_taller_id' => $solicitud->id],
        );
    }
}
