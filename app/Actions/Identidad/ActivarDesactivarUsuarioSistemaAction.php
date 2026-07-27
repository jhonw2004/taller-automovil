<?php

namespace App\Actions\Identidad;

use App\Exceptions\BusinessException;
use App\Models\AsignacionRol;
use App\Models\UsuarioSistema;

/**
 * El admin de un taller activa/desactiva un usuario de su propio taller (008-spec.md).
 * `activo` es una columna global de `UsuarioSistema` (usada por `canAccessPanel()`), no por
 * taller: desactivar aquí le quita el acceso al usuario en todos los talleres donde tenga
 * asignación, no solo en el activo. No se modela un "activo por taller" porque el esquema de
 * `empleados`/`asignaciones_rol` no lo distingue (brecha documentada en `resume.md`, aceptable
 * para el MVP ya que un usuario perteneciendo a más de un taller es un caso raro).
 */
class ActivarDesactivarUsuarioSistemaAction
{
    public function execute(UsuarioSistema $usuario, int $tallerId, bool $activo): UsuarioSistema
    {
        $perteneceAlTaller = $usuario->asignacionesVigentes()
            ->contains(fn (AsignacionRol $asignacion) => $asignacion->taller_id === $tallerId);

        if (! $perteneceAlTaller) {
            throw new BusinessException('El usuario no pertenece a este taller.');
        }

        $usuario->update(['activo' => $activo]);

        return $usuario;
    }
}
