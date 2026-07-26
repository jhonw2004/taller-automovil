<?php

namespace Database\Factories;

use App\Models\Identidad;
use App\Models\UsuarioSistema;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsuarioSistema>
 */
class UsuarioSistemaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'identidad_id' => Identidad::factory(),
            'username' => fake()->unique()->userName(),
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName(),
            'activo' => true,
        ];
    }
}
