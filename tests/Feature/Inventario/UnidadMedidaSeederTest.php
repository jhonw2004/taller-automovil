<?php

use App\Models\UnidadMedida;
use Database\Seeders\UnidadMedidaSeeder;

it('UnidadMedidaSeeder crea las 4 unidades base y es idempotente', function () {
    (new UnidadMedidaSeeder)->run();
    (new UnidadMedidaSeeder)->run();

    expect(UnidadMedida::count())->toBe(4);
    expect(UnidadMedida::activos()->pluck('nombre')->sort()->values()->all())
        ->toBe(['kilo', 'litro', 'metro', 'unidad']);
});
