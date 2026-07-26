<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehiculo>
 */
class VehiculoFactory extends Factory
{
    protected $model = Vehiculo::class;

    public function definition(): array
    {
        return [
            // `cliente_id` primero: `taller_id` se deriva del cliente ya resuelto para que ambos
            // queden consistentes por defecto (007-spec.md: "un vehículo pertenece... al mismo
            // taller que ese cliente").
            'cliente_id' => Cliente::factory(),
            'taller_id' => fn (array $attributes) => Cliente::find($attributes['cliente_id'])->taller_id,
            'placa' => fake()->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'marca' => fake()->randomElement(['Toyota', 'Nissan', 'Suzuki', 'Chevrolet', 'Kia']),
            'modelo' => fake()->word(),
            'anio' => fake()->numberBetween(2000, (int) date('Y')),
            'color' => fake()->safeColorName(),
            'vin' => fake()->optional()->bothify('#################'),
            'tipo_vehiculo' => 'AUTO',
            'kilometraje' => fake()->numberBetween(0, 200000),
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
