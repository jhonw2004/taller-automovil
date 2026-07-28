<?php

namespace App\Listeners\Notificaciones;

use App\Events\ResenaGuardada;
use App\Notifications\ResenaNuevaNotification;

/**
 * `ResenaGuardada` se dispara tanto en creación como en edición de una reseña, y también al
 * moderarla (`ModerarResenaAction` la reutiliza para el mismo recálculo de calificación) — pero
 * `resena.nueva` (014-plan.md) es solo para reseñas nuevas. `wasRecentlyCreated` distingue el caso
 * sin tocar la firma del evento: sigue siendo true en la misma request que `Resena::create()`,
 * false en cualquier `->update()` posterior (edición o moderación).
 */
class NotificarResenaNuevaListener
{
    public function handle(ResenaGuardada $event): void
    {
        if (! $event->resena->wasRecentlyCreated) {
            return;
        }

        ResenaNuevaNotification::enviar($event->resena, $event->taller);
    }
}
