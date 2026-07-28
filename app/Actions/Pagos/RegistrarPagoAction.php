<?php

namespace App\Actions\Pagos;

use App\Events\PagoRegistrado;
use App\Exceptions\BusinessException;
use App\Models\MetodoPago;
use App\Models\NotaVenta;
use App\Models\Pago;
use Illuminate\Support\Facades\DB;

/**
 * Registro de pago contra una nota de venta (013-spec.md/plan.md), en una sola transacción:
 * `lockForUpdate` sobre la fila de la nota (constitution.md §3.6), valida que la nota no esté
 * `ANULADA` y que el método de pago esté activo, rechaza si el monto supera el saldo pendiente
 * (013-spec.md: "la suma de pagos confirmados nunca puede superar el total"), inserta el pago
 * `CONFIRMADO` y recalcula la nota vía `NotaVenta::recalcularTotales()` (012, extendido en esta
 * feature para sumar pagos y aplicar las reglas de estado PAGADA/PENDIENTE/EMITIDA). Dispara
 * `PagoRegistrado` (sin listener todavía, ver la clase del evento) para el trigger `pago.registrado`
 * de `014-notificaciones/plan.md`.
 */
class RegistrarPagoAction
{
    public function execute(
        NotaVenta $nota,
        int $metodoPagoId,
        float $monto,
        ?string $referencia = null,
        ?string $observacion = null,
        ?int $usuarioSistemaId = null,
    ): Pago {
        if ($monto <= 0) {
            throw new BusinessException('El monto del pago debe ser mayor a cero.');
        }

        return DB::transaction(function () use ($nota, $metodoPagoId, $monto, $referencia, $observacion, $usuarioSistemaId) {
            $nota = NotaVenta::whereKey($nota->id)->lockForUpdate()->firstOrFail();

            if ($nota->estado === 'ANULADA') {
                throw new BusinessException('No se pueden registrar pagos sobre una nota anulada.');
            }

            $metodo = MetodoPago::find($metodoPagoId);

            if (! $metodo || ! $metodo->activo) {
                throw new BusinessException('El método de pago no está activo.');
            }

            if ($monto > (float) $nota->saldo) {
                throw new BusinessException('El pago no puede superar el saldo pendiente de la nota.');
            }

            $pago = Pago::create([
                'nota_venta_id' => $nota->id,
                'metodo_pago_id' => $metodoPagoId,
                'usuario_sistema_id' => $usuarioSistemaId,
                'fecha_pago' => now(),
                'monto' => $monto,
                'referencia' => $referencia,
                'observacion' => $observacion,
                'estado' => 'CONFIRMADO',
            ]);

            $nota->recalcularTotales();

            event(new PagoRegistrado($pago->fresh()));

            return $pago->fresh();
        });
    }
}
