<?php

namespace App\Events;

use App\Models\Pago;

/**
 * Se dispara al confirmar un pago contra una nota de venta (013-pagos, trigger `pago.registrado`
 * en `014-notificaciones/plan.md`: destinatario "admin del taller con permiso `pagos.ver`"). Sin
 * listener todavía — el consumidor real es `014-notificaciones`, que no existe todavía, mismo
 * patrón de evento disparado antes que su consumidor que `StockBajoDetectado` (010) dejó
 * documentado.
 */
class PagoRegistrado
{
    public function __construct(
        public readonly Pago $pago,
    ) {}
}
