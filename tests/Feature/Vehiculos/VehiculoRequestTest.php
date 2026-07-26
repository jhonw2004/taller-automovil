<?php

use App\Http\Requests\Vehiculos\VehiculoRequest;
use App\Models\Cliente;
use App\Models\Taller;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\Validator;

function validarVehiculo(array $datos): Illuminate\Contracts\Validation\Validator
{
    $request = new VehiculoRequest;
    $request->merge($datos);

    // `prepareForValidation()` (normaliza la placa) solo se invoca automáticamente cuando el
    // Form Request se resuelve vía el contenedor de una request HTTP real — se llama a mano
    // para probarlo sin necesidad de una ruta/controlador real (el Request no tiene uno todavía).
    (new ReflectionMethod($request, 'prepareForValidation'))->invoke($request);

    return Validator::make($request->all(), $request->rules());
}

it('normaliza la placa con guion/minusculas antes de validar el formato', function () {
    $taller = Taller::factory()->create();
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    session(['taller_activo_id' => $taller->id]);

    $validator = validarVehiculo([
        'cliente_id' => $cliente->id,
        'placa' => 'abc-123',
        'tipo_vehiculo' => 'AUTO',
    ]);

    expect($validator->fails())->toBeFalse()
        ->and($validator->validated()['placa'])->toBe('ABC123');
});

it('rechaza una placa que no cumple el regex incluso normalizada', function () {
    $taller = Taller::factory()->create();
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    session(['taller_activo_id' => $taller->id]);

    $validator = validarVehiculo([
        'cliente_id' => $cliente->id,
        'placa' => '123-ABC',
        'tipo_vehiculo' => 'AUTO',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('placa'))->toBeTrue();
});

it('rechaza una placa ya usada por otro vehiculo del mismo taller', function () {
    $taller = Taller::factory()->create();
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    Vehiculo::factory()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id, 'placa' => 'ABC123']);
    session(['taller_activo_id' => $taller->id]);

    $validator = validarVehiculo([
        'cliente_id' => $cliente->id,
        'placa' => 'ABC-123',
        'tipo_vehiculo' => 'AUTO',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('placa'))->toBeTrue();
});

it('rechaza un cliente que pertenece a otro taller', function () {
    $tallerActivo = Taller::factory()->create();
    $otroTaller = Taller::factory()->create();
    $clienteDeOtroTaller = Cliente::factory()->create(['taller_id' => $otroTaller->id]);
    session(['taller_activo_id' => $tallerActivo->id]);

    $validator = validarVehiculo([
        'cliente_id' => $clienteDeOtroTaller->id,
        'placa' => 'ABC123',
        'tipo_vehiculo' => 'AUTO',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('cliente_id'))->toBeTrue();
});

it('rechaza un anio fuera de rango y un kilometraje negativo', function () {
    $taller = Taller::factory()->create();
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    session(['taller_activo_id' => $taller->id]);

    $validator = validarVehiculo([
        'cliente_id' => $cliente->id,
        'placa' => 'ABC123',
        'tipo_vehiculo' => 'AUTO',
        'anio' => 1800,
        'kilometraje' => -5,
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('anio'))->toBeTrue()
        ->and($validator->errors()->has('kilometraje'))->toBeTrue();
});
