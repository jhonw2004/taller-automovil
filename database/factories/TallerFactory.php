<?php

namespace Database\Factories;

use App\Models\Taller;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory sobre el prototipo actual de `talleres` (specs/017-infraestructura-sistema/plan.md §7
 * lo reemplaza más adelante). Solo cubre lo mínimo que otras specs necesitan para pruebas
 * (FK a un taller válido), no el esquema completo de 003-gestion-talleres.
 *
 * @extends Factory<Taller>
 */
class TallerFactory extends Factory
{
    protected $model = Taller::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->company(),
            'lat' => fake()->latitude(-17.9, -17.7),
            'lon' => fake()->longitude(-63.3, -63.1),
        ];
    }
}
