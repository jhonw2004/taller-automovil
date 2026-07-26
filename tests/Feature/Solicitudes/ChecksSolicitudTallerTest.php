<?php

use App\Models\SolicitudTaller;
use App\Models\Taller;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

it('el CHECK de BD rechaza una solicitud COMPLETADA sin taller_id', function () {
    expect(fn () => SolicitudTaller::factory()->create(['estado' => 'COMPLETADA', 'taller_id' => null]))
        ->toThrow(QueryException::class);
});

it('el CHECK de BD rechaza una solicitud RECHAZADA con taller_id asignado', function () {
    $taller = Taller::factory()->create();

    expect(fn () => SolicitudTaller::factory()->create(['estado' => 'RECHAZADA', 'taller_id' => $taller->id]))
        ->toThrow(QueryException::class);
});

it('el CHECK de BD permite COMPLETADA con taller_id', function () {
    $taller = Taller::factory()->create();

    $solicitud = SolicitudTaller::factory()->create(['estado' => 'COMPLETADA', 'taller_id' => $taller->id]);

    expect($solicitud->estado)->toBe('COMPLETADA');
});

it('el CHECK de BD rechaza un estado fuera del catálogo', function () {
    expect(fn () => SolicitudTaller::factory()->create(['estado' => 'INVENTADO']))
        ->toThrow(QueryException::class);
});

it('el CHECK de BD rechaza lat/lon fuera de rango cuando no son nulos', function () {
    expect(fn () => SolicitudTaller::factory()->create(['lat' => 100, 'lon' => -63.18]))
        ->toThrow(QueryException::class);
    expect(fn () => SolicitudTaller::factory()->create(['lat' => -17.78, 'lon' => 200]))
        ->toThrow(QueryException::class);
});

it('permite lat/lon nulos en la creación de la solicitud (ubicación opcional)', function () {
    $solicitud = SolicitudTaller::factory()->create(['lat' => null, 'lon' => null]);

    expect($solicitud->lat)->toBeNull()->and($solicitud->lon)->toBeNull();
});

it('el token_publico es único a nivel de BD', function () {
    $token = (string) Str::uuid();
    SolicitudTaller::factory()->create(['token_publico' => $token]);

    expect(fn () => SolicitudTaller::factory()->create(['token_publico' => $token]))
        ->toThrow(QueryException::class);
});

it('taller_id es único: una solicitud no puede compartir taller con otra', function () {
    $taller = Taller::factory()->create();
    SolicitudTaller::factory()->create(['estado' => 'COMPLETADA', 'taller_id' => $taller->id]);

    expect(fn () => SolicitudTaller::factory()->create(['estado' => 'COMPLETADA', 'taller_id' => $taller->id]))
        ->toThrow(QueryException::class);
});
