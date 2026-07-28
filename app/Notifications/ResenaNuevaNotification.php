<?php

namespace App\Notifications;

use App\Actions\Notificaciones\CrearNotificacionAction;
use App\Models\Resena;
use App\Models\Taller;
use App\Models\UsuarioSistema;

/**
 * Destinatario: "admin del taller (propietario + shop admin)" (014-plan.md) — por rol, no por
 * permiso (a diferencia de nota.emitida/pago.registrado/stock.bajo), porque no hay un permiso
 * `resenas.ver` dedicado en el catálogo de 002.
 */
class ResenaNuevaNotification
{
    private const ROLES_ADMIN = ['owner', 'shop-admin'];

    public static function enviar(Resena $resena, Taller $taller): void
    {
        $destinatarios = UsuarioSistema::conRolEnTaller(self::ROLES_ADMIN, $taller->id);

        foreach ($destinatarios as $usuario) {
            app(CrearNotificacionAction::class)->execute(
                tipo: 'resena.nueva',
                titulo: "Nueva reseña de {$resena->usuario->nombre} — {$resena->calificacion} estrellas",
                mensaje: "Recibiste una nueva reseña de {$resena->calificacion} estrellas en {$taller->nombre}.",
                usuarioSistemaId: $usuario->id,
                tallerId: $taller->id,
                data: ['resena_id' => $resena->id],
            );
        }
    }
}
