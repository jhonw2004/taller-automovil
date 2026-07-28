<?php

namespace Database\Factories;

use App\Models\OrdenTrabajo;
use App\Models\OrdenTrabajoHistorialEstado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrdenTrabajoHistorialEstado>
 */
class OrdenTrabajoHistorialEstadoFactory extends Factory
{
    protected $model = OrdenTrabajoHistorialEstado::class;

    public function definition(): array
    {
        return [
            'orden_trabajo_id' => OrdenTrabajo::factory(),
            'estado_anterior' => null,
            'estado_nuevo' => 'PENDIENTE',
            'usuario_sistema_id' => null,
            'observacion' => null,
        ];
    }
}
