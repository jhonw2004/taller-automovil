<?php

namespace App\Filament\Erp\Resources\OrdenTrabajoResource\Pages;

use App\Actions\Ordenes\CrearOrdenTrabajoAction;
use App\Exceptions\BusinessException;
use App\Filament\Erp\Resources\OrdenTrabajoResource;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * No hace `OrdenTrabajo::create()` directo: la creación real pasa por `CrearOrdenTrabajoAction`
 * (código secuencial `OT-YYYY-###`, validación vehículo↔cliente↔taller, historial inicial).
 */
class CreateOrdenTrabajo extends CreateRecord
{
    protected static string $resource = OrdenTrabajoResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(CrearOrdenTrabajoAction::class)->execute(
                clienteId: (int) $data['cliente_id'],
                vehiculoId: (int) $data['vehiculo_id'],
                empleadoAsignadoId: $data['empleado_asignado_id'] ?? null,
                creadoPorUsuarioSistemaId: Filament::auth()->id(),
                datos: Arr::except($data, ['cliente_id', 'vehiculo_id', 'empleado_asignado_id']),
            );
        } catch (BusinessException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();

            throw (new Halt)->rollBackDatabaseTransaction();
        }
    }
}
