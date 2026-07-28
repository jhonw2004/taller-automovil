<?php

namespace Database\Seeders;

use App\Models\UnidadMedida;
use Illuminate\Database\Seeder;

/**
 * Unidades de medida base (017-infraestructura-sistema/spec.md), catálogo global.
 * Idempotente vía `updateOrCreate` por `nombre`, mismo patrón que `MetodoPagoSeeder`.
 */
class UnidadMedidaSeeder extends Seeder
{
    protected array $unidades = [
        ['nombre' => 'unidad', 'simbolo' => 'u'],
        ['nombre' => 'litro', 'simbolo' => 'L'],
        ['nombre' => 'metro', 'simbolo' => 'm'],
        ['nombre' => 'kilo', 'simbolo' => 'kg'],
    ];

    public function run(): void
    {
        foreach ($this->unidades as $unidad) {
            UnidadMedida::query()->updateOrCreate(
                ['nombre' => $unidad['nombre']],
                ['simbolo' => $unidad['simbolo'], 'activo' => true],
            );
        }
    }
}
