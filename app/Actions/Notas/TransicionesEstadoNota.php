<?php

namespace App\Actions\Notas;

/**
 * Máquina de estados de la nota de venta (012-spec.md). `PAGADA` no lista ningún destino — es
 * final, mismo criterio de lectura literal ya aplicado a `ENTREGADA` en `TransicionesEstadoOrden`
 * (011): la nota informal del spec ("no se recomienda anular salvo error") no es una transición
 * real porque no aparece como destino en la fila de la tabla. En la práctica, `PENDIENTE` (con
 * pagos parciales) sí lista `ANULADA` como transición permitida por la tabla, pero
 * `AnularNotaVentaAction` la bloquea igual por la regla independiente "si tiene pagos confirmados,
 * la anulación se bloquea" — la única transición a `ANULADA` que realmente prospera es desde
 * `EMITIDA` (monto_pagado siempre 0 en ese estado).
 */
class TransicionesEstadoNota
{
    public const MAPA = [
        'EMITIDA' => ['PENDIENTE', 'PAGADA', 'ANULADA'],
        'PENDIENTE' => ['PAGADA', 'ANULADA'],
        'PAGADA' => [],
        'ANULADA' => [],
    ];

    public static function permite(string $estadoActual, string $estadoNuevo): bool
    {
        return in_array($estadoNuevo, self::MAPA[$estadoActual] ?? [], true);
    }
}
