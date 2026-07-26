<?php

namespace App\Actions\Talleres;

use App\Exceptions\BusinessException;
use App\Models\AsignacionRol;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use Illuminate\Support\Facades\DB;

/**
 * Auditoría vía `activity()` (spatie/laravel-activitylog), igual que
 * `CambiarVisibilidadTallerAction` — ver esa clase para el porqué de no usar `auditoria_eventos`
 * todavía.
 */
class CambiarPropietarioTallerAction
{
    public function execute(Taller $taller, UsuarioSistema $nuevoPropietario, UsuarioSistema $actor): Taller
    {
        $esPropietarioActual = $taller->propietario_usuario_sistema_id === $actor->id;

        if (! $actor->esSuperAdmin() && ! $esPropietarioActual) {
            throw new BusinessException('Solo el super administrador o el propietario actual pueden cambiar el propietario del taller.');
        }

        $tieneRolOwner = $nuevoPropietario->asignacionesVigentes()
            ->contains(fn (AsignacionRol $asignacion) => $asignacion->rol->slug === 'owner' && $asignacion->taller_id === $taller->id);

        if (! $tieneRolOwner) {
            throw new BusinessException('El nuevo propietario debe tener una asignación de rol OWNER vigente en este taller.');
        }

        return DB::transaction(function () use ($taller, $nuevoPropietario, $actor) {
            $anterior = $taller->propietario_usuario_sistema_id;

            $taller->propietario_usuario_sistema_id = $nuevoPropietario->id;
            $taller->save();

            activity()
                ->causedBy($actor)
                ->performedOn($taller)
                ->withProperties(['anterior' => $anterior, 'nuevo' => $nuevoPropietario->id])
                ->log('cambio_propietario_taller');

            return $taller;
        });
    }
}
