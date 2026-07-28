<?php

namespace Database\Factories;

use App\Models\OrdenTrabajo;
use App\Models\OrdenTrabajoNota;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrdenTrabajoNota>
 */
class OrdenTrabajoNotaFactory extends Factory
{
    protected $model = OrdenTrabajoNota::class;

    public function definition(): array
    {
        return [
            'orden_trabajo_id' => OrdenTrabajo::factory(),
            'usuario_sistema_id' => null,
            'tipo' => 'INTERNA',
            'nota' => fake()->sentence(),
        ];
    }
}
