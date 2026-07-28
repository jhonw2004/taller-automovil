<?php

namespace Database\Factories;

use App\Models\Proveedor;
use App\Models\Taller;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proveedor>
 */
class ProveedorFactory extends Factory
{
    protected $model = Proveedor::class;

    public function definition(): array
    {
        return [
            'taller_id' => Taller::factory(),
            'nombre' => fake()->company(),
            'contacto' => fake()->optional()->name(),
            'telefono' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->companyEmail(),
            'direccion' => fake()->optional()->address(),
            'nit' => null,
            'observaciones' => fake()->optional()->sentence(),
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
