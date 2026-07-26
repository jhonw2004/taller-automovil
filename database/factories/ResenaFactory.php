<?php

namespace Database\Factories;

use App\Models\Resena;
use App\Models\Taller;
use App\Models\UsuarioMarketplace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resena>
 */
class ResenaFactory extends Factory
{
    protected $model = Resena::class;

    public function definition(): array
    {
        return [
            'usuario_marketplace_id' => UsuarioMarketplace::factory(),
            'taller_id' => Taller::factory(),
            'calificacion' => fake()->numberBetween(1, 5),
            'comentario' => fake()->optional()->sentence(),
            'estado' => 'PUBLICADA',
        ];
    }

    public function oculta(): static
    {
        return $this->state(fn () => ['estado' => 'OCULTA']);
    }

    public function reportada(): static
    {
        return $this->state(fn () => ['estado' => 'REPORTADA']);
    }
}
