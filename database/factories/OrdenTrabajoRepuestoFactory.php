<?php

namespace Database\Factories;

use App\Models\OrdenTrabajo;
use App\Models\OrdenTrabajoRepuesto;
use App\Models\Repuesto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrdenTrabajoRepuesto>
 */
class OrdenTrabajoRepuestoFactory extends Factory
{
    protected $model = OrdenTrabajoRepuesto::class;

    public function definition(): array
    {
        $cantidad = 1;
        $precioUnitario = fake()->randomFloat(2, 10, 300);
        $descuento = 0;

        return [
            'orden_trabajo_id' => OrdenTrabajo::factory(),
            'repuesto_id' => Repuesto::factory(),
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'descuento' => $descuento,
            'subtotal' => $cantidad * $precioUnitario - $descuento,
            'estado' => 'PENDIENTE',
        ];
    }
}
