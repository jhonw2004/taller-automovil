<?php

namespace App\Http\Controllers;

use App\Models\Favorito;
use App\Models\Resena;
use App\Models\Taller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TallerPerfilController extends Controller
{
    /**
     * `activosVisibles()` ya excluye INACTIVO/SUSPENDIDO/no-visibles/soft-deleted — `firstOrFail()`
     * sobre ese scope da 404 automático para cualquiera de esos casos (005-marketplace-busqueda-perfil/spec.md),
     * sin distinguir "no existe" de "existe pero no es público" (evita filtrar esa información).
     */
    public function show(string $slug, Request $request): View
    {
        $taller = Taller::activosVisibles()
            ->with(['categorias', 'horarios' => fn ($query) => $query->orderBy('dia_semana')])
            ->where('slug', $slug)
            ->firstOrFail();

        $categoriaIds = $taller->categorias->pluck('id');

        $similares = $categoriaIds->isEmpty()
            ? collect()
            : Taller::activosVisibles()
                ->whereHas('categorias', fn ($query) => $query->whereIn('categorias.id', $categoriaIds))
                ->where('id', '!=', $taller->id)
                ->limit(3)
                ->get();

        $usuario = $request->user();

        $miResena = $usuario
            ? Resena::query()->where('taller_id', $taller->id)->where('usuario_marketplace_id', $usuario->id)->first()
            : null;

        // Excluye la propia reseña de la lista general: ya se muestra aparte en
        // `<x-marketplace.resena-form>` ("Mi reseña"), listarla dos veces sería redundante.
        $resenas = Resena::query()
            ->where('taller_id', $taller->id)
            ->where('estado', 'PUBLICADA')
            ->when($miResena, fn ($query) => $query->where('id', '!=', $miResena->id))
            ->with('usuario')
            ->latest()
            ->get();

        $esFavorito = $usuario
            ? Favorito::query()->where('taller_id', $taller->id)->where('usuario_marketplace_id', $usuario->id)->exists()
            : false;

        return view('marketplace.workshops.show', [
            'taller' => $taller,
            'similares' => $similares,
            'resenas' => $resenas,
            'miResena' => $miResena,
            'esFavorito' => $esFavorito,
        ]);
    }
}
