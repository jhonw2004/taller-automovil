<?php

namespace Database\Factories;

use App\Models\Empleado;
use App\Models\Taller;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Empleado>
 */
class EmpleadoFactory extends Factory
{
    protected $model = Empleado::class;

    public function definition(): array
    {
        return [
            'taller_id' => Taller::factory(),
            'usuario_sistema_id' => null,
            'codigo' => 'EMP-'.fake()->unique()->numerify('#####'),
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName(),
            'cargo' => fake()->jobTitle(),
            'telefono' => fake()->optional()->numerify('7#######'),
            'email' => fake()->optional()->safeEmail(),
            'fecha_ingreso' => fake()->date(),
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
