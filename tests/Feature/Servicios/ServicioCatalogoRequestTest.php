<?php

use App\Http\Requests\Servicios\ServicioCatalogoRequest;
use App\Models\ServicioCatalogo;
use App\Models\Taller;
use Illuminate\Support\Facades\Validator;

function validarServicio(array $datos): Illuminate\Contracts\Validation\Validator
{
    $request = new ServicioCatalogoRequest;
    $request->merge($datos);

    return Validator::make($request->all(), $request->rules());
}

it('exige codigo, nombre y precio_base', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);

    $validator = validarServicio([]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('codigo'))->toBeTrue()
        ->and($validator->errors()->has('nombre'))->toBeTrue()
        ->and($validator->errors()->has('precio_base'))->toBeTrue();
});

it('rechaza un precio_base negativo', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);

    $validator = validarServicio([
        'codigo' => 'SRV-001',
        'nombre' => 'Cambio de aceite',
        'precio_base' => -10,
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('precio_base'))->toBeTrue();
});

it('rechaza un codigo ya usado por otro servicio del mismo taller', function () {
    $taller = Taller::factory()->create();
    ServicioCatalogo::factory()->create(['taller_id' => $taller->id, 'codigo' => 'SRV-001']);
    session(['taller_activo_id' => $taller->id]);

    $validator = validarServicio([
        'codigo' => 'SRV-001',
        'nombre' => 'Cambio de aceite',
        'precio_base' => 50,
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('codigo'))->toBeTrue();
});

it('permite el mismo codigo si pertenece a otro taller', function () {
    $otroTaller = Taller::factory()->create();
    ServicioCatalogo::factory()->create(['taller_id' => $otroTaller->id, 'codigo' => 'SRV-001']);
    $tallerActivo = Taller::factory()->create();
    session(['taller_activo_id' => $tallerActivo->id]);

    $validator = validarServicio([
        'codigo' => 'SRV-001',
        'nombre' => 'Cambio de aceite',
        'precio_base' => 50,
    ]);

    expect($validator->fails())->toBeFalse();
});
