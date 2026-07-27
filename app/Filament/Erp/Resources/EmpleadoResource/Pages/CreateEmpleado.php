<?php

namespace App\Filament\Erp\Resources\EmpleadoResource\Pages;

use App\Actions\Empleados\CrearEmpleadoConAccesoAction;
use App\Actions\Empleados\CrearEmpleadoSinAccesoAction;
use App\Exceptions\BusinessException;
use App\Filament\Erp\Resources\EmpleadoResource;
use App\Models\Rol;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateEmpleado extends CreateRecord
{
    protected static string $resource = EmpleadoResource::class;

    /**
     * No hace `Empleado::create()` directo: el toggle "tiene_acceso" (no es una columna del
     * modelo) decide si se dispara `CrearEmpleadoSinAccesoAction` o `CrearEmpleadoConAccesoAction`
     * (ver `EmpleadoResource::form()`).
     */
    protected function handleRecordCreation(array $data): Model
    {
        $tallerId = session('taller_activo_id');
        $tieneAcceso = (bool) ($data['tiene_acceso'] ?? false);
        $username = $data['username'] ?? null;
        $rolId = $data['rol_id'] ?? null;
        $datosEmpleado = Arr::except($data, ['tiene_acceso', 'username', 'rol_id']);

        if (! $tieneAcceso) {
            return app(CrearEmpleadoSinAccesoAction::class)->execute($tallerId, $datosEmpleado);
        }

        try {
            $resultado = app(CrearEmpleadoConAccesoAction::class)->execute(
                tallerId: $tallerId,
                datosEmpleado: $datosEmpleado,
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
            ->title('Empleado creado con acceso')
            ->body("Usuario: {$resultado['usuario']->username}. Contraseña temporal: {$resultado['password_temporal']} (guárdala ahora, no se volverá a mostrar).")
            ->persistent()
            ->send();

        return $resultado['empleado'];
    }
}
