<?php

namespace Database\Factories;

use App\Models\CredencialSistema;
use App\Models\UsuarioSistema;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<CredencialSistema>
 */
class CredencialSistemaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_sistema_id' => UsuarioSistema::factory(),
            'password_hash' => Hash::make('Password123!'),
            'debe_cambiar_password' => false,
            'password_changed_at' => now(),
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ];
    }
}
