<?php

namespace App\Actions\Identidad;

use App\Exceptions\BusinessException;
use App\Models\UsuarioSistema;
use Illuminate\Support\Facades\DB;

/**
 * El admin de un taller activa/desactiva el acceso de un usuario **a ese taller específico**
 * (008-spec.md). Actúa sobre `asignaciones_rol.activo` (por taller), no sobre el
 * `UsuarioSistema.activo` global — así un usuario con acceso a varios talleres puede perder
 * acceso a uno sin verse afectado en los demás. `UsuarioSistema.activo` sigue existiendo como
 * un "kill switch" global independiente (usado por `canAccessPanel()`/el provider de login),
 * pero esta Action ya no lo toca.
 *
 * La membresía se valida contra **todas** las asignaciones del usuario a ese taller (no solo las
 * vigentes vía `asignacionesVigentes()`), porque reactivar (`activo: true`) debe poder encontrar
 * una asignación que hoy está desactivada — filtrar por vigencia la habría dejado invisible.
 */
class ActivarDesactivarUsuarioSistemaAction
{
    public function execute(UsuarioSistema $usuario, int $tallerId, bool $activo): UsuarioSistema
    {
        $asignaciones = $usuario->asignacionesRol()->where('taller_id', $tallerId)->get();

        if ($asignaciones->isEmpty()) {
            throw new BusinessException('El usuario no pertenece a este taller.');
        }

        DB::transaction(function () use ($asignaciones, $activo) {
            foreach ($asignaciones as $asignacion) {
                $asignacion->update(['activo' => $activo]);
            }
        });

        return $usuario;
    }
}
