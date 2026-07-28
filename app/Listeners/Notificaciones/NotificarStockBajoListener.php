<?php

namespace App\Listeners\Notificaciones;

use App\Events\StockBajoDetectado;
use App\Notifications\StockBajoNotification;

/**
 * Consumidor real de `StockBajoDetectado` (010-inventario-repuestos), que se disparaba sin
 * listener desde antes de que `014-notificaciones` existiera.
 */
class NotificarStockBajoListener
{
    public function handle(StockBajoDetectado $event): void
    {
        StockBajoNotification::enviar($event->repuesto);
    }
}
