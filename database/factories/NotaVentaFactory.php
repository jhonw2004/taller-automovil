<?php

namespace Database\Factories;

use App\Models\NotaVenta;
use App\Models\Taller;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotaVenta>
 */
class NotaVentaFactory extends Factory
{
    protected $model = NotaVenta::class;

    public function definition(): array
    {
        return [
            'taller_id' => Taller::factory(),
            'codigo' => 'NV-'.date('Y').'-'.fake()->unique()->numerify('###'),
            'cliente_id' => null,
            'orden_trabajo_id' => null,
            'estado' => 'EMITIDA',
            'fecha_emision' => now(),
            'subtotal' => 0,
            'descuento' => 0,
            'total' => 0,
            'monto_pagado' => 0,
            'saldo' => 0,
        ];
    }
}
