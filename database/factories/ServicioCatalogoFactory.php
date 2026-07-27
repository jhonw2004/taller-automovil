<?php

namespace Database\Factories;

use App\Models\ServicioCatalogo;
use App\Models\Taller;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServicioCatalogo>
 */
class ServicioCatalogoFactory extends Factory
{
    protected $model = ServicioCatalogo::class;

    public function definition(): array
    {
        return [
            'taller_id' => Taller::factory(),
            'codigo' => 'SRV-'.fake()->unique()->numerify('#####'),
            'nombre' => fake()->randomElement(['Cambio de aceite', 'Alineación', 'Frenos', 'Diagnóstico general', 'Afinación']),
            'descripcion' => fake()->optional()->sentence(),
            'precio_base' => fake()->randomFloat(2, 20, 500),
            'duracion_minutos' => fake()->optional()->numberBetween(15, 240),
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
