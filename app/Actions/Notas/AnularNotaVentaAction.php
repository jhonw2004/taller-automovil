<?php

namespace App\Actions\Notas;

use App\Actions\Auditoria\RegistrarEventoAuditoriaAction;
use App\Actions\Inventario\RegistrarMovimientoInventarioAction;
use App\Exceptions\BusinessException;
use App\Models\NotaVenta;
use Illuminate\Support\Facades\DB;

/**
 * Anulación de nota de venta (012-spec.md/plan.md), en una sola transacción:
 * 1. Motivo obligatorio + la transición actual→ANULADA debe estar permitida
 *    (`TransicionesEstadoNota`). En la práctica esto ya excluye `PAGADA` (estado final, sin
 *    destinos listados — mismo criterio que `ENTREGADA` en `TransicionesEstadoOrden`/011).
 * 2. Bloqueo explícito si `monto_pagado > 0` ("Debe reversar los pagos primero", 012-spec.md) —
 *    redundante con (1) para `PAGADA`, pero es la regla real que bloquea `PENDIENTE` (que sí
 *    aparece como transición válida en la tabla de estados, ya que PENDIENTE implica pagos
 *    parciales > 0 por definición).
 * 3. Si es venta directa (`orden_trabajo_id` NULL) y tiene líneas de repuesto: repone stock
 *    (AJUSTE_POSITIVO) por cada una — toda línea de repuesto de una nota directa generó una SALIDA
 *    real al crearse (`CrearNotaVentaDirectaAction`), así que no hace falta consultar movimientos,
 *    la cantidad a reponer es la misma que la línea ya registra.
 * 4. Si proviene de una orden (`orden_trabajo_id` no nulo): no se toca inventario — el descuento de
 *    stock ocurrió al marcar los repuestos como ENTREGADO en la orden (010/011), no en la nota.
 * 5. Cambia el estado a ANULADA y audita vía `RegistrarEventoAuditoriaAction` → `auditoria_eventos`
 *    (015-plan.md: "anulación de nota" está en el catálogo de eventos de negocio) — 012-plan.md
 *    no define una tabla de historial dedicada para notas, a diferencia de
 *    `ordenes_trabajo_estados_historial` en 011.
 */
class AnularNotaVentaAction
{
    public function execute(
        NotaVenta $nota,
        string $motivo,
        ?int $usuarioSistemaId = null,
    ): NotaVenta {
        if (! filled($motivo)) {
            throw new BusinessException('El motivo de anulación es obligatorio.');
        }

        if (! TransicionesEstadoNota::permite($nota->estado, 'ANULADA')) {
            throw new BusinessException("No se puede anular la nota desde su estado actual ({$nota->estado}).");
        }

        if ((float) $nota->monto_pagado > 0) {
            throw new BusinessException('Debe reversar los pagos primero.');
        }

        return DB::transaction(function () use ($nota, $motivo, $usuarioSistemaId) {
            if ($nota->orden_trabajo_id === null) {
                $lineasRepuesto = $nota->lineas()->whereNotNull('repuesto_id')->with('repuesto')->get();

                foreach ($lineasRepuesto as $linea) {
                    app(RegistrarMovimientoInventarioAction::class)->execute(
                        repuesto: $linea->repuesto,
                        tipoMovimiento: 'AJUSTE_POSITIVO',
                        cantidad: (float) $linea->cantidad,
                        usuarioSistemaId: $usuarioSistemaId,
                        motivo: "Reposición por anulación de nota {$nota->codigo}.",
                        referencia: $nota->codigo,
                    );
                }
            }

            $nota->estado = 'ANULADA';
            $nota->save();

            app(RegistrarEventoAuditoriaAction::class)->execute(
                evento: 'anular_nota_venta',
                usuarioSistemaId: $usuarioSistemaId,
                tallerId: $nota->taller_id,
                entidad: $nota,
                datos: ['motivo' => $motivo],
            );

            return $nota->fresh();
        });
    }
}
