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
 * NOTA: la asignación del rol OWNER para el taller (`$usuario->assignRole('owner')`) queda
 * pendiente de conectar aquí hasta que exista el catálogo de roles de sistema
 * (spec 002-roles-permisos + seeding de 017-infraestructura-sistema).
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
