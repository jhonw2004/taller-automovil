<?php

use App\Models\Taller;
use App\Models\TallerHorario;
use Illuminate\Support\Carbon;

/**
 * `Carbon::setTestNow()` congela "ahora" a mediodía (nunca cruza medianoche, consistente con
 * "sin horarios partidos" de 003-gestion-talleres/spec.md) — evita un test frágil que dependa
 * de la hora real de ejecución de la suite.
 */
beforeEach(function () {
    Carbon::setTestNow(Carbon::create(2026, 7, 20, 12, 0, 0, 'America/La_Paz')); // lunes
});

afterEach(function () {
    Carbon::setTestNow();
});

it('esta cerrado cuando el horario del dia tiene cerrado=true', function () {
    $taller = Taller::factory()->create();
    TallerHorario::factory()->cerrado()->create([
        'taller_id' => $taller->id,
        'dia_semana' => Carbon::now('America/La_Paz')->isoWeekday(),
    ]);

    expect($taller->fresh(['horarios'])->estaAbiertoAhora())->toBeFalse();
});

it('esta abierto cuando la hora actual esta dentro del rango de apertura/cierre', function () {
    $taller = Taller::factory()->create();
    TallerHorario::factory()->create([
        'taller_id' => $taller->id,
        'dia_semana' => Carbon::now('America/La_Paz')->isoWeekday(),
        'hora_apertura' => '08:00',
        'hora_cierre' => '18:00',
        'cerrado' => false,
    ]);

    expect($taller->fresh(['horarios'])->estaAbiertoAhora())->toBeTrue();
});

it('esta cerrado cuando la hora actual esta fuera del rango de apertura/cierre', function () {
    $taller = Taller::factory()->create();
    TallerHorario::factory()->create([
        'taller_id' => $taller->id,
        'dia_semana' => Carbon::now('America/La_Paz')->isoWeekday(),
        'hora_apertura' => '14:00',
        'hora_cierre' => '18:00',
        'cerrado' => false,
    ]);

    expect($taller->fresh(['horarios'])->estaAbiertoAhora())->toBeFalse();
});

it('esta cerrado cuando no hay horario definido para el dia de hoy', function () {
    $taller = Taller::factory()->create();

    expect($taller->fresh(['horarios'])->estaAbiertoAhora())->toBeFalse();
});
