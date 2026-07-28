<?php

namespace App\Observers;

use App\Models\NotaVenta;
use App\Notifications\NotaEmitidaNotification;

/**
 * Toda nota se crea en estado `EMITIDA` (`CrearNotaVentaDesdeOrdenAction`/`CrearNotaVentaDirectaAction`,
 * nunca `::create()` directo desde Filament) — no hace falta distinguir el estado en `created()`.
 */
class NotaVentaObserver
{
    public function created(NotaVenta $nota): void
    {
        NotaEmitidaNotification::enviar($nota);
    }
}
