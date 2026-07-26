<?php

namespace App\Events;

use App\Models\Resena;
use App\Models\Taller;

/**
 * Se dispara al crear/editar una reseña o al cambiar su `estado` vía moderación — en los tres
 * casos el promedio/cantidad de reseñas del taller puede verse afectado (006-resenas-favoritos/plan.md).
 */
class ResenaGuardada
{
    public function __construct(
        public readonly Resena $resena,
        public readonly Taller $taller,
    ) {}
}
