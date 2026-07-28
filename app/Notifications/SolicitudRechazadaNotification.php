<?php

namespace App\Notifications;

use App\Actions\Notificaciones\CrearNotificacionAction;
use App\Models\SolicitudTaller;
use App\Models\UsuarioSistema;

/**
 * Mismo criterio de destinatario que SolicitudAprobadaNotification: el super admin que procesó la
 * solicitud (el solicitante público no tiene cuenta a la que notificar in-app).
 */
class SolicitudRechazadaNotification
{
    public static function enviar(SolicitudTaller $solicitud, UsuarioSistema $actor): void
    {
        app(CrearNotificacionAction::class)->execute(
            tipo: 'solicitud.rechazada',
            titulo: "Solicitud de {$solicitud->taller_nombre} rechazada: {$solicitud->motivo_rechazo}",
            mensaje: "La solicitud de alta de \"{$solicitud->taller_nombre}\" fue rechazada. Motivo: {$solicitud->motivo_rechazo}",
            usuarioSistemaId: $actor->id,
            data: ['solicitud_taller_id' => $solicitud->id],
        );
    }
}
