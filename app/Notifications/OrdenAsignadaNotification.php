<?php

namespace App\Notifications;

use App\Actions\Notificaciones\CrearNotificacionAction;
use App\Models\OrdenTrabajo;

/**
 * Destinatario: "usuario_sistema_id vinculado al empleado (si tiene acceso)" (014-plan.md) — si el
 * empleado asignado no tiene cuenta de sistema, no hay a quién notificar in-app, se omite en
 * silencio (no es un error de negocio, es un estado normal — no todo empleado tiene acceso).
 */
class OrdenAsignadaNotification
{
    public static function enviar(OrdenTrabajo $orden): void
    {
        $empleado = $orden->empleadoAsignado;

        if (! $empleado || ! $empleado->tieneAcceso()) {
            return;
        }

        app(CrearNotificacionAction::class)->execute(
            tipo: 'orden.asignada',
            titulo: "Nueva orden {$orden->codigo} asignada",
            mensaje: "Se te asignó la orden de trabajo {$orden->codigo}.",
            usuarioSistemaId: $empleado->usuario_sistema_id,
            tallerId: $orden->taller_id,
            ordenTrabajoId: $orden->id,
        );
    }
}
