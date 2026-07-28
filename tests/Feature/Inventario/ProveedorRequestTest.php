<?php

use App\Http\Requests\Proveedores\ProveedorRequest;
use App\Models\Proveedor;
use App\Models\Taller;
use Illuminate\Support\Facades\Validator;

function validarProveedor(array $datos): Illuminate\Contracts\Validation\Validator
{
    $request = new ProveedorRequest;
    $request->merge($datos);

    return Validator::make($request->all(), $request->rules());
}

it('exige nombre', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);

    $validator = validarProveedor([]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('nombre'))->toBeTrue();
});

it('rechaza un nit ya usado por otro proveedor del mismo taller', function () {
    $taller = Taller::factory()->create();
    Proveedor::factory()->create(['taller_id' => $taller->id, 'nit' => '123456789']);
    session(['taller_activo_id' => $taller->id]);

    $validator = validarProveedor([
        'nombre' => 'Repuestos SRL',
        'nit' => '123456789',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('nit'))->toBeTrue();
});

it('permite el mismo nit si pertenece a otro taller', function () {
    $otroTaller = Taller::factory()->create();
    Proveedor::factory()->create(['taller_id' => $otroTaller->id, 'nit' => '123456789']);
    $tallerActivo = Taller::factory()->create();
    session(['taller_activo_id' => $tallerActivo->id]);

    $validator = validarProveedor([
        'nombre' => 'Repuestos SRL',
        'nit' => '123456789',
    ]);

    expect($validator->fails())->toBeFalse();
});

it('rechaza un email invalido', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);

    $validator = validarProveedor([
        'nombre' => 'Repuestos SRL',
        'email' => 'no-es-un-email',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue();
});
