<?php

namespace App\Actions\Auditoria;

use App\Models\AuditoriaEvento;
use Illuminate\Database\Eloquent\Model;

/**
 * Único punto de escritura de `auditoria_eventos` (015-plan.md). Reemplaza el uso de
 * `activity()` (spatie/laravel-activitylog) que varias Actions de features anteriores usaban
 * como stand-in genérico mientras esta tabla no existía (003/004/006/008/011/012/013,
 * documentado explícitamente en cada una) — ver `database/migrations/..._drop_activity_log_table`
 * para el porqué del reemplazo completo, no solo un stand-in adicional.
 */
class RegistrarEventoAuditoriaAction
{
    public function execute(
        string $evento,
        ?int $usuarioSistemaId = null,
        ?int $usuarioMarketplaceId = null,
        ?int $tallerId = null,
        ?Model $entidad = null,
        ?array $datos = null,
    ): AuditoriaEvento {
        return AuditoriaEvento::create([
            'usuario_sistema_id' => $usuarioSistemaId,
            'usuario_marketplace_id' => $usuarioMarketplaceId,
            'taller_id' => $tallerId,
            'evento' => $evento,
            'entidad_tipo' => $entidad !== null ? $entidad::class : null,
            'entidad_id' => $entidad?->getKey(),
            'datos' => $datos,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
