<?php

namespace App\Actions\Solicitudes;

use App\Actions\Auditoria\RegistrarEventoAuditoriaAction;
use App\Exceptions\BusinessException;
use App\Models\SolicitudTaller;
use App\Models\SolicitudTallerHistorial;
use App\Models\UsuarioSistema;
use App\Notifications\SolicitudAprobadaNotification;
use Illuminate\Support\Facades\DB;

/**
 * Aprueba la solicitud sin crear el taller todavía (spec.md: "Super admin aprueba sin
 * completar"). El taller se crea después con `AprobarYCompletarSolicitudAction` (transición
 * APROBADA -> COMPLETADA).
 */
class AprobarSinCompletarSolicitudAction
{
    private const ORIGENES_VALIDOS = ['PENDIENTE', 'EN_REVISION'];

    public function execute(SolicitudTaller $solicitud, UsuarioSistema $actor): SolicitudTaller
    {
        if (! $actor->esSuperAdmin() && ! $actor->tienePermiso('solicitudes.aprobar')) {
            throw new BusinessException('No tiene permiso para aprobar solicitudes de alta de taller.');
        }

        return DB::transaction(function () use ($solicitud, $actor) {
            $solicitud = SolicitudTaller::whereKey($solicitud->id)->lockForUpdate()->firstOrFail();

            if (! in_array($solicitud->estado, self::ORIGENES_VALIDOS, true)) {
                throw new BusinessException("No se puede aprobar desde el estado {$solicitud->estado}.");
            }

            $anterior = $solicitud->estado;

            $solicitud->estado = 'APROBADA';
            $solicitud->aprobada_at = now();
            $solicitud->gestionada_por_usuario_sistema_id = $actor->id;
            $solicitud->save();

            SolicitudTallerHistorial::create([
                'solicitud_taller_id' => $solicitud->id,
                'estado_anterior' => $anterior,
                'estado_nuevo' => 'APROBADA',
                'usuario_sistema_id' => $actor->id,
            ]);

            app(RegistrarEventoAuditoriaAction::class)->execute(
                evento: 'aprobar_sin_completar_solicitud_taller',
                usuarioSistemaId: $actor->id,
                entidad: $solicitud,
            );

            SolicitudAprobadaNotification::enviar($solicitud, $actor);

            return $solicitud;
        });
    }
}
