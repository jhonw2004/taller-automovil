<?php

namespace App\Actions\Identidad;

use App\Actions\Auditoria\RegistrarAccesoAuditoriaAction;
use App\Exceptions\BusinessException;
use App\Models\HistorialPassword;
use App\Models\UsuarioSistema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Cambia la contraseña de un usuario sistema (primer login forzado, expiración, o cambio
 * voluntario). Rechaza reutilizar una de las últimas 5 contraseñas (spec 001).
 *
 * No se implementa vía `Illuminate\Auth\Events\PasswordReset` porque ese evento pertenece
 * al flujo de recuperación por email de Laravel, que está fuera de alcance del MVP para
 * usuario sistema (no hay tabla `password_reset_tokens`). Este cambio es siempre iniciado
 * por el propio usuario autenticado, no por un token de email — por el mismo motivo,
 * `auditoria_accesos` (PASSWORD_CHANGE/EXITOSO, 015-plan.md) se audita directo aquí en vez de
 * vía un listener de evento nativo.
 */
class CambiarPasswordAction
{
    private const HISTORIAL_MAXIMO = 5;

    public function execute(UsuarioSistema $usuario, string $nuevaPasswordPlano): void
    {
        $credencial = $usuario->credencialSistema;

        if (Hash::check($nuevaPasswordPlano, $credencial->password_hash)) {
            throw new BusinessException('La nueva contraseña no puede ser igual a la actual.');
        }

        $historialReciente = $usuario->historialPasswords()
            ->latest('id')
            ->limit(self::HISTORIAL_MAXIMO)
            ->get();

        foreach ($historialReciente as $anterior) {
            if (Hash::check($nuevaPasswordPlano, $anterior->password_hash)) {
                throw new BusinessException('Esa contraseña ya fue utilizada anteriormente, elige una diferente.');
            }
        }

        DB::transaction(function () use ($usuario, $credencial, $nuevaPasswordPlano) {
            HistorialPassword::create([
                'usuario_sistema_id' => $usuario->id,
                'password_hash' => $credencial->password_hash,
            ]);

            $idsAConservar = $usuario->historialPasswords()
                ->latest('id')
                ->limit(self::HISTORIAL_MAXIMO)
                ->pluck('id');

            $usuario->historialPasswords()->whereNotIn('id', $idsAConservar)->delete();

            $credencial->update([
                'password_hash' => Hash::make($nuevaPasswordPlano),
                'debe_cambiar_password' => false,
                'password_changed_at' => now(),
                'password_expires_at' => now()->addDays(90),
                'intentos_fallidos' => 0,
                'bloqueado_hasta' => null,
            ]);

            app(RegistrarAccesoAuditoriaAction::class)->execute(
                tipoAcceso: 'PASSWORD_CHANGE',
                resultado: 'EXITOSO',
                usuarioSistemaId: $usuario->id,
                identificador: $usuario->username,
            );
        });
    }
}
