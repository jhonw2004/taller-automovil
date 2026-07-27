<?php

namespace App\Filament\Erp\Resources\EmpleadoResource\Pages;

use App\Actions\Empleados\VincularAccesoEmpleadoAction;
use App\Exceptions\BusinessException;
use App\Filament\Erp\Resources\EmpleadoResource;
use App\Models\Rol;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditEmpleado extends EditRecord
{
    protected static string $resource = EmpleadoResource::class;

    /**
     * Si el empleado editado todavía no tiene acceso y el toggle se activó en este guardado,
     * otorga el acceso vía `VincularAccesoEmpleadoAction` (mismo paso que usa la creación con
     * acceso desde cero) — así un "empleado sin acceso" puede recibirlo después sin tener que
     * recrearlo. Si ya tenía acceso, el toggle queda deshabilitado en el formulario y este
     * bloque no se ejecuta.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $tieneAcceso = (bool) ($data['tiene_acceso'] ?? false);
        $username = $data['username'] ?? null;
        $rolId = $data['rol_id'] ?? null;
        $datosEmpleado = Arr::except($data, ['tiene_acceso', 'username', 'rol_id']);

        $record->update($datosEmpleado);

        if ($record->usuario_sistema_id === null && $tieneAcceso) {
            try {
                $resultado = app(VincularAccesoEmpleadoAction::class)->execute(
                    empleado: $record,
                    username: $username,
                    rol: Rol::findOrFail($rolId),
                    asignadoPor: Filament::auth()->user(),
                );
            } catch (BusinessException $exception) {
                Notification::make()->danger()->title($exception->getMessage())->send();

                throw (new Halt)->rollBackDatabaseTransaction();
            }

            Notification::make()
                ->success()
                ->title('Acceso otorgado')
                ->body("Usuario: {$resultado['usuario']->username}. Contraseña temporal: {$resultado['password_temporal']} (guárdala ahora, no se volverá a mostrar).")
                ->persistent()
                ->send();
        }

        return $record->fresh();
    }
}
