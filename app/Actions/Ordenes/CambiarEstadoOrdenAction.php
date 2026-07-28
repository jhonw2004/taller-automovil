<?php

namespace App\Actions\Ordenes;

use App\Exceptions\BusinessException;
use App\Models\OrdenTrabajo;
use App\Models\OrdenTrabajoHistorialEstado;
use Illuminate\Support\Facades\DB;

/**
 * Transiciones normales de la máquina de estados (011-spec.md). No maneja `ANULADA`: esa
 * transición exige permiso especial + motivo obligatorio + reposición de stock, responsabilidad
 * de `AnularOrdenTrabajoAction`. Cada cambio inserta una fila en el historial (append-only).
 */
class CambiarEstadoOrdenAction
{
    public function execute(
        OrdenTrabajo $orden,
        string $nuevoEstado,
        ?int $usuarioSistemaId = null,
        ?string $observacion = null,
    ): OrdenTrabajo {
        if ($nuevoEstado === 'ANULADA') {
            throw new BusinessException('Use la acción de anular para cancelar la orden.');
        }

        if (! TransicionesEstadoOrden::permite($orden->estado, $nuevoEstado)) {
            throw new BusinessException("Transición de estado inválida: {$orden->estado} → {$nuevoEstado}.");
        }

        return DB::transaction(function () use ($orden, $nuevoEstado, $usuarioSistemaId, $observacion) {
            $estadoAnterior = $orden->estado;

            $orden->estado = $nuevoEstado;

            if ($nuevoEstado === 'ENTREGADA') {
                $orden->fecha_entrega_real = now();
            }

            $orden->save();

            OrdenTrabajoHistorialEstado::create([
                'orden_trabajo_id' => $orden->id,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $nuevoEstado,
                'usuario_sistema_id' => $usuarioSistemaId,
                'observacion' => $observacion,
            ]);

            return $orden->fresh();
        });
    }
}
