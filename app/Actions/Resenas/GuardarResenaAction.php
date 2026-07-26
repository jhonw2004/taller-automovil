<?php

namespace App\Actions\Resenas;

use App\Events\ResenaGuardada;
use App\Models\Resena;
use App\Models\Taller;
use App\Models\UsuarioMarketplace;
use Illuminate\Support\Facades\DB;

/**
 * Upsert (006-resenas-favoritos/spec.md): un usuario tiene a lo sumo una reseña por taller
 * (`UNIQUE usuario_marketplace_id, taller_id`). Si ya existe, se actualiza calificacion/comentario
 * sin tocar `estado` — editar la propia reseña no debe revertir silenciosamente una moderación
 * previa (OCULTA/REPORTADA); eso solo lo cambia el super admin via `ModerarResenaAction`.
 */
class GuardarResenaAction
{
    public function execute(UsuarioMarketplace $usuario, Taller $taller, int $calificacion, ?string $comentario): Resena
    {
        return DB::transaction(function () use ($usuario, $taller, $calificacion, $comentario) {
            $resena = Resena::query()
                ->where('usuario_marketplace_id', $usuario->id)
                ->where('taller_id', $taller->id)
                ->first();

            if ($resena) {
                $resena->update([
                    'calificacion' => $calificacion,
                    'comentario' => $comentario,
                ]);
            } else {
                $resena = Resena::create([
                    'usuario_marketplace_id' => $usuario->id,
                    'taller_id' => $taller->id,
                    'calificacion' => $calificacion,
                    'comentario' => $comentario,
                    'estado' => 'PUBLICADA',
                ]);
            }

            event(new ResenaGuardada($resena, $taller));

            return $resena;
        });
    }
}
