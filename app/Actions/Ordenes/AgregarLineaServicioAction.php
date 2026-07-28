<?php

namespace App\Actions\Ordenes;

use App\Exceptions\BusinessException;
use App\Models\OrdenTrabajo;
use App\Models\OrdenTrabajoServicio;
use App\Models\ServicioCatalogo;
use Illuminate\Support\Facades\DB;

/**
 * `precio_unitario` es un snapshot de `ServicioCatalogo::precio_base` al momento de agregar la
 * línea — no cambia si el catálogo cambia después (011-spec.md). Recalcula los totales de la
 * orden en la misma transacción (constitution.md §3.7).
 */
class AgregarLineaServicioAction
{
    public function execute(
        OrdenTrabajo $orden,
        int $servicioCatalogoId,
        int $cantidad = 1,
        float $descuento = 0,
    ): OrdenTrabajoServicio {
        if ($orden->estado === 'ANULADA') {
            throw new BusinessException('La orden está anulada y no puede editarse.');
        }

        if ($cantidad <= 0) {
            throw new BusinessException('La cantidad debe ser mayor a cero.');
        }

        $servicio = ServicioCatalogo::find($servicioCatalogoId);

        if (! $servicio) {
            throw new BusinessException('El servicio no pertenece al taller activo.');
        }

        $bruto = $cantidad * (float) $servicio->precio_base;

        if ($descuento < 0 || $descuento > $bruto) {
            throw new BusinessException('El descuento no puede superar el bruto de la línea.');
        }

        return DB::transaction(function () use ($orden, $servicio, $cantidad, $descuento, $bruto) {
            $linea = OrdenTrabajoServicio::create([
                'orden_trabajo_id' => $orden->id,
                'servicio_catalogo_id' => $servicio->id,
                'cantidad' => $cantidad,
                'precio_unitario' => $servicio->precio_base,
                'descuento' => $descuento,
                'subtotal' => $bruto - $descuento,
                'estado' => 'PENDIENTE',
            ]);

            $orden->recalcularTotales();

            return $linea;
        });
    }
}
