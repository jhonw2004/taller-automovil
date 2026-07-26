<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\View\View;

/**
 * 005-marketplace-busqueda-perfil/plan.md: renderiza la vista con Alpine. No ejecuta la query de
 * búsqueda — eso lo hace el cliente contra `GET /api/talleres/search` al montar la página.
 */
class TallerBusquedaController extends Controller
{
    public function index(): View
    {
        return view('marketplace.search.index', [
            'categorias' => Categoria::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }
}
