<?php

namespace App\Notifications;

use App\Actions\Notificaciones\CrearNotificacionAction;
use App\Models\OrdenTrabajo;
use App\Models\UsuarioSistema;

/**
 * Destinatarios: "usuario_sistema_id del empleado asignado + admin del taller" (014-plan.md,
 * cualquier transición). Admin = roles `owner`/`shop-admin` (mismo criterio de rol que
 * `ResenaNuevaNotification`). Se deduplica por id: el empleado asignado puede ser también el owner
 * en un taller chico.
 */
class OrdenCambioEstadoNotification
{
    private const ROLES_ADMIN = ['owner', 'shop-admin'];

    public static function enviar(OrdenTrabajo $orden): void
    {
        $destinatarios = UsuarioSistema::conRolEnTaller(self::ROLES_ADMIN, $orden->taller_id);

        $empleado = $orden->empleadoAsignado;

        if ($empleado && $empleado->tieneAcceso() && ! $destinatarios->contains('id', $empleado->usuario_sistema_id)) {
            $destinatarios->push(UsuarioSistema::find($empleado->usuario_sistema_id));
        }

        foreach ($destinatarios->filter() as $usuario) {
            app(CrearNotificacionAction::class)->execute(
                tipo: 'orden.cambio_estado',
                titulo: "Orden {$orden->codigo} cambió a {$orden->estado}",
                mensaje: "La orden de trabajo {$orden->codigo} cambió de estado a {$orden->estado}.",
                usuarioSistemaId: $usuario->id,
                tallerId: $orden->taller_id,
                ordenTrabajoId: $orden->id,
            );
        }
    }
}
