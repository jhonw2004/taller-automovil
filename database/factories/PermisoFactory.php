<?php

namespace Database\Factories;

use App\Models\Permiso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permiso>
 */
class PermisoFactory extends Factory
{
    protected $model = Permiso::class;

    public function definition(): array
    {
        $modulo = fake()->unique()->word();

        return [
            'modulo' => $modulo,
            'nombre' => fake()->sentence(3),
            'slug' => "{$modulo}.".fake()->word(),
            'descripcion' => null,
            'activo' => true,
        ];
    }
}
