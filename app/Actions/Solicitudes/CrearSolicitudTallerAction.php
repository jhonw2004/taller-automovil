<?php

namespace App\Actions\Solicitudes;

use App\Models\SolicitudTaller;
use App\Models\SolicitudTallerHistorial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creación pública (sin cuenta) de una solicitud de alta de taller (004-solicitud-alta-taller).
 * `token_publico` se genera aquí (no en el modelo/BD) para poder devolverlo de inmediato al
 * solicitante sin un segundo round-trip. Historial inicial con `usuario_sistema_id = null`:
 * el evento lo origina el propio solicitante, no un usuario del sistema.
 */
class CrearSolicitudTallerAction
{
    public function execute(array $data): SolicitudTaller
    {
        return DB::transaction(function () use ($data) {
            $solicitud = SolicitudTaller::create([
                ...$data,
                'token_publico' => (string) Str::uuid(),
                'estado' => 'PENDIENTE',
                'enviada_at' => now(),
            ]);

            SolicitudTallerHistorial::create([
                'solicitud_taller_id' => $solicitud->id,
                'estado_anterior' => null,
                'estado_nuevo' => 'PENDIENTE',
                'usuario_sistema_id' => null,
                'observacion' => 'Solicitud enviada por el solicitante.',
            ]);

            return $solicitud;
        });
    }
}
