<?php

namespace App\Actions\Ordenes;

use App\Exceptions\BusinessException;
use App\Models\OrdenTrabajoServicio;
use Illuminate\Support\Facades\DB;

/**
 * Máquina de estados de línea de servicio (011-spec.md): PENDIENTE → REALIZADO|ANULADO,
 * REALIZADO → ANULADO. Sin efecto de stock. Recalcula los totales de la orden porque una línea
 * ANULADA deja de sumar (constitution.md §3.7).
 */
class CambiarEstadoLineaServicioAction
{
    private const MAPA = [
        'PENDIENTE' => ['REALIZADO', 'ANULADO'],
        'REALIZADO' => ['ANULADO'],
        'ANULADO' => [],
    ];

    public function execute(OrdenTrabajoServicio $linea, string $nuevoEstado): OrdenTrabajoServicio
    {
        $orden = $linea->ordenTrabajo;

        if ($orden->estado === 'ANULADA') {
            throw new BusinessException('La orden está anulada y no puede editarse.');
        }

        if (! in_array($nuevoEstado, self::MAPA[$linea->estado] ?? [], true)) {
            throw new BusinessException("Transición de estado inválida para la línea: {$linea->estado} → {$nuevoEstado}.");
        }

        return DB::transaction(function () use ($linea, $orden, $nuevoEstado) {
            $linea->estado = $nuevoEstado;
            $linea->save();

            $orden->recalcularTotales();

            return $linea->fresh();
        });
    }
}
