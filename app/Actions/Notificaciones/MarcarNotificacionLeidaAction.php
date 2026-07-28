<?php

namespace App\Actions\Notificaciones;

use App\Exceptions\BusinessException;
use App\Models\Notificacion;
use App\Models\UsuarioMarketplace;
use App\Models\UsuarioSistema;

/**
 * "Un usuario solo puede marcar como leídas sus propias notificaciones" (014-spec.md) — el actor
 * puede venir de cualquiera de los dos guards (`web`/`sistema`), se valida contra la columna de
 * destinatario que corresponda según su tipo, nunca contra `id` a secas (un usuario marketplace y
 * uno sistema pueden compartir el mismo `id` numérico en tablas distintas).
 */
class MarcarNotificacionLeidaAction
{
    public function execute(Notificacion $notificacion, UsuarioMarketplace|UsuarioSistema $actor): Notificacion
    {
        $esDestinatario = match (true) {
            $actor instanceof UsuarioMarketplace => $notificacion->usuario_marketplace_id === $actor->id,
            $actor instanceof UsuarioSistema => $notificacion->usuario_sistema_id === $actor->id,
        };

        if (! $esDestinatario) {
            throw new BusinessException('No puede marcar como leída una notificación que no es suya.');
        }

        $notificacion->marcarLeida();

        return $notificacion;
    }
}
