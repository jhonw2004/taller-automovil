<?php

namespace App\Actions\Inventario;

use App\Events\StockBajoDetectado;
use App\Exceptions\BusinessException;
use App\Models\InventarioMovimiento;
use App\Models\Repuesto;
use Illuminate\Support\Facades\DB;

/**
 * Unico punto de escritura de `inventario_movimientos`/`repuestos.stock_actual`
 * (010-inventario-repuestos/plan.md): `lockForUpdate` sobre el repuesto, calculo de
 * `stock_resultante` segun el tipo de movimiento, rechazo si quedaria negativo — todo en la
 * misma transaccion (constitution.md §3.6/§3.7).
 */
class RegistrarMovimientoInventarioAction
{
    private const TIPOS_QUE_SUMAN = ['ENTRADA', 'AJUSTE_POSITIVO'];

    private const TIPOS_QUE_RESTAN = ['SALIDA', 'AJUSTE_NEGATIVO'];

    private const TIPOS_VALIDOS = [...self::TIPOS_QUE_SUMAN, ...self::TIPOS_QUE_RESTAN];

    public function execute(
        Repuesto $repuesto,
        string $tipoMovimiento,
        float $cantidad,
        ?int $usuarioSistemaId = null,
        ?string $motivo = null,
        ?string $referencia = null,
        ?int $proveedorId = null,
        ?float $costoUnitario = null,
        ?int $ordenTrabajoRepuestoId = null,
    ): InventarioMovimiento {
        if (! in_array($tipoMovimiento, self::TIPOS_VALIDOS, true)) {
            throw new BusinessException("Tipo de movimiento de inventario inválido: {$tipoMovimiento}.");
        }

        if ($cantidad <= 0) {
            throw new BusinessException('La cantidad del movimiento debe ser mayor a cero.');
        }

        return DB::transaction(function () use (
            $repuesto,
            $tipoMovimiento,
            $cantidad,
            $usuarioSistemaId,
            $motivo,
            $referencia,
            $proveedorId,
            $costoUnitario,
            $ordenTrabajoRepuestoId,
        ) {
            $repuesto = Repuesto::whereKey($repuesto->id)->lockForUpdate()->firstOrFail();

            if ($tipoMovimiento === 'SALIDA' && $ordenTrabajoRepuestoId !== null) {
                $yaExisteSalida = InventarioMovimiento::where('orden_trabajo_repuesto_id', $ordenTrabajoRepuestoId)
                    ->where('tipo_movimiento', 'SALIDA')
                    ->exists();

                if ($yaExisteSalida) {
                    throw new BusinessException('Ya se registró una salida de inventario para esta línea de orden.');
                }
            }

            $stockAnterior = (float) $repuesto->stock_actual;
            $signo = in_array($tipoMovimiento, self::TIPOS_QUE_SUMAN, true) ? 1 : -1;
            $stockResultante = $stockAnterior + ($signo * $cantidad);

            if ($stockResultante < 0) {
                throw new BusinessException('Stock insuficiente para registrar este movimiento.');
            }

            $movimiento = InventarioMovimiento::create([
                'taller_id' => $repuesto->taller_id,
                'repuesto_id' => $repuesto->id,
                'tipo_movimiento' => $tipoMovimiento,
                'cantidad' => $cantidad,
                'stock_anterior' => $stockAnterior,
                'stock_resultante' => $stockResultante,
                'costo_unitario' => $costoUnitario,
                'proveedor_id' => $proveedorId,
                'orden_trabajo_repuesto_id' => $ordenTrabajoRepuestoId,
                'usuario_sistema_id' => $usuarioSistemaId,
                'motivo' => $motivo,
                'referencia' => $referencia,
            ]);

            $repuesto->stock_actual = $stockResultante;
            $repuesto->save();

            if ($stockResultante <= (float) $repuesto->stock_minimo) {
                event(new StockBajoDetectado($repuesto->fresh()));
            }

            return $movimiento;
        });
    }
}
