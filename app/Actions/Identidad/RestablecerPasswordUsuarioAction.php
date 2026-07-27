<?php

namespace App\Actions\Identidad;

use App\Exceptions\BusinessException;
use App\Models\AsignacionRol;
use App\Models\UsuarioSistema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * El admin de un taller restablece la contraseña de un usuario de su propio taller
 * (008-spec.md). Genera una nueva contraseña temporal (mismo criterio que
 * `GenerarCredencialInicialAction`) y fuerza el cambio en el próximo login; también limpia
 * cualquier bloqueo por intentos fallidos, ya que el admin ya verificó la identidad del
 * usuario para llegar a este flujo.
 */
class RestablecerPasswordUsuarioAction
{
    public function execute(UsuarioSistema $usuario, int $tallerId): string
    {
        $perteneceAlTaller = $usuario->asignacionesVigentes()
            ->contains(fn (AsignacionRol $asignacion) => $asignacion->taller_id === $tallerId);

        if (! $perteneceAlTaller) {
            throw new BusinessException('El usuario no pertenece a este taller.');
        }

        $passwordTemporal = Str::password(16, symbols: true);

        $usuario->credencialSistema->update([
            'password_hash' => Hash::make($passwordTemporal),
            'debe_cambiar_password' => true,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        return $passwordTemporal;
    }
}
