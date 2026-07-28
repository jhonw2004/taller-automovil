<?php

namespace App\Notifications;

use App\Actions\Notificaciones\CrearNotificacionAction;
use App\Models\Repuesto;
use App\Models\UsuarioSistema;

/**
 * Destinatario: "usuarios con permiso inventario.ver del taller" (014-plan.md). Consumida por
 * `App\Listeners\Notificaciones\NotificarStockBajoListener`, enganchado al evento
 * `StockBajoDetectado` que `010-inventario-repuestos` ya dejaba disparándose sin listener.
 */
class StockBajoNotification
{
    public static function enviar(Repuesto $repuesto): void
    {
        $destinatarios = UsuarioSistema::conPermisoEnTaller('inventario.ver', $repuesto->taller_id);

        foreach ($destinatarios as $usuario) {
            app(CrearNotificacionAction::class)->execute(
                tipo: 'stock.bajo',
                titulo: "Stock bajo: {$repuesto->nombre} — disponible: {$repuesto->stock_actual}",
                mensaje: "El repuesto {$repuesto->nombre} está en o por debajo de su stock mínimo ({$repuesto->stock_minimo}).",
                usuarioSistemaId: $usuario->id,
                tallerId: $repuesto->taller_id,
                data: ['repuesto_id' => $repuesto->id],
            );
        }
    }
}
