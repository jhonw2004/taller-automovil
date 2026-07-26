<?php

namespace App\Listeners\Talleres;

use App\Events\ResenaEliminada;
use App\Events\ResenaGuardada;
use App\Models\Resena;
use App\Models\Taller;

/**
 * Recalculo sincrono de `calificacion_promedio`/`cantidad_resenas` (constitution.md §3.12): no
 * implementa `ShouldQueue`, corre en el mismo request/transaccion que disparo el evento. Solo
 * cuenta reseñas `estado = 'PUBLICADA'` (006-resenas-favoritos/spec.md) — `SoftDeletes` en
 * `Resena` ya excluye las eliminadas del query por defecto.
 */
class RecalcularCalificacionTallerListener
{
    public function handleResenaGuardada(ResenaGuardada $event): void
    {
        $this->recalcular($event->taller);
    }

    public function handleResenaEliminada(ResenaEliminada $event): void
    {
        $this->recalcular($event->taller);
    }

    private function recalcular(Taller $taller): void
    {
        $stats = Resena::query()
            ->where('taller_id', $taller->id)
            ->where('estado', 'PUBLICADA')
            ->selectRaw('COUNT(*) as cantidad, COALESCE(AVG(calificacion), 0) as promedio')
            ->first();

        // `forceFill()`, no `update()`: `calificacion_promedio`/`cantidad_resenas` son columnas
        // derivadas, deliberadamente fuera de `Taller::$fillable` (nunca deben aceptarse desde un
        // formulario o request) — `update()` las habría ignorado en silencio por protección de
        // mass-assignment.
        $taller->forceFill([
            'calificacion_promedio' => round((float) $stats->promedio, 2),
            'cantidad_resenas' => (int) $stats->cantidad,
        ])->save();
    }
}
