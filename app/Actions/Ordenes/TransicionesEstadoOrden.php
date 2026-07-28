<?php

namespace App\Actions\Ordenes;

/**
 * Máquina de estados de la orden (011-spec.md). Tabla única compartida por `CambiarEstadoOrdenAction`
 * (transiciones normales) y `AnularOrdenTrabajoAction` (verifica que `ANULADA` esté entre los
 * destinos permitidos desde el estado actual antes de anular). `ENTREGADA` y `ANULADA` no listan
 * ningún destino — son finales; "Cualquier transición no listada se rechaza" (011-spec.md) se
 * aplica literalmente incluso a `ENTREGADA` pese a la nota informal del spec ("no se recomienda
 * anular salvo error"): esa nota no aparece como un destino real en la fila de la tabla, así que
 * no se implementa como una transición válida — no hay forma de anular una orden ENTREGADA en el
 * MVP.
 */
class TransicionesEstadoOrden
{
    public const MAPA = [
        'PENDIENTE' => ['EN_DIAGNOSTICO', 'ESPERANDO_APROBACION', 'EN_PROGRESO', 'ANULADA'],
        'EN_DIAGNOSTICO' => ['ESPERANDO_APROBACION', 'EN_PROGRESO', 'PAUSADA', 'ANULADA'],
        'ESPERANDO_APROBACION' => ['EN_PROGRESO', 'PAUSADA', 'ANULADA'],
        'EN_PROGRESO' => ['PAUSADA', 'COMPLETADA', 'ANULADA'],
        'PAUSADA' => ['EN_PROGRESO', 'ANULADA'],
        'COMPLETADA' => ['ENTREGADA', 'ANULADA'],
        'ENTREGADA' => [],
        'ANULADA' => [],
    ];

    public static function permite(string $estadoActual, string $estadoNuevo): bool
    {
        return in_array($estadoNuevo, self::MAPA[$estadoActual] ?? [], true);
    }
}
