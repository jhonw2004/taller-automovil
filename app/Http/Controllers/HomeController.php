<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Taller;
use Illuminate\View\View;

/**
 * Home (`/`) — 016-ui-design-system/plan.md: layout `app`, hero + banda de categorías + stats +
 * la misma experiencia de búsqueda (filtros + mapa + lista) que `/talleres/buscar`, montada por
 * Alpine. El controlador no ejecuta la búsqueda en sí (eso lo hace `TallerBusquedaApiController`),
 * solo agrega datos estáticos/agregados baratos para el hero y la banda de categorías.
 */
class HomeController extends Controller
{
    public function index(): View
    {
        $categorias = Categoria::where('activo', true)->orderBy('nombre')->get();

        return view('marketplace.home', [
            'categorias' => $categorias,
            'stats' => [
                'talleres' => Taller::activosVisibles()->count(),
                'categorias' => $categorias->count(),
                'calificacionPromedio' => round((float) Taller::activosVisibles()->avg('calificacion_promedio'), 1),
            ],
        ]);
    }
}
