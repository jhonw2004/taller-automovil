<?php

namespace App\Actions\Talleres;

use App\Actions\Auditoria\RegistrarEventoAuditoriaAction;
use App\Exceptions\BusinessException;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use Illuminate\Support\Facades\DB;

/**
 * Auditoría vía `RegistrarEventoAuditoriaAction` → `auditoria_eventos` (015-plan.md). El cambio
 * de `visible_en_mapa` en sí también queda en `auditoria_talleres` vía `TallerObserver` (campo
 * sensible del catálogo de 015-spec.md) — este evento nombrado es un registro de negocio
 * adicional, no un reemplazo: `auditoria_talleres` es el snapshot genérico de cualquier campo
 * sensible, `auditoria_eventos` es el catálogo curado de acciones de negocio.
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

            app(RegistrarEventoAuditoriaAction::class)->execute(
                evento: 'cambio_visibilidad_taller',
                usuarioSistemaId: $actor->id,
                tallerId: $taller->id,
                entidad: $taller,
                datos: ['anterior' => $anterior, 'nuevo' => $visible],
            );

            return $taller;
        });
    }
}
