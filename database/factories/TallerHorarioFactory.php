<?php

namespace Database\Factories;

use App\Models\Taller;
use App\Models\TallerHorario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TallerHorario>
 */
class TallerHorarioFactory extends Factory
{
    protected $model = TallerHorario::class;

    public function definition(): array
    {
        return [
            'taller_id' => Taller::factory(),
            'dia_semana' => fake()->unique()->numberBetween(1, 7),
            'hora_apertura' => '08:00',
            'hora_cierre' => '18:00',
            'cerrado' => false,
        ];
    }

    public function cerrado(): static
    {
        return $this->state(fn () => [
            'hora_apertura' => null,
            'hora_cierre' => null,
            'cerrado' => true,
        ]);
    }
}
