<?php

namespace App\Actions\Talleres;

use App\Actions\Auditoria\RegistrarEventoAuditoriaAction;
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
 * Auditoría vía `RegistrarEventoAuditoriaAction` → `auditoria_eventos`, igual que las otras dos
 * Actions de Taller (ver `CambiarVisibilidadTallerAction` sobre el doble tracking con
 * `auditoria_talleres`, que también aplica aquí porque `estado` es un campo sensible).
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

            app(RegistrarEventoAuditoriaAction::class)->execute(
                evento: 'cambio_estado_taller',
                usuarioSistemaId: $actor->id,
                tallerId: $taller->id,
                entidad: $taller,
                datos: ['anterior' => $anterior, 'nuevo' => $nuevoEstado],
            );

            return $taller;
        });
    }
}
