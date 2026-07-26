<?php

namespace App\Actions\Solicitudes;

use App\Exceptions\BusinessException;
use App\Models\SolicitudTaller;
use App\Models\SolicitudTallerHistorial;
use Illuminate\Support\Facades\DB;

/**
 * Cancelación iniciada por el propio solicitante (sin cuenta, vía `token_publico`) — no hay
 * `UsuarioSistema` actor, el historial queda con `usuario_sistema_id = null`.
 */
class CancelarSolicitudAction
{
    private const ORIGENES_VALIDOS = ['PENDIENTE', 'EN_REVISION'];

    public function execute(SolicitudTaller $solicitud): SolicitudTaller
    {
        return DB::transaction(function () use ($solicitud) {
            $solicitud = SolicitudTaller::whereKey($solicitud->id)->lockForUpdate()->firstOrFail();

            if (! in_array($solicitud->estado, self::ORIGENES_VALIDOS, true)) {
                throw new BusinessException("No se puede cancelar una solicitud en estado {$solicitud->estado}.");
            }

            $anterior = $solicitud->estado;

            $solicitud->estado = 'CANCELADA';
            $solicitud->save();

            SolicitudTallerHistorial::create([
                'solicitud_taller_id' => $solicitud->id,
                'estado_anterior' => $anterior,
                'estado_nuevo' => 'CANCELADA',
                'usuario_sistema_id' => null,
                'observacion' => 'Cancelada por el solicitante.',
            ]);

            return $solicitud;
        });
    }
}
