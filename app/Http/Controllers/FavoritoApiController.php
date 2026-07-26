<?php

namespace App\Http\Controllers;

use App\Models\Favorito;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 006-resenas-favoritos: sin Action dedicada — alta/baja de favorito es un `firstOrCreate`/`delete`
 * idempotente sin más regla de negocio (plan.md no define una Action para esto, a diferencia de
 * reseñas). Requieren guard `web` (rutas con `auth:web` + `throttle:10,1`).
 */
class FavoritoApiController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'taller_id' => ['required', 'integer', 'exists:talleres,id'],
        ]);

        $favorito = Favorito::query()->firstOrCreate([
            'usuario_marketplace_id' => $request->user()->id,
            'taller_id' => $data['taller_id'],
        ]);

        return response()->json(['data' => [
            'id' => $favorito->id,
            'taller_id' => $favorito->taller_id,
        ]]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'taller_id' => ['required', 'integer'],
        ]);

        Favorito::query()
            ->where('usuario_marketplace_id', $request->user()->id)
            ->where('taller_id', $data['taller_id'])
            ->delete();

        return response()->json(['message' => 'Favorito eliminado.']);
    }
}
