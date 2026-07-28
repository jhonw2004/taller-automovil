<?php

namespace App\Actions\Auditoria;

use App\Models\AuditoriaAcceso;

/**
 * Único punto de escritura de `auditoria_accesos` (015-plan.md). Nunca recibe ni guarda
 * `password_hash` (constitution.md §7) — solo `identificador` (username/email intentado).
 */
class RegistrarAccesoAuditoriaAction
{
    public function execute(
        string $tipoAcceso,
        string $resultado,
        ?int $usuarioSistemaId = null,
        ?int $usuarioMarketplaceId = null,
        ?int $tallerId = null,
        ?string $identificador = null,
    ): AuditoriaAcceso {
        return AuditoriaAcceso::create([
            'usuario_sistema_id' => $usuarioSistemaId,
            'usuario_marketplace_id' => $usuarioMarketplaceId,
            'taller_id' => $tallerId,
            'tipo_acceso' => $tipoAcceso,
            'resultado' => $resultado,
            'identificador' => $identificador,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
