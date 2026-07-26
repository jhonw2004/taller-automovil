<?php

namespace App\Actions\Roles;

use App\Exceptions\BusinessException;
use App\Models\AsignacionRol;
use App\Models\Rol;
use App\Models\UsuarioSistema;
use Illuminate\Support\Facades\DB;

/**
 * `$asignadoPor = null` está reservado para el bootstrap del sistema (seeders): no hay
 * super admin todavía cuando se crea el primero, así que ese caso omite las validaciones
 * de "quién puede asignar" y confía en el llamador (solo seeders deben pasar null).
 */
class AsignarRolAction
{
    public function execute(
        UsuarioSistema $usuario,
        Rol $rol,
        ?int $tallerId,
        ?UsuarioSistema $asignadoPor = null,
        ?string $vigenteDesde = null,
        ?string $vigenteHasta = null,
    ): AsignacionRol {
        if (! $usuario->activo) {
            throw new BusinessException('El usuario está inactivo.');
        }

        if (! $rol->activo) {
            throw new BusinessException('El rol está inactivo.');
        }

        if ($rol->taller_id !== null && $rol->taller_id !== $tallerId) {
            throw new BusinessException('El taller de la asignación no coincide con el taller del rol.');
        }

        if ($asignadoPor !== null && ! $asignadoPor->esSuperAdmin()) {
            if ($rol->slug === 'super-admin') {
                throw new BusinessException('Solo un super administrador puede asignar el rol SUPER_ADMIN.');
            }

            if ($tallerId === null) {
                throw new BusinessException('Solo un super administrador puede asignar roles globales.');
            }

            $tieneAccesoAlTaller = $asignadoPor->asignacionesVigentes()
                ->contains(fn (AsignacionRol $a) => $a->taller_id === $tallerId);

            if (! $tieneAccesoAlTaller) {
                throw new BusinessException('No puede asignar roles fuera de su propio taller.');
            }
        }

        $yaAsignado = AsignacionRol::query()
            ->where('usuario_sistema_id', $usuario->id)
            ->where('rol_id', $rol->id)
            ->where('taller_id', $tallerId)
            ->exists();

        if ($yaAsignado) {
            throw new BusinessException('El usuario ya tiene este rol asignado en este taller.');
        }

        return DB::transaction(fn () => AsignacionRol::create([
            'usuario_sistema_id' => $usuario->id,
            'rol_id' => $rol->id,
            'taller_id' => $tallerId,
            'activo' => true,
            'asignado_por_usuario_sistema_id' => $asignadoPor?->id,
            'vigente_desde' => $vigenteDesde ?? now()->toDateString(),
            'vigente_hasta' => $vigenteHasta,
        ]));
    }
}
