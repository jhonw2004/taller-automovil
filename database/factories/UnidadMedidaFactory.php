<?php

namespace Database\Factories;

use App\Models\UnidadMedida;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnidadMedida>
 */
class UnidadMedidaFactory extends Factory
{
    protected $model = UnidadMedida::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->randomElement(['Unidad', 'Litro', 'Metro', 'Kilogramo', 'Caja', 'Par', 'Galón']),
            'simbolo' => fake()->unique()->lexify('??'),
            'descripcion' => fake()->optional()->sentence(),
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
