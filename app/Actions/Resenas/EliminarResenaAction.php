<?php

namespace App\Actions\Resenas;

use App\Events\ResenaEliminada;
use App\Exceptions\BusinessException;
use App\Models\Resena;
use App\Models\UsuarioMarketplace;
use Illuminate\Support\Facades\DB;

class EliminarResenaAction
{
    public function execute(Resena $resena, UsuarioMarketplace $usuario): void
    {
        if ($resena->usuario_marketplace_id !== $usuario->id) {
            throw new BusinessException('No puede eliminar una reseña que no le pertenece.');
        }

        DB::transaction(function () use ($resena) {
            $taller = $resena->taller;

            $resena->delete();

            event(new ResenaEliminada($taller));
        });
    }
}
