<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Taller;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'taller_id' => Taller::factory(),
            'codigo' => 'CLI-'.fake()->unique()->numerify('#####'),
            'tipo_persona' => 'NATURAL',
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName(),
            'razon_social' => null,
            'nit_ci' => fake()->optional()->numerify('#######'),
            'telefono' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'direccion' => fake()->optional()->streetAddress(),
            'observaciones' => fake()->optional()->sentence(),
            'activo' => true,
        ];
    }

    public function juridica(): static
    {
        return $this->state(fn () => [
            'tipo_persona' => 'JURIDICA',
            'apellido' => null,
            'razon_social' => fake()->company(),
        ]);
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
