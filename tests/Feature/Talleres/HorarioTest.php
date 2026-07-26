<?php

use App\Http\Requests\Talleres\GuardarHorarioTallerRequest;
use App\Models\Taller;
use App\Models\TallerHorario;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;

it('rechaza en BD un horario con hora_cierre anterior o igual a hora_apertura', function () {
    $taller = Taller::factory()->create();

    expect(fn () => TallerHorario::factory()->create([
        'taller_id' => $taller->id,
        'hora_apertura' => '18:00',
        'hora_cierre' => '08:00',
    ]))->toThrow(QueryException::class);
});

it('rechaza en BD un horario abierto sin hora_apertura ni hora_cierre', function () {
    $taller = Taller::factory()->create();

    expect(fn () => TallerHorario::factory()->create([
        'taller_id' => $taller->id,
        'cerrado' => false,
        'hora_apertura' => null,
        'hora_cierre' => null,
    ]))->toThrow(QueryException::class);
});

it('permite un horario cerrado sin horas', function () {
    $taller = Taller::factory()->create();

    $horario = TallerHorario::factory()->cerrado()->create(['taller_id' => $taller->id, 'dia_semana' => 7]);

    expect($horario->cerrado)->toBeTrue();
});

it('rechaza en BD dos horarios para el mismo taller y día de semana', function () {
    $taller = Taller::factory()->create();
    TallerHorario::factory()->create(['taller_id' => $taller->id, 'dia_semana' => 1]);

    expect(fn () => TallerHorario::factory()->create(['taller_id' => $taller->id, 'dia_semana' => 1]))
        ->toThrow(QueryException::class);
});

it('el Form Request exige hora_apertura y hora_cierre cuando no está cerrado', function () {
    $request = new GuardarHorarioTallerRequest;
    $request->merge(['taller_id' => Taller::factory()->create()->id, 'dia_semana' => 1, 'cerrado' => false]);

    $validator = Validator::make($request->all(), $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('hora_apertura'))->toBeTrue()
        ->and($validator->errors()->has('hora_cierre'))->toBeTrue();
});

it('el Form Request no exige horas cuando está cerrado', function () {
    $request = new GuardarHorarioTallerRequest;
    $request->merge(['taller_id' => Taller::factory()->create()->id, 'dia_semana' => 1, 'cerrado' => true]);

    $validator = Validator::make($request->all(), $request->rules());

    expect($validator->fails())->toBeFalse();
});

it('el Form Request rechaza hora_cierre anterior a hora_apertura', function () {
    $request = new GuardarHorarioTallerRequest;
    $request->merge([
        'taller_id' => Taller::factory()->create()->id,
        'dia_semana' => 1,
        'cerrado' => false,
        'hora_apertura' => '18:00',
        'hora_cierre' => '08:00',
    ]);

    $validator = Validator::make($request->all(), $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('hora_cierre'))->toBeTrue();
});

it('el Form Request rechaza un día de semana ya usado por ese taller', function () {
    $taller = Taller::factory()->create();
    TallerHorario::factory()->create(['taller_id' => $taller->id, 'dia_semana' => 3]);

    $request = new GuardarHorarioTallerRequest;
    $request->merge([
        'taller_id' => $taller->id,
        'dia_semana' => 3,
        'cerrado' => false,
        'hora_apertura' => '08:00',
        'hora_cierre' => '18:00',
    ]);

    $validator = Validator::make($request->all(), $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('dia_semana'))->toBeTrue();
});
