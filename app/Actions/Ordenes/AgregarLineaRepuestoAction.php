<?php

namespace App\Actions\Ordenes;

use App\Exceptions\BusinessException;
use App\Models\OrdenTrabajo;
use App\Models\OrdenTrabajoRepuesto;
use App\Models\Repuesto;
use Illuminate\Support\Facades\DB;

/**
 * `precio_unitario` es un snapshot de `Repuesto::precio_venta` al momento de agregar la línea
 * (011-spec.md). Agregar la línea NO descuenta stock todavía — solo reserva; el descuento real
 * ocurre al pasar la línea a `ENTREGADO` (`CambiarEstadoLineaRepuestoAction`).
 */
class AgregarLineaRepuestoAction
{
    public function execute(
        OrdenTrabajo $orden,
        int $repuestoId,
        float $cantidad = 1,
        float $descuento = 0,
    ): OrdenTrabajoRepuesto {
        if ($orden->estado === 'ANULADA') {
            throw new BusinessException('La orden está anulada y no puede editarse.');
        }

        if ($cantidad <= 0) {
            throw new BusinessException('La cantidad debe ser mayor a cero.');
        }

        $repuesto = Repuesto::find($repuestoId);

        if (! $repuesto) {
            throw new BusinessException('El repuesto no pertenece al taller activo.');
        }

        $bruto = $cantidad * (float) $repuesto->precio_venta;

        if ($descuento < 0 || $descuento > $bruto) {
            throw new BusinessException('El descuento no puede superar el bruto de la línea.');
        }

        return DB::transaction(function () use ($orden, $repuesto, $cantidad, $descuento, $bruto) {
            $linea = OrdenTrabajoRepuesto::create([
                'orden_trabajo_id' => $orden->id,
                'repuesto_id' => $repuesto->id,
                'cantidad' => $cantidad,
                'precio_unitario' => $repuesto->precio_venta,
                'descuento' => $descuento,
                'subtotal' => $bruto - $descuento,
                'estado' => 'PENDIENTE',
            ]);

            $orden->recalcularTotales();

            return $linea;
        });
    }
}
