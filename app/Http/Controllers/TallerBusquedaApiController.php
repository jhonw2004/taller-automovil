<?php

namespace App\Http\Controllers;

use App\Models\Taller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * 005-marketplace-busqueda-perfil: endpoint público (sin autenticación) consumido por
 * `Alpine.store('search')`. Nunca ejecuta la búsqueda desde el servidor al renderizar la
 * página — solo desde aquí, vía fetch().
 */
class TallerBusquedaApiController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = Taller::query()->activosVisibles()->with(['categorias', 'horarios']);

        $tieneUbicacion = $request->filled('lat') && $request->filled('lon');

        if ($tieneUbicacion) {
            $lat = (float) $request->query('lat');
            $lon = (float) $request->query('lon');
            $radioMetros = (float) $request->query('radio', 10) * 1000;

            $query->conDistanciaA($lat, $lon)->cercanoA($lat, $lon, $radioMetros);
        }

        if ($request->filled('categoria')) {
            $query->whereHas(
                'categorias',
                fn ($categorias) => $categorias->where('categorias.slug', $request->query('categoria'))
            );
        }

        if ($request->filled('q')) {
            $query->where('nombre', 'ILIKE', '%'.$request->query('q').'%');
        }

        if ($request->filled('min_calificacion')) {
            $query->where('calificacion_promedio', '>=', (float) $request->query('min_calificacion'));
        }

        if ($request->boolean('open_now')) {
            $diaHoy = Carbon::now('America/La_Paz')->isoWeekday();
            $horaActual = Carbon::now('America/La_Paz')->format('H:i:s');

            $query->join('talleres_horarios as th_open', function ($join) use ($diaHoy) {
                $join->on('talleres.id', '=', 'th_open.taller_id')
                    ->where('th_open.dia_semana', $diaHoy)
                    ->where('th_open.cerrado', false);
            })
                ->whereTime('th_open.hora_apertura', '<=', $horaActual)
                ->whereTime('th_open.hora_cierre', '>=', $horaActual);

            // El join hace ambigua "id"/etc. sin calificar. Si `conDistanciaA` ya corrió arriba,
            // ya agregó "talleres.*" — agregarlo de nuevo duplicaría la columna en el resultado.
            if (! $tieneUbicacion) {
                $query->addSelect('talleres.*');
            }
        }

        match ($request->query('sort', 'cercania')) {
            'calificacion' => $query->orderByDesc('calificacion_promedio'),
            'resenas' => $query->orderByDesc('cantidad_resenas'),
            default => $tieneUbicacion ? $query->orderBy('distancia') : $query->orderByDesc('calificacion_promedio'),
        };

        $talleres = $query->get();

        return response()->json([
            'data' => $talleres->map->toSearchJsonResponse()->values(),
            'meta' => ['total' => $talleres->count()],
        ]);
    }
}
