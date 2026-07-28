<?php

namespace App\Actions\Solicitudes;

use App\Actions\Auditoria\RegistrarEventoAuditoriaAction;
use App\Exceptions\BusinessException;
use App\Models\SolicitudTaller;
use App\Models\SolicitudTallerHistorial;
use App\Models\UsuarioSistema;
use Illuminate\Support\Facades\DB;

class IniciarRevisionSolicitudAction
{
    private const ORIGENES_VALIDOS = ['PENDIENTE'];

    public function execute(SolicitudTaller $solicitud, UsuarioSistema $actor): SolicitudTaller
    {
        if (! $actor->esSuperAdmin() && ! $actor->tienePermiso('solicitudes.revisar')) {
            throw new BusinessException('No tiene permiso para revisar solicitudes de alta de taller.');
        }

        return DB::transaction(function () use ($solicitud, $actor) {
            $solicitud = SolicitudTaller::whereKey($solicitud->id)->lockForUpdate()->firstOrFail();

            if (! in_array($solicitud->estado, self::ORIGENES_VALIDOS, true)) {
                throw new BusinessException("No se puede iniciar revisión desde el estado {$solicitud->estado}.");
            }

            $anterior = $solicitud->estado;

            $solicitud->estado = 'EN_REVISION';
            $solicitud->revisada_at = now();
            $solicitud->gestionada_por_usuario_sistema_id = $actor->id;
            $solicitud->save();

            SolicitudTallerHistorial::create([
                'solicitud_taller_id' => $solicitud->id,
                'estado_anterior' => $anterior,
                'estado_nuevo' => 'EN_REVISION',
                'usuario_sistema_id' => $actor->id,
            ]);

            app(RegistrarEventoAuditoriaAction::class)->execute(
                evento: 'iniciar_revision_solicitud_taller',
                usuarioSistemaId: $actor->id,
                entidad: $solicitud,
            );

            return $solicitud;
        });
    }
}
