<?php

namespace App\Filament\Erp\Resources\AsignacionRolResource\Pages;

use App\Actions\Roles\AsignarRolAction;
use App\Exceptions\BusinessException;
use App\Filament\Erp\Resources\AsignacionRolResource;
use App\Models\Rol;
use App\Models\UsuarioSistema;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class CreateAsignacionRol extends CreateRecord
{
    protected static string $resource = AsignacionRolResource::class;

    /**
     * No hace `Eloquent::create()`: reusa `AsignarRolAction` (ya valida ámbito, usuario/rol
     * activos, duplicados) en vez de duplicar esa lógica aquí.
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(AsignarRolAction::class)->execute(
                usuario: UsuarioSistema::findOrFail($data['usuario_sistema_id']),
                rol: Rol::findOrFail($data['rol_id']),
                tallerId: session('taller_activo_id'),
                asignadoPor: Filament::auth()->user(),
                vigenteDesde: $data['vigente_desde'] ?? null,
                vigenteHasta: $data['vigente_hasta'] ?? null,
            );
        } catch (BusinessException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();

            throw (new Halt)->rollBackDatabaseTransaction();
        }
    }
}
