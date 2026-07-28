<?php

namespace Database\Factories;

use App\Models\HistorialPassword;
use App\Models\UsuarioSistema;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<HistorialPassword>
 */
class HistorialPasswordFactory extends Factory
{
    protected $model = HistorialPassword::class;

    public function definition(): array
    {
        return [
            'usuario_sistema_id' => UsuarioSistema::factory(),
            'password_hash' => Hash::make(fake()->password(12)),
        ];
    }
}
