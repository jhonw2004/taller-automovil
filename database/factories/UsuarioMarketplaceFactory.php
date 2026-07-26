<?php

namespace Database\Factories;

use App\Models\Identidad;
use App\Models\UsuarioMarketplace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsuarioMarketplace>
 */
class UsuarioMarketplaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'identidad_id' => Identidad::factory()->marketplace(),
            'nombre' => fake()->name(),
            'activo' => true,
        ];
    }
}
