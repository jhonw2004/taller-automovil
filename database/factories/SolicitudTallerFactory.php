<?php

namespace Database\Factories;

use App\Models\SolicitudTaller;
use App\Models\Taller;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SolicitudTaller>
 */
class SolicitudTallerFactory extends Factory
{
    protected $model = SolicitudTaller::class;

    public function definition(): array
    {
        return [
            'token_publico' => (string) Str::uuid(),
            'estado' => 'PENDIENTE',
            'solicitante_nombre' => fake()->name(),
            'solicitante_email' => fake()->unique()->safeEmail(),
            'solicitante_telefono' => fake()->phoneNumber(),
            'taller_nombre' => fake()->unique()->company(),
            'taller_direccion' => fake()->streetAddress(),
            'lat' => fake()->latitude(-17.9, -17.7),
            'lon' => fake()->longitude(-63.3, -63.1),
            'enviada_at' => now(),
        ];
    }

    public function enRevision(): static
    {
        return $this->state(fn () => ['estado' => 'EN_REVISION', 'revisada_at' => now()]);
    }

    public function aprobada(): static
    {
        return $this->state(fn () => ['estado' => 'APROBADA', 'aprobada_at' => now()]);
    }

    public function rechazada(): static
    {
        return $this->state(fn () => ['estado' => 'RECHAZADA', 'rechazada_at' => now(), 'motivo_rechazo' => fake()->sentence()]);
    }

    public function cancelada(): static
    {
        return $this->state(fn () => ['estado' => 'CANCELADA']);
    }

    public function completada(): static
    {
        return $this->state(fn () => [
            'estado' => 'COMPLETADA',
            'completada_at' => now(),
            'taller_id' => Taller::factory(),
        ]);
    }
}
