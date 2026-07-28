<?php

use App\Http\Requests\Repuestos\RepuestoRequest;
use App\Models\Repuesto;
use App\Models\Taller;
use Illuminate\Support\Facades\Validator;

function validarRepuesto(array $datos): Illuminate\Contracts\Validation\Validator
{
    $request = new RepuestoRequest;
    $request->merge($datos);

    return Validator::make($request->all(), $request->rules());
}

it('exige codigo, nombre, precio_costo y precio_venta', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);

    $validator = validarRepuesto([]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('codigo'))->toBeTrue()
        ->and($validator->errors()->has('nombre'))->toBeTrue()
        ->and($validator->errors()->has('precio_costo'))->toBeTrue()
        ->and($validator->errors()->has('precio_venta'))->toBeTrue();
});

it('rechaza precio_costo o precio_venta negativos', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);

    $validator = validarRepuesto([
        'codigo' => 'REP-001',
        'nombre' => 'Filtro de aceite',
        'precio_costo' => -1,
        'precio_venta' => -1,
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('precio_costo'))->toBeTrue()
        ->and($validator->errors()->has('precio_venta'))->toBeTrue();
});

it('rechaza un codigo ya usado por otro repuesto del mismo taller', function () {
    $taller = Taller::factory()->create();
    Repuesto::factory()->create(['taller_id' => $taller->id, 'codigo' => 'REP-001']);
    session(['taller_activo_id' => $taller->id]);

    $validator = validarRepuesto([
        'codigo' => 'REP-001',
        'nombre' => 'Filtro de aceite',
        'precio_costo' => 10,
        'precio_venta' => 20,
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('codigo'))->toBeTrue();
});

it('rechaza un codigo_barras ya usado por otro repuesto del mismo taller', function () {
    $taller = Taller::factory()->create();
    Repuesto::factory()->create(['taller_id' => $taller->id, 'codigo_barras' => '7501234567890']);
    session(['taller_activo_id' => $taller->id]);

    $validator = validarRepuesto([
        'codigo' => 'REP-002',
        'nombre' => 'Filtro de aceite',
        'codigo_barras' => '7501234567890',
        'precio_costo' => 10,
        'precio_venta' => 20,
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('codigo_barras'))->toBeTrue();
});

it('permite el mismo codigo si pertenece a otro taller', function () {
    $otroTaller = Taller::factory()->create();
    Repuesto::factory()->create(['taller_id' => $otroTaller->id, 'codigo' => 'REP-001']);
    $tallerActivo = Taller::factory()->create();
    session(['taller_activo_id' => $tallerActivo->id]);

    $validator = validarRepuesto([
        'codigo' => 'REP-001',
        'nombre' => 'Filtro de aceite',
        'precio_costo' => 10,
        'precio_venta' => 20,
    ]);

    expect($validator->fails())->toBeFalse();
});
