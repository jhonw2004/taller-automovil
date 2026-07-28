<?php

namespace App\Actions\Ordenes;

use App\Actions\Inventario\RegistrarMovimientoInventarioAction;
use App\Exceptions\BusinessException;
use App\Models\OrdenTrabajoRepuesto;
use Illuminate\Support\Facades\DB;

/**
 * Máquina de estados de línea de repuesto (011-spec.md): PENDIENTE → ENTREGADO|ANULADO,
 * ENTREGADO → ANULADO.
 * - PENDIENTE→ENTREGADO descuenta stock (`RegistrarMovimientoInventarioAction`, tipo SALIDA, con
 *   `ordenTrabajoRepuestoId` = esta línea para que la Action de inventario rechace una doble
 *   salida). Si el stock es insuficiente, la Action de inventario lanza `BusinessException`, la
 *   transacción hace rollback completo y la línea NO cambia de estado (011-plan.md).
 * - ENTREGADO→ANULADO repone stock (AJUSTE_POSITIVO) antes de anular la línea — mismo principio
 *   que la reposición de `AnularOrdenTrabajoAction` para toda la orden, aplicado aquí a nivel de
 *   una sola línea (no está en un criterio de aceptación explícito de 011-spec.md, pero es la
 *   misma regla de "no dejar stock inconsistente" ya establecida para la anulación completa).
 */
class CambiarEstadoLineaRepuestoAction
{
    private const MAPA = [
        'PENDIENTE' => ['ENTREGADO', 'ANULADO'],
        'ENTREGADO' => ['ANULADO'],
        'ANULADO' => [],
    ];

    public function execute(
        OrdenTrabajoRepuesto $linea,
        string $nuevoEstado,
        ?int $usuarioSistemaId = null,
    ): OrdenTrabajoRepuesto {
        $orden = $linea->ordenTrabajo;

        if ($orden->estado === 'ANULADA') {
            throw new BusinessException('La orden está anulada y no puede editarse.');
        }

        if (! in_array($nuevoEstado, self::MAPA[$linea->estado] ?? [], true)) {
            throw new BusinessException("Transición de estado inválida para la línea: {$linea->estado} → {$nuevoEstado}.");
        }

        return DB::transaction(function () use ($linea, $orden, $nuevoEstado, $usuarioSistemaId) {
            if ($nuevoEstado === 'ENTREGADO') {
                app(RegistrarMovimientoInventarioAction::class)->execute(
                    repuesto: $linea->repuesto,
                    tipoMovimiento: 'SALIDA',
                    cantidad: (float) $linea->cantidad,
                    usuarioSistemaId: $usuarioSistemaId,
                    motivo: "Consumo por orden {$orden->codigo}.",
                    ordenTrabajoRepuestoId: $linea->id,
                );
            }

            if ($nuevoEstado === 'ANULADO' && $linea->estado === 'ENTREGADO') {
                app(RegistrarMovimientoInventarioAction::class)->execute(
                    repuesto: $linea->repuesto,
                    tipoMovimiento: 'AJUSTE_POSITIVO',
                    cantidad: (float) $linea->cantidad,
                    usuarioSistemaId: $usuarioSistemaId,
                    motivo: "Reposición por anulación de línea de orden {$orden->codigo}.",
                    ordenTrabajoRepuestoId: $linea->id,
                );
            }

            $linea->estado = $nuevoEstado;
            $linea->save();

            $orden->recalcularTotales();

            return $linea->fresh();
        });
    }
}
