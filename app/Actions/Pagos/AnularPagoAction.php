<?php

namespace App\Actions\Pagos;

use App\Exceptions\BusinessException;
use App\Models\NotaVenta;
use App\Models\Pago;
use Illuminate\Support\Facades\DB;

/**
 * Anulación de pago (013-spec.md/plan.md), en una sola transacción: `lockForUpdate` sobre la fila
 * de la nota y sobre el pago (constitution.md §3.6), el pago pasa a `ANULADO` (nunca se borra
 * físicamente, constitution.md §2) y se excluye de `monto_pagado` al recalcular la nota — si la
 * nota estaba `PAGADA` vuelve a `PENDIENTE` (o `EMITIDA` si `monto_pagado` queda en 0), regla que
 * ya aplica `NotaVenta::recalcularTotales()` sin necesidad de lógica adicional aquí. Auditado vía
 * `activity()` (013-spec.md: "la anulación de pago queda auditada"), mismo patrón que
 * `AnularNotaVentaAction` (012) — no existe una tabla de historial dedicada para pagos.
 */
class AnularPagoAction
{
    public function execute(Pago $pago, ?int $usuarioSistemaId = null): Pago
    {
        return DB::transaction(function () use ($pago, $usuarioSistemaId) {
            $nota = NotaVenta::whereKey($pago->nota_venta_id)->lockForUpdate()->firstOrFail();
            $pago = Pago::whereKey($pago->id)->lockForUpdate()->firstOrFail();

            if ($pago->estado !== 'CONFIRMADO') {
                throw new BusinessException('Solo se puede anular un pago confirmado.');
            }

            $pago->estado = 'ANULADO';
            $pago->save();

            $nota->recalcularTotales();

            activity()
                ->causedBy($usuarioSistemaId)
                ->performedOn($pago)
                ->log('anular_pago');

            return $pago->fresh();
        });
    }
}
