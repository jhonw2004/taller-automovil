<?php

namespace Database\Factories;

use App\Models\MetodoPago;
use App\Models\NotaVenta;
use App\Models\Pago;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pago>
 */
class PagoFactory extends Factory
{
    protected $model = Pago::class;

    public function definition(): array
    {
        return [
            'nota_venta_id' => NotaVenta::factory(),
            'metodo_pago_id' => MetodoPago::factory(),
            'usuario_sistema_id' => null,
            'fecha_pago' => now(),
            'monto' => fake()->randomFloat(2, 10, 100),
            'referencia' => null,
            'observacion' => null,
            'estado' => 'CONFIRMADO',
        ];
    }

    public function anulado(): static
    {
        return $this->state(fn () => ['estado' => 'ANULADO']);
    }
}
