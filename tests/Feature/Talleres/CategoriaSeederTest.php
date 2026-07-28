<?php

use App\Models\Categoria;
use Database\Seeders\CategoriaSeeder;

it('CategoriaSeeder crea las 6 categorias de ejemplo y es idempotente', function () {
    (new CategoriaSeeder)->run();
    (new CategoriaSeeder)->run();

    expect(Categoria::count())->toBe(6);
    expect(Categoria::pluck('slug')->sort()->values()->all())
        ->toBe([
            'diagnostico-computarizado',
            'electricidad-automotriz',
            'hojalateria-y-pintura',
            'mecanica-general',
            'neumaticos',
            'tuning',
        ]);
});
