<?php

namespace Database\Factories;

use App\Models\Repuesto;
use App\Models\Taller;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Repuesto>
 */
class RepuestoFactory extends Factory
{
    protected $model = Repuesto::class;

    public function definition(): array
    {
        return [
            'taller_id' => Taller::factory(),
            'codigo' => 'REP-'.fake()->unique()->numerify('#####'),
            'nombre' => fake()->randomElement(['Filtro de aceite', 'Pastillas de freno', 'Batería 12V', 'Correa de distribución', 'Bujía']),
            'descripcion' => fake()->optional()->sentence(),
            'codigo_barras' => null,
            'unidad_medida_id' => null,
            'stock_actual' => 0,
            'stock_minimo' => fake()->randomFloat(3, 0, 10),
            'precio_costo' => fake()->randomFloat(2, 5, 300),
            'precio_venta' => fake()->randomFloat(2, 10, 500),
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
