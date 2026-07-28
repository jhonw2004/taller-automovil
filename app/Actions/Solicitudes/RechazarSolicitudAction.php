<?php

namespace App\Actions\Solicitudes;

use App\Exceptions\BusinessException;
use App\Models\SolicitudTaller;
use App\Models\SolicitudTallerHistorial;
use App\Models\UsuarioSistema;
use App\Notifications\SolicitudRechazadaNotification;
use Illuminate\Support\Facades\DB;

class RechazarSolicitudAction
{
    private const ORIGENES_VALIDOS = ['PENDIENTE', 'EN_REVISION', 'APROBADA'];

    public function execute(SolicitudTaller $solicitud, string $motivoRechazo, UsuarioSistema $actor): SolicitudTaller
    {
        if (! $actor->esSuperAdmin() && ! $actor->tienePermiso('solicitudes.rechazar')) {
            throw new BusinessException('No tiene permiso para rechazar solicitudes de alta de taller.');
        }

        if (trim($motivoRechazo) === '') {
            throw new BusinessException('El rechazo de una solicitud requiere un motivo.');
        }

        return DB::transaction(function () use ($solicitud, $motivoRechazo, $actor) {
            $solicitud = SolicitudTaller::whereKey($solicitud->id)->lockForUpdate()->firstOrFail();

            if (! in_array($solicitud->estado, self::ORIGENES_VALIDOS, true)) {
                throw new BusinessException("No se puede rechazar desde el estado {$solicitud->estado}.");
            }

            $anterior = $solicitud->estado;

            $solicitud->estado = 'RECHAZADA';
            $solicitud->motivo_rechazo = $motivoRechazo;
            $solicitud->rechazada_at = now();
            $solicitud->gestionada_por_usuario_sistema_id = $actor->id;
            $solicitud->save();

            SolicitudTallerHistorial::create([
                'solicitud_taller_id' => $solicitud->id,
                'estado_anterior' => $anterior,
                'estado_nuevo' => 'RECHAZADA',
                'usuario_sistema_id' => $actor->id,
                'observacion' => $motivoRechazo,
            ]);

            activity()->causedBy($actor)->performedOn($solicitud)->log('rechazar_solicitud_taller');

            SolicitudRechazadaNotification::enviar($solicitud, $actor);

            return $solicitud;
        });
    }
}
