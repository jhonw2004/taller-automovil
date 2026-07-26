<?php

namespace Database\Seeders;

use App\Actions\Identidad\GenerarCredencialInicialAction;
use App\Actions\Roles\AsignarRolAction;
use App\Models\Rol;
use App\Models\UsuarioSistema;
use Illuminate\Database\Seeder;

/**
 * Primer usuario SUPER_ADMIN del sistema. `AsignarRolAction` recibe `$asignadoPor = null`
 * a propósito: es el único caso legítimo de bootstrap, no existe todavía ningún super admin
 * que pueda "asignar" el primero.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (UsuarioSistema::where('username', 'superadmin')->exists()) {
            $this->command->info('Super admin ya existe, se omite.');

            return;
        }

        $resultado = app(GenerarCredencialInicialAction::class)->execute(
            username: 'superadmin',
            nombre: 'Super Admin',
            email: 'superadmin@tallerautomoviles.bo',
        );

        $rolSuperAdmin = Rol::where('slug', 'super-admin')->whereNull('taller_id')->firstOrFail();

        app(AsignarRolAction::class)->execute(
            usuario: $resultado['usuario'],
            rol: $rolSuperAdmin,
            tallerId: null,
            asignadoPor: null,
        );

        $this->command->warn('=== SUPER ADMIN CREADO ===');
        $this->command->warn('Username: superadmin');
        $this->command->warn("Password: {$resultado['password_temporal']}");
        $this->command->warn('==========================');
    }
}
