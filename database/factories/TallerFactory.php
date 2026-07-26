<?php

namespace Database\Factories;

use App\Models\Taller;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Taller>
 */
class TallerFactory extends Factory
{
    protected $model = Taller::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->company(),
            'descripcion' => fake()->optional()->sentence(),
            'telefono' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->companyEmail(),
            'nit' => fake()->optional()->numerify('#########'),
            'direccion' => fake()->streetAddress(),
            'lat' => fake()->latitude(-17.9, -17.7),
            'lon' => fake()->longitude(-63.3, -63.1),
            'estado' => 'ACTIVO',
            'visible_en_mapa' => false,
        ];
    }

    public function visible(): static
    {
        return $this->state(fn () => ['estado' => 'ACTIVO', 'visible_en_mapa' => true]);
    }

    public function suspendido(): static
    {
        return $this->state(fn () => ['estado' => 'SUSPENDIDO']);
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['estado' => 'INACTIVO']);
    }
}
