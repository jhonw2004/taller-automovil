<?php

use App\Http\Requests\Clientes\ClienteRequest;
use App\Models\Cliente;
use App\Models\Taller;
use Illuminate\Support\Facades\Validator;

function validarCliente(array $datos): Illuminate\Contracts\Validation\Validator
{
    $request = new ClienteRequest;
    $request->merge($datos);

    return Validator::make($request->all(), $request->rules());
}

it('exige nombre y tipo_persona valido', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);

    $validator = validarCliente(['codigo' => 'CLI-001', 'tipo_persona' => 'INVALIDO']);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('nombre'))->toBeTrue()
        ->and($validator->errors()->has('tipo_persona'))->toBeTrue();
});

it('rechaza un codigo ya usado por otro cliente del mismo taller', function () {
    $taller = Taller::factory()->create();
    Cliente::factory()->create(['taller_id' => $taller->id, 'codigo' => 'CLI-001']);
    session(['taller_activo_id' => $taller->id]);

    $validator = validarCliente(['codigo' => 'CLI-001', 'tipo_persona' => 'NATURAL', 'nombre' => 'Juan']);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('codigo'))->toBeTrue();
});

it('permite el mismo codigo si pertenece a otro taller', function () {
    $otroTaller = Taller::factory()->create();
    Cliente::factory()->create(['taller_id' => $otroTaller->id, 'codigo' => 'CLI-001']);
    $tallerActivo = Taller::factory()->create();
    session(['taller_activo_id' => $tallerActivo->id]);

    $validator = validarCliente(['codigo' => 'CLI-001', 'tipo_persona' => 'NATURAL', 'nombre' => 'Juan']);

    expect($validator->fails())->toBeFalse();
});

it('rechaza un nit_ci ya usado por otro cliente del mismo taller', function () {
    $taller = Taller::factory()->create();
    Cliente::factory()->create(['taller_id' => $taller->id, 'nit_ci' => '1234567']);
    session(['taller_activo_id' => $taller->id]);

    $validator = validarCliente([
        'codigo' => 'CLI-002',
        'tipo_persona' => 'NATURAL',
        'nombre' => 'Juan',
        'nit_ci' => '1234567',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('nit_ci'))->toBeTrue();
});

it('no rechaza nit_ci en blanco aunque otro cliente tambien lo tenga en blanco', function () {
    $taller = Taller::factory()->create();
    Cliente::factory()->create(['taller_id' => $taller->id, 'nit_ci' => null]);
    session(['taller_activo_id' => $taller->id]);

    $validator = validarCliente([
        'codigo' => 'CLI-002',
        'tipo_persona' => 'NATURAL',
        'nombre' => 'Juan',
        'nit_ci' => '',
    ]);

    expect($validator->fails())->toBeFalse();
});

it('rechaza un email con formato invalido', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);

    $validator = validarCliente([
        'codigo' => 'CLI-001',
        'tipo_persona' => 'NATURAL',
        'nombre' => 'Juan',
        'email' => 'no-es-un-email',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue();
});
