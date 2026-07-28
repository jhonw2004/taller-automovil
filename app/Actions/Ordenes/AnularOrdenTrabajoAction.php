<?php

namespace App\Actions\Ordenes;

use App\Actions\Inventario\RegistrarMovimientoInventarioAction;
use App\Exceptions\BusinessException;
use App\Models\OrdenTrabajo;
use App\Models\OrdenTrabajoHistorialEstado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Anulación de orden (011-spec.md/plan.md), en una sola transacción:
 * 1. Motivo obligatorio + la transición actual→ANULADA debe estar permitida (`TransicionesEstadoOrden`,
 *    misma tabla que usa `CambiarEstadoOrdenAction` — p. ej. una orden `ENTREGADA` no tiene ningún
 *    destino permitido, tampoco ANULADA).
 * 2. **Brecha documentada a propósito**: el spec exige bloquear si la orden tiene una nota de venta
 *    PENDIENTE/PAGADA y auto-anular una nota EMITIDA — `012-notas-venta` no existe todavía (no es
 *    dependencia de `011` en `depends_on`), así que ese paso no puede implementarse: no hay tabla
 *    `notas_venta` que consultar. Ninguna orden puede tener una nota de venta real todavía, por lo
 *    que omitirlo no cambia el comportamiento observable hoy. Debe agregarse aquí cuando `012` exista.
 * 3. Repone stock (AJUSTE_POSITIVO vía `RegistrarMovimientoInventarioAction`) por cada línea de
 *    repuesto en estado ENTREGADO.
 * 4. Marca todas las líneas no ANULADAS como ANULADAS. Los totales (`subtotal_*`/`descuento`/`total`)
 *    NO se recalculan aquí a propósito: quedan congelados como registro histórico de lo que valía
 *    la orden al momento de anularse, en vez de recalcularse a 0 (que además podría violar el CHECK
 *    `descuento <= subtotal_servicios + subtotal_repuestos` si había un descuento aplicado).
 * 5. Inserta historial con el motivo obligatorio.
 */
class AnularOrdenTrabajoAction
{
    public function execute(
        OrdenTrabajo $orden,
        string $motivo,
        ?int $usuarioSistemaId = null,
    ): OrdenTrabajo {
        if (! filled($motivo)) {
            throw new BusinessException('El motivo de anulación es obligatorio.');
        }

        if (! TransicionesEstadoOrden::permite($orden->estado, 'ANULADA')) {
            throw new BusinessException("No se puede anular la orden desde su estado actual ({$orden->estado}).");
        }

        return DB::transaction(function () use ($orden, $motivo, $usuarioSistemaId) {
            $estadoAnterior = $orden->estado;

            $lineasRepuestoEntregadas = $orden->lineasRepuestos()->where('estado', 'ENTREGADO')->get();

            foreach ($lineasRepuestoEntregadas as $linea) {
                app(RegistrarMovimientoInventarioAction::class)->execute(
                    repuesto: $linea->repuesto,
                    tipoMovimiento: 'AJUSTE_POSITIVO',
                    cantidad: (float) $linea->cantidad,
                    usuarioSistemaId: $usuarioSistemaId,
                    motivo: Str::limit("Reposición por anulación de orden {$orden->codigo}.", 250, ''),
                    ordenTrabajoRepuestoId: $linea->id,
                );
            }

            $orden->lineasServicios()->where('estado', '!=', 'ANULADO')->update(['estado' => 'ANULADO']);
            $orden->lineasRepuestos()->where('estado', '!=', 'ANULADO')->update(['estado' => 'ANULADO']);

            $orden->estado = 'ANULADA';
            $orden->save();

            OrdenTrabajoHistorialEstado::create([
                'orden_trabajo_id' => $orden->id,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => 'ANULADA',
                'usuario_sistema_id' => $usuarioSistemaId,
                'observacion' => $motivo,
            ]);

            return $orden->fresh();
        });
    }
}
