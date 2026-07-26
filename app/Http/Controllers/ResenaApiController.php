<?php

namespace App\Http\Controllers;

use App\Actions\Resenas\EliminarResenaAction;
use App\Actions\Resenas\GuardarResenaAction;
use App\Http\Requests\Resenas\GuardarResenaRequest;
use App\Models\Resena;
use App\Models\Taller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 006-resenas-favoritos: endpoints JSON consumidos por `Alpine.data('resenaForm')` en el perfil
 * público. Requieren guard `web` (rutas registradas con `auth:web` + `throttle:10,1`).
 */
class ResenaApiController extends Controller
{
    public function storeOrUpdate(GuardarResenaRequest $request, GuardarResenaAction $action): JsonResponse
    {
        $taller = Taller::findOrFail($request->validated('taller_id'));

        $resena = $action->execute(
            $request->user(),
            $taller,
            $request->validated('calificacion'),
            $request->validated('comentario'),
        );

        return response()->json(['data' => [
            'id' => $resena->id,
            'taller_id' => $resena->taller_id,
            'calificacion' => $resena->calificacion,
            'comentario' => $resena->comentario,
            'estado' => $resena->estado,
        ]]);
    }

    public function destroy(Resena $resena, Request $request, EliminarResenaAction $action): JsonResponse
    {
        $action->execute($resena, $request->user());

        return response()->json(['message' => 'Reseña eliminada.']);
    }
}
