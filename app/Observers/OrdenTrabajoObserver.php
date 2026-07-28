<?php

namespace App\Observers;

use App\Models\OrdenTrabajo;
use App\Notifications\OrdenAsignadaNotification;
use App\Notifications\OrdenCambioEstadoNotification;

/**
 * Primer Observer del proyecto (hasta 013 toda la lógica de negocio vivía en Actions). Se elige
 * Observer y no un hook dentro de cada Action porque `empleado_asignado_id`/`estado` cambian por
 * más de un camino: creación (`CrearOrdenTrabajoAction`), edición estándar de Filament sin Action
 * dedicada (`EditOrdenTrabajo`, 011-plan.md: "sin Action dedicada, canEdit() ya bloquea ANULADA"),
 * `CambiarEstadoOrdenAction` y `AnularOrdenTrabajoAction` (que también escribe `estado` directo).
 * Un Observer en `updated()`/`created()` cubre los cuatro sin duplicar la notificación en cada uno.
 */
class OrdenTrabajoObserver
{
    public function created(OrdenTrabajo $orden): void
    {
        if ($orden->empleado_asignado_id !== null) {
            OrdenAsignadaNotification::enviar($orden);
        }
    }

    public function updated(OrdenTrabajo $orden): void
    {
        if ($orden->wasChanged('empleado_asignado_id') && $orden->empleado_asignado_id !== null) {
            OrdenAsignadaNotification::enviar($orden);
        }

        // Solo transiciones reales, no la asignación inicial de estado en creación (ese caso lo
        // cubre `created()` únicamente para `orden.asignada`, no para `orden.cambio_estado` —
        // 014-plan.md dice "orden cambia de estado", no "orden se crea en un estado").
        if ($orden->wasChanged('estado')) {
            OrdenCambioEstadoNotification::enviar($orden);
        }
    }
}
