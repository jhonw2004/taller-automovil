<?php

use App\Models\Taller;

it('genera el slug a partir del nombre', function () {
    $taller = Taller::factory()->create(['nombre' => 'Taller El Rápido']);

    expect($taller->slug)->toBe('taller-el-rapido');
});

it('agrega un sufijo numérico automático ante colisión de slug', function () {
    $primero = Taller::factory()->create(['nombre' => 'Taller El Rápido']);
    $segundo = Taller::factory()->create(['nombre' => 'Taller El Rápido']);

    expect($primero->slug)->toBe('taller-el-rapido')
        ->and($segundo->slug)->toBe('taller-el-rapido-1');
});
