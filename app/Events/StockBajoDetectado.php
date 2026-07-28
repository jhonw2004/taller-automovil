<?php

namespace App\Events;

use App\Models\Repuesto;

/**
 * Se dispara tras cada movimiento de inventario cuando `stock_actual <= stock_minimo`
 * (010-inventario-repuestos/plan.md). Sin listener todavia: el consumidor real (notificar a
 * usuarios con permiso de inventario del taller) es responsabilidad de `014-notificaciones`,
 * que no existe todavia — mismo patron de evento disparado antes que su consumidor que
 * `006-resenas-favoritos` dejó documentado para `orden_trabajo_id`.
 */
class StockBajoDetectado
{
    public function __construct(
        public readonly Repuesto $repuesto,
    ) {}
}
