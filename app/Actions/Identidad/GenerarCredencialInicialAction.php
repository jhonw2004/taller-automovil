<?php

namespace App\Actions\Identidad;

use App\Models\CredencialSistema;
use App\Models\Identidad;
use App\Models\UsuarioSistema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * El Super Admin genera la credencial inicial del admin del taller (único usuario que crea
 * directamente, spec 001). Devuelve el usuario y la contraseña temporal en texto plano para
 * mostrarla una sola vez — el llamador es responsable de no persistirla ni loguearla.
 *
 * No asigna el rol OWNER aquí a propósito: esta Action solo crea identidad+usuario+credencial
 * (responsabilidad única). El llamador (p. ej. el flujo de aprobación de 004) compone con
 * `App\Actions\Roles\AsignarRolAction` para asignar OWNER en el taller correspondiente.
 */
class GenerarCredencialInicialAction
{
    public function execute(string $username, string $nombre, ?string $email = null, ?string $apellido = null): array
    {
        return DB::transaction(function () use ($username, $nombre, $email, $apellido) {
            $identidad = Identidad::create([
                'tipo' => 'SISTEMA',
                'email' => $email,
                'estado' => 'ACTIVO',
            ]);

            $usuario = UsuarioSistema::create([
                'identidad_id' => $identidad->id,
                'username' => $username,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'activo' => true,
            ]);

            $passwordTemporal = Str::password(16, symbols: true);

            CredencialSistema::create([
                'usuario_sistema_id' => $usuario->id,
                'password_hash' => Hash::make($passwordTemporal),
                'debe_cambiar_password' => true,
            ]);

            return [
                'usuario' => $usuario,
                'password_temporal' => $passwordTemporal,
            ];
        });
    }
}
