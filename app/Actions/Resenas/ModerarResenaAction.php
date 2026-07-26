<?php

namespace App\Actions\Resenas;

use App\Events\ResenaGuardada;
use App\Exceptions\BusinessException;
use App\Models\Resena;
use App\Models\UsuarioSistema;
use Illuminate\Support\Facades\DB;

/**
 * Moderación (006-resenas-favoritos/spec.md): solo el super admin puede ocultar o marcar una
 * reseña como reportada; también se usa para volver a publicarla. Cambiar `estado` puede afectar
 * si la reseña cuenta en `calificacion_promedio` — reutiliza `ResenaGuardada` para disparar el
 * mismo recálculo que el flujo público de creación/edición, una sola fuente de verdad.
 */
class ModerarResenaAction
{
    private const ESTADOS_VALIDOS = ['PUBLICADA', 'OCULTA', 'REPORTADA'];

    public function execute(Resena $resena, string $estado, UsuarioSistema $actor): Resena
    {
        if (! $actor->tienePermiso('moderacion.resenas')) {
            throw new BusinessException('No tiene permiso para moderar reseñas.');
        }

        if (! in_array($estado, self::ESTADOS_VALIDOS, true)) {
            throw new BusinessException('Estado de moderación inválido.');
        }

        return DB::transaction(function () use ($resena, $estado, $actor) {
            $anterior = $resena->estado;

            $resena->update(['estado' => $estado]);

            event(new ResenaGuardada($resena, $resena->taller));

            activity()
                ->causedBy($actor)
                ->performedOn($resena)
                ->withProperties(['anterior' => $anterior, 'nuevo' => $estado])
                ->log('moderacion_resena');

            return $resena;
        });
    }
}
