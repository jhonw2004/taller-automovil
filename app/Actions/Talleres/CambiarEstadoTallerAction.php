<?php

namespace App\Actions\Talleres;

use App\Exceptions\BusinessException;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use Illuminate\Support\Facades\DB;

/**
 * Transiciona `estado` (ACTIVO/INACTIVO/SUSPENDIDO) — distinto de `visible_en_mapa`
 * (CambiarVisibilidadTallerAction) o del propietario (CambiarPropietarioTallerAction). El
 * catálogo de permisos de 002-roles-permisos solo define `admin.talleres.suspender` en el
 * ámbito Super Admin para esta operación, así que se restringe a Super Admin (no existe un
 * permiso equivalente para que el propio taller se autosuspenda).
 *
 * Auditoría vía `activity()` (spatie/laravel-activitylog), igual que las otras dos Actions de
 * Taller — ver `CambiarVisibilidadTallerAction` para el porqué de no usar `auditoria_eventos`.
 */
class CambiarEstadoTallerAction
{
    private const ESTADOS_VALIDOS = ['ACTIVO', 'INACTIVO', 'SUSPENDIDO'];

    public function execute(Taller $taller, string $nuevoEstado, UsuarioSistema $actor): Taller
    {
        if (! $actor->esSuperAdmin()) {
            throw new BusinessException('Solo el super administrador puede cambiar el estado de un taller.');
        }

        if (! in_array($nuevoEstado, self::ESTADOS_VALIDOS, true)) {
            throw new BusinessException("Estado inválido: {$nuevoEstado}.");
        }

        return DB::transaction(function () use ($taller, $nuevoEstado, $actor) {
            $anterior = $taller->estado;

            $taller->estado = $nuevoEstado;
            $taller->save();

            activity()
                ->causedBy($actor)
                ->performedOn($taller)
                ->withProperties(['anterior' => $anterior, 'nuevo' => $nuevoEstado])
                ->log('cambio_estado_taller');

            return $taller;
        });
    }
}
