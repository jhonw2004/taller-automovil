<?php

namespace App\Actions\Talleres;

use App\Actions\Auditoria\RegistrarEventoAuditoriaAction;
use App\Exceptions\BusinessException;
use App\Models\AsignacionRol;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use Illuminate\Support\Facades\DB;

/**
 * Auditoría vía `RegistrarEventoAuditoriaAction` → `auditoria_eventos` (015-plan.md), igual que
 * `CambiarVisibilidadTallerAction`. `propietario_usuario_sistema_id` no está en el catálogo de
 * campos sensibles de `auditoria_talleres` (015-spec.md), así que este evento es el único
 * registro de este cambio — no hay doble tracking como en visibilidad/estado.
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

            app(RegistrarEventoAuditoriaAction::class)->execute(
                evento: 'cambio_propietario_taller',
                usuarioSistemaId: $actor->id,
                tallerId: $taller->id,
                entidad: $taller,
                datos: ['anterior' => $anterior, 'nuevo' => $nuevoPropietario->id],
            );

            return $taller;
        });
    }
}
