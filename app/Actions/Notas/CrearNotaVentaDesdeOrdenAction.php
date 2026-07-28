<?php

namespace App\Actions\Notas;

use App\Actions\SequentialCodeGenerator;
use App\Exceptions\BusinessException;
use App\Models\NotaVenta;
use App\Models\NotaVentaLinea;
use App\Models\OrdenTrabajo;
use Illuminate\Support\Facades\DB;

/**
 * Copia las líneas activas (no ANULADO) de una orden `COMPLETADA`/`ENTREGADA` a una nota nueva
 * (012-spec.md/plan.md). El código se genera con `SequentialCodeGenerator` directamente (mismo
 * criterio ya aplicado en `CrearOrdenTrabajoAction` de 011: no se envuelve en una Action
 * `GenerarCodigoNota` propia, la tarea de `012-tasks.md` se satisface por reutilización, no
 * duplicación). No toca inventario: el descuento de stock de las líneas de repuesto ya ocurrió al
 * marcar cada línea `ENTREGADO` en la orden (`010`/`011`), y `012-spec.md` es explícito en que una
 * nota originada desde una orden nunca genera una segunda salida.
 * `precio_unitario`/`descuento`/`subtotal` de cada línea de orden se copian tal cual (ya son un
 * snapshot congelado desde que se agregaron a la orden) — no se vuelven a calcular contra el
 * catálogo actual.
 */
class CrearNotaVentaDesdeOrdenAction
{
    private const ESTADOS_ORDEN_FACTURABLES = ['COMPLETADA', 'ENTREGADA'];

    public function execute(OrdenTrabajo $orden, ?int $usuarioSistemaId = null): NotaVenta
    {
        if (! in_array($orden->estado, self::ESTADOS_ORDEN_FACTURABLES, true)) {
            throw new BusinessException('Solo se puede generar una nota de venta desde una orden completada o entregada.');
        }

        $lineasServicios = $orden->lineasServicios()->where('estado', '!=', 'ANULADO')->with('servicioCatalogo')->get();
        $lineasRepuestos = $orden->lineasRepuestos()->where('estado', '!=', 'ANULADO')->with('repuesto')->get();

        if ($lineasServicios->isEmpty() && $lineasRepuestos->isEmpty()) {
            throw new BusinessException('La orden no tiene líneas activas para facturar.');
        }

        return DB::transaction(function () use ($orden, $usuarioSistemaId, $lineasServicios, $lineasRepuestos) {
            $codigo = SequentialCodeGenerator::generate((string) $orden->taller_id, 'NV', 'notas_venta');

            $nota = NotaVenta::create([
                'taller_id' => $orden->taller_id,
                'codigo' => $codigo,
                'cliente_id' => $orden->cliente_id,
                'orden_trabajo_id' => $orden->id,
                'usuario_sistema_id' => $usuarioSistemaId,
                'fecha_emision' => now(),
                'estado' => 'EMITIDA',
                'subtotal' => 0,
                'descuento' => 0,
                'total' => 0,
                'monto_pagado' => 0,
                'saldo' => 0,
            ]);

            foreach ($lineasServicios as $linea) {
                NotaVentaLinea::create([
                    'nota_venta_id' => $nota->id,
                    'servicio_catalogo_id' => $linea->servicio_catalogo_id,
                    'repuesto_id' => null,
                    'descripcion' => $linea->servicioCatalogo->nombre,
                    'cantidad' => $linea->cantidad,
                    'precio_unitario' => $linea->precio_unitario,
                    'descuento' => $linea->descuento,
                    'subtotal' => $linea->subtotal,
                ]);
            }

            foreach ($lineasRepuestos as $linea) {
                NotaVentaLinea::create([
                    'nota_venta_id' => $nota->id,
                    'servicio_catalogo_id' => null,
                    'repuesto_id' => $linea->repuesto_id,
                    'descripcion' => $linea->repuesto->nombre,
                    'cantidad' => $linea->cantidad,
                    'precio_unitario' => $linea->precio_unitario,
                    'descuento' => $linea->descuento,
                    'subtotal' => $linea->subtotal,
                ]);
            }

            $nota->recalcularTotales();

            return $nota->fresh();
        });
    }
}
