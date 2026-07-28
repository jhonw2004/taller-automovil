<?php

namespace App\Notifications;

use App\Actions\Notificaciones\CrearNotificacionAction;
use App\Models\NotaVenta;
use App\Models\UsuarioSistema;

/**
 * Destinatario: "admin del taller (todos con permiso notas.ver)" (014-plan.md).
 */
class NotaEmitidaNotification
{
    public static function enviar(NotaVenta $nota): void
    {
        $destinatarios = UsuarioSistema::conPermisoEnTaller('notas.ver', $nota->taller_id);

        $cliente = $nota->cliente;
        $titulo = "Nueva nota {$nota->codigo} emitida".($cliente ? " por {$cliente->nombreCompleto}" : '');

        foreach ($destinatarios as $usuario) {
            app(CrearNotificacionAction::class)->execute(
                tipo: 'nota.emitida',
                titulo: $titulo,
                mensaje: "Se emitió la nota de venta {$nota->codigo} por un total de Bs {$nota->total}.",
                usuarioSistemaId: $usuario->id,
                tallerId: $nota->taller_id,
                data: ['nota_venta_id' => $nota->id],
            );
        }
    }
}
