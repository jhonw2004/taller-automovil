<?php

namespace App\Notifications;

use App\Actions\Notificaciones\CrearNotificacionAction;
use App\Models\Pago;
use App\Models\UsuarioSistema;

/**
 * Destinatario: "admin del taller (todos con permiso pagos.ver)" (014-plan.md). Consumida por
 * `App\Listeners\Notificaciones\NotificarPagoRegistradoListener`, enganchado al evento
 * `PagoRegistrado` que `013-pagos` ya dejaba disparándose sin listener.
 */
class PagoRegistradoNotification
{
    public static function enviar(Pago $pago): void
    {
        $nota = $pago->notaVenta;
        $destinatarios = UsuarioSistema::conPermisoEnTaller('pagos.ver', $nota->taller_id);

        foreach ($destinatarios as $usuario) {
            app(CrearNotificacionAction::class)->execute(
                tipo: 'pago.registrado',
                titulo: "Pago de Bs {$pago->monto} registrado en {$nota->codigo}",
                mensaje: "Se registró un pago de Bs {$pago->monto} en la nota de venta {$nota->codigo}.",
                usuarioSistemaId: $usuario->id,
                tallerId: $nota->taller_id,
                data: ['pago_id' => $pago->id, 'nota_venta_id' => $nota->id],
            );
        }
    }
}
