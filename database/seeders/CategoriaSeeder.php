<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

/**
 * Categorías de taller de ejemplo (017-infraestructura-sistema/spec.md), catálogo global.
 * Idempotente vía `updateOrCreate` por `nombre` (el `slug` se deriva automáticamente
 * de `nombre` vía `HasSlug`, así que no puede ser la clave de búsqueda).
 */
class CategoriaSeeder extends Seeder
{
    protected array $categorias = [
        'Mecánica general',
        'Electricidad automotriz',
        'Neumáticos',
        'Diagnóstico computarizado',
        'Tuning',
        'Hojalatería y pintura',
    ];

    public function run(): void
    {
        foreach ($this->categorias as $nombre) {
            Categoria::query()->updateOrCreate(
                ['nombre' => $nombre],
                ['activo' => true],
            );
        }
    }
}
