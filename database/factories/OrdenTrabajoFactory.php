<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrdenTrabajo>
 */
class OrdenTrabajoFactory extends Factory
{
    protected $model = OrdenTrabajo::class;

    public function definition(): array
    {
        return [
            // `cliente_id` primero: `taller_id`/`vehiculo_id` se derivan de él para que los tres
            // queden consistentes por defecto, mismo patrón que `VehiculoFactory` (007).
            'cliente_id' => Cliente::factory(),
            'taller_id' => fn (array $attributes) => Cliente::find($attributes['cliente_id'])->taller_id,
            'vehiculo_id' => fn (array $attributes) => Vehiculo::factory()->create([
                'cliente_id' => $attributes['cliente_id'],
            ])->id,
            'codigo' => 'OT-'.date('Y').'-'.fake()->unique()->numerify('###'),
            'estado' => 'PENDIENTE',
            'prioridad' => 'MEDIA',
            'fecha_recepcion' => now(),
            'subtotal_servicios' => 0,
            'subtotal_repuestos' => 0,
            'descuento' => 0,
            'total' => 0,
        ];
    }
}
