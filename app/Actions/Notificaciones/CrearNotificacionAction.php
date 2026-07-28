<?php

namespace App\Actions\Notificaciones;

use App\Exceptions\BusinessException;
use App\Models\Notificacion;

/**
 * Único punto de escritura de `notificaciones` (014-plan.md), consumido por las 10 clases de
 * `app/Notifications/` — cada una resuelve destinatarios y arma título/mensaje según el tipo, pero
 * la inserción real (y la validación de "exactamente un destinatario") vive acá una sola vez.
 */
class CrearNotificacionAction
{
    public function execute(
        string $tipo,
        string $titulo,
        string $mensaje,
        ?int $usuarioMarketplaceId = null,
        ?int $usuarioSistemaId = null,
        ?int $tallerId = null,
        ?int $ordenTrabajoId = null,
        ?array $data = null,
    ): Notificacion {
        if (($usuarioMarketplaceId === null) === ($usuarioSistemaId === null)) {
            throw new BusinessException('Una notificación debe tener exactamente un destinatario.');
        }

        return Notificacion::create([
            'usuario_marketplace_id' => $usuarioMarketplaceId,
            'usuario_sistema_id' => $usuarioSistemaId,
            'taller_id' => $tallerId,
            'orden_trabajo_id' => $ordenTrabajoId,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'data' => $data,
            'leida' => false,
        ]);
    }
}
