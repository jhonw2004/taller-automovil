<?php

namespace App\Listeners\Notificaciones;

use App\Events\PagoRegistrado;
use App\Notifications\PagoRegistradoNotification;

/**
 * Consumidor real de `PagoRegistrado` (013-pagos), que se disparaba sin listener desde antes de
 * que `014-notificaciones` existiera.
 */
class NotificarPagoRegistradoListener
{
    public function handle(PagoRegistrado $event): void
    {
        PagoRegistradoNotification::enviar($event->pago);
    }
}
