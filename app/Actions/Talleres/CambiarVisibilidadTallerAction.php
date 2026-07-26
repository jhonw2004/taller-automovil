<?php

namespace App\Actions\Talleres;

use App\Exceptions\BusinessException;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use Illuminate\Support\Facades\DB;

/**
 * Auditoría vía el helper `activity()` de spatie/laravel-activitylog (misma tabla genérica que
 * `App\Traits\BelongsToTaller::sinScope()`), no la tabla `auditoria_eventos` de 015-auditoria:
 * esa tabla todavía no existe (015 depende de 003, se implementa después en el orden del
 * proyecto). Revisar si conviene migrar este log cuando se implemente 015.
 */
class CambiarVisibilidadTallerAction
{
    public function execute(Taller $taller, bool $visible, UsuarioSistema $actor): Taller
    {
        if (! $actor->esSuperAdmin() && ! $actor->tienePermiso('taller.configurar', $taller->id)) {
            throw new BusinessException('No tiene permiso para cambiar la visibilidad de este taller.');
        }

        // Solo se valida lat/lon, no `geom`: son la fuente de verdad (constitution.md §1) y
        // `geom` se deriva de ellas automáticamente (HasGeolocation). Comprobar `$taller->geom`
        // aquí es además poco fiable en una instancia recién creada sin releer de BD: el cast
        // recibe el `Expression` crudo (aún sin resolver a WKB por Postgres), no un string hex,
        // así que `GeometryCast::get()` lo trataría como inválido y devolvería null igual.
        if ($visible && ($taller->lat === null || $taller->lon === null)) {
            throw new BusinessException('El taller necesita latitud y longitud para ser visible en el mapa.');
        }

        return DB::transaction(function () use ($taller, $visible, $actor) {
            $anterior = $taller->visible_en_mapa;

            $taller->visible_en_mapa = $visible;
            $taller->save();

            activity()
                ->causedBy($actor)
                ->performedOn($taller)
                ->withProperties(['anterior' => $anterior, 'nuevo' => $visible])
                ->log('cambio_visibilidad_taller');

            return $taller;
        });
    }
}
