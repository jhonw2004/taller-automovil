<?php

namespace App\Actions\Ordenes;

use App\Exceptions\BusinessException;
use App\Models\OrdenTrabajo;

/**
 * Cambia el descuento a nivel de orden (distinto del descuento por línea). Delega la validación
 * ("no negativo, no mayor a la suma de subtotales") a `OrdenTrabajo::recalcularTotales()`, que
 * ya la aplica también tras cada alta/baja de línea (011-spec.md, constitution.md §3.7).
 */
class ActualizarDescuentoOrdenAction
{
    public function execute(OrdenTrabajo $orden, float $descuento): OrdenTrabajo
    {
        if ($orden->estado === 'ANULADA') {
            throw new BusinessException('La orden está anulada y no puede editarse.');
        }

        $orden->recalcularTotales($descuento);

        return $orden->fresh();
    }
}
