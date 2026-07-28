<?php

namespace Database\Factories;

use App\Models\OrdenTrabajo;
use App\Models\OrdenTrabajoServicio;
use App\Models\ServicioCatalogo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrdenTrabajoServicio>
 */
class OrdenTrabajoServicioFactory extends Factory
{
    protected $model = OrdenTrabajoServicio::class;

    public function definition(): array
    {
        $cantidad = 1;
        $precioUnitario = fake()->randomFloat(2, 10, 300);
        $descuento = 0;

        return [
            'orden_trabajo_id' => OrdenTrabajo::factory(),
            'servicio_catalogo_id' => ServicioCatalogo::factory(),
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'descuento' => $descuento,
            'subtotal' => $cantidad * $precioUnitario - $descuento,
            'estado' => 'PENDIENTE',
        ];
    }
}
