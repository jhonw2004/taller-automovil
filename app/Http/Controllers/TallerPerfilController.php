<?php

namespace App\Http\Controllers;

use App\Models\Taller;
use Illuminate\View\View;

class TallerPerfilController extends Controller
{
    /**
     * `activosVisibles()` ya excluye INACTIVO/SUSPENDIDO/no-visibles/soft-deleted — `firstOrFail()`
     * sobre ese scope da 404 automático para cualquiera de esos casos (005-marketplace-busqueda-perfil/spec.md),
     * sin distinguir "no existe" de "existe pero no es público" (evita filtrar esa información).
     */
    public function show(string $slug): View
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

        return view('marketplace.workshops.show', [
            'taller' => $taller,
            'similares' => $similares,
        ]);
    }
}
