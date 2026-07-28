<?php

namespace App\Actions\Solicitudes;

use App\Exceptions\BusinessException;
use App\Models\SolicitudTaller;
use App\Models\SolicitudTallerHistorial;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use App\Notifications\SolicitudAprobadaNotification;
use Illuminate\Support\Facades\DB;

/**
 * Aprueba y crea el taller en la misma transacción (spec.md, "Aprobación con creación de
 * taller"): bloquea la fila (`FOR UPDATE`) para que dos aprobaciones concurrentes no completen
 * la misma solicitud dos veces, valida el estado, crea el `Taller`, vincula `taller_id`, pasa a
 * `COMPLETADA` e inserta historial. Si la creación del taller falla, todo hace rollback — no
 * queda taller huérfano ni solicitud a medias.
 *
 * `$tallerData` permite que el super admin edite los datos antes de crear el taller (plan.md:
 * "modal pre-cargado con datos + mapa editable"); cualquier campo ausente cae al valor guardado
 * en la solicitud. Requiere `solicitudes.aprobar` Y `solicitudes.crear_taller` — son permisos
 * separados en el catálogo (002-roles-permisos) a propósito, para poder delegar la revisión sin
 * delegar la creación efectiva del tenant.
 */
class AprobarYCompletarSolicitudAction
{
    private const ORIGENES_VALIDOS = ['PENDIENTE', 'EN_REVISION', 'APROBADA'];

    public function execute(SolicitudTaller $solicitud, array $tallerData, UsuarioSistema $actor): SolicitudTaller
    {
        if (! $actor->esSuperAdmin()
            && (! $actor->tienePermiso('solicitudes.aprobar') || ! $actor->tienePermiso('solicitudes.crear_taller'))) {
            throw new BusinessException('No tiene permiso para aprobar y crear el taller de esta solicitud.');
        }

        return DB::transaction(function () use ($solicitud, $tallerData, $actor) {
            $solicitud = SolicitudTaller::whereKey($solicitud->id)->lockForUpdate()->firstOrFail();

            if (! in_array($solicitud->estado, self::ORIGENES_VALIDOS, true)) {
                throw new BusinessException("No se puede aprobar y completar desde el estado {$solicitud->estado}.");
            }

            $lat = $tallerData['lat'] ?? $solicitud->lat;
            $lon = $tallerData['lon'] ?? $solicitud->lon;

            if ($lat === null || $lon === null) {
                throw new BusinessException('Se requiere latitud y longitud para crear el taller.');
            }

            $taller = Taller::create([
                'nombre' => $tallerData['nombre'] ?? $solicitud->taller_nombre,
                'direccion' => $tallerData['direccion'] ?? $solicitud->taller_direccion,
                'telefono' => $tallerData['telefono'] ?? $solicitud->solicitante_telefono,
                'email' => $tallerData['email'] ?? $solicitud->solicitante_email,
                'lat' => $lat,
                'lon' => $lon,
                'osm_id' => $tallerData['osm_id'] ?? $solicitud->osm_id,
                'estado' => 'ACTIVO',
                'visible_en_mapa' => false,
            ]);

            $anterior = $solicitud->estado;

            $solicitud->estado = 'COMPLETADA';
            $solicitud->completada_at = now();
            $solicitud->taller_id = $taller->id;
            $solicitud->gestionada_por_usuario_sistema_id = $actor->id;
            $solicitud->save();

            SolicitudTallerHistorial::create([
                'solicitud_taller_id' => $solicitud->id,
                'estado_anterior' => $anterior,
                'estado_nuevo' => 'COMPLETADA',
                'usuario_sistema_id' => $actor->id,
                'observacion' => "Taller creado: {$taller->nombre} (#{$taller->id}).",
            ]);

            activity()
                ->causedBy($actor)
                ->performedOn($solicitud)
                ->withProperties(['taller_id' => $taller->id])
                ->log('aprobar_completar_solicitud_taller');

            SolicitudAprobadaNotification::enviar($solicitud, $actor);

            return $solicitud;
        });
    }
}
