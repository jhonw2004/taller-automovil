<?php

namespace Database\Factories;

use App\Models\Rol;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rol>
 */
class RolFactory extends Factory
{
    protected $model = Rol::class;

    public function definition(): array
    {
        return [
            'taller_id' => null,
            'nombre' => fake()->unique()->jobTitle(),
            'slug' => fake()->unique()->slug(2),
            'descripcion' => null,
            'es_sistema' => false,
            'activo' => true,
        ];
    }
}
