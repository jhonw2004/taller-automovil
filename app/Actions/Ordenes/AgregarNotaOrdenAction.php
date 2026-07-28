<?php

namespace App\Actions\Ordenes;

use App\Exceptions\BusinessException;
use App\Models\OrdenTrabajo;
use App\Models\OrdenTrabajoNota;

/**
 * Notas `INTERNA` (solo ERP) o `PUBLICA` (reservada para un futuro portal de cliente, sin efecto
 * visible en el MVP — 011-spec.md). Una orden ANULADA no admite notas nuevas, mismo criterio que
 * "no puede editarse ni generar líneas... nuevas".
 */
class AgregarNotaOrdenAction
{
    public function execute(
        OrdenTrabajo $orden,
        string $tipo,
        string $nota,
        ?int $usuarioSistemaId = null,
    ): OrdenTrabajoNota {
        if ($orden->estado === 'ANULADA') {
            throw new BusinessException('La orden está anulada y no admite notas nuevas.');
        }

        if (! in_array($tipo, ['INTERNA', 'PUBLICA'], true)) {
            throw new BusinessException("Tipo de nota inválido: {$tipo}.");
        }

        return OrdenTrabajoNota::create([
            'orden_trabajo_id' => $orden->id,
            'usuario_sistema_id' => $usuarioSistemaId,
            'tipo' => $tipo,
            'nota' => $nota,
        ]);
    }
}
