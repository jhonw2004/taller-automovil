<?php

namespace Database\Factories;

use App\Models\InventarioMovimiento;
use App\Models\Repuesto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventarioMovimiento>
 *
 * Uso directo (sin pasar por `RegistrarMovimientoInventarioAction`) solo para tests que ejercitan
 * el modelo/tabla en aislamiento (constraints de BD, aislamiento por taller). Los tests de reglas
 * de negocio (calculo de stock, rechazo de stock negativo) deben usar la Action real.
 */
class InventarioMovimientoFactory extends Factory
{
    protected $model = InventarioMovimiento::class;

    public function definition(): array
    {
        $repuesto = Repuesto::factory()->create();
        $cantidad = fake()->randomFloat(3, 1, 20);

        return [
            'taller_id' => $repuesto->taller_id,
            'repuesto_id' => $repuesto->id,
            'tipo_movimiento' => 'ENTRADA',
            'cantidad' => $cantidad,
            'stock_anterior' => 0,
            'stock_resultante' => $cantidad,
            'costo_unitario' => fake()->optional()->randomFloat(2, 5, 100),
            'proveedor_id' => null,
            'orden_trabajo_repuesto_id' => null,
            'usuario_sistema_id' => null,
            'motivo' => fake()->optional()->sentence(),
            'referencia' => null,
        ];
    }
}
