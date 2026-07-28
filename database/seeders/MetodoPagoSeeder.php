<?php

namespace Database\Seeders;

use App\Models\MetodoPago;
use Illuminate\Database\Seeder;

/**
 * Métodos de pago base (013-pagos/tasks.md), catálogo global. Se desactivan, nunca se borran
 * (constitution.md §2) — el seeder es idempotente vía `updateOrCreate` por `slug`.
 */
class MetodoPagoSeeder extends Seeder
{
    protected array $metodos = [
        ['nombre' => 'Efectivo', 'slug' => 'efectivo'],
        ['nombre' => 'Tarjeta', 'slug' => 'tarjeta'],
        ['nombre' => 'QR', 'slug' => 'qr'],
        ['nombre' => 'Transferencia', 'slug' => 'transferencia'],
    ];

    public function run(): void
    {
        foreach ($this->metodos as $metodo) {
            MetodoPago::query()->updateOrCreate(
                ['slug' => $metodo['slug']],
                ['nombre' => $metodo['nombre'], 'activo' => true],
            );
        }
    }
}
