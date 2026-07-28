<?php

namespace Database\Factories;

use App\Models\NotaVenta;
use App\Models\NotaVentaLinea;
use App\Models\Repuesto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotaVentaLinea>
 */
class NotaVentaLineaFactory extends Factory
{
    protected $model = NotaVentaLinea::class;

    public function definition(): array
    {
        $cantidad = 1;
        $precioUnitario = fake()->randomFloat(2, 10, 300);
        $descuento = 0;

        return [
            'nota_venta_id' => NotaVenta::factory(),
            'servicio_catalogo_id' => null,
            'repuesto_id' => Repuesto::factory(),
            'descripcion' => fake()->words(3, true),
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'descuento' => $descuento,
            'subtotal' => $cantidad * $precioUnitario - $descuento,
        ];
    }
}
