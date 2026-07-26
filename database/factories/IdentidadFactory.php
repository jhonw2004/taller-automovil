<?php

namespace Database\Factories;

use App\Models\Identidad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Identidad>
 */
class IdentidadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo' => 'SISTEMA',
            'email' => fake()->unique()->safeEmail(),
            'estado' => 'ACTIVO',
        ];
    }

    public function marketplace(): static
    {
        return $this->state(['tipo' => 'MARKETPLACE']);
    }
}
