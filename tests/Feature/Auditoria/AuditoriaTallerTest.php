<?php

use App\Exceptions\BusinessException;
use App\Models\AuditoriaTaller;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use Illuminate\Database\QueryException;

it('audita la creacion de un taller con geom_new pero sin geom_old', function () {
    $taller = Taller::factory()->create(['lat' => -17.78, 'lon' => -63.18, 'nombre' => 'Taller Nuevo']);

    $evento = AuditoriaTaller::where('taller_id', $taller->id)->where('operacion', 'INSERT')->first();

    expect($evento)->not->toBeNull();
    expect($evento->datos_old)->toBeNull();
    expect($evento->datos_new['nombre'])->toBe('Taller Nuevo');
    expect($evento->geom_old)->toBeNull();
    expect($evento->geom_new)->toEqual(['lat' => -17.78, 'lon' => -63.18]);
});

it('audita un cambio de lat/lon con geom_old y geom_new correctos', function () {
    $taller = Taller::factory()->create(['lat' => -17.78, 'lon' => -63.18]);
    AuditoriaTaller::query()->delete();

    $taller->lat = -17.80;
    $taller->lon = -63.20;
    $taller->save();

    $evento = AuditoriaTaller::where('taller_id', $taller->id)->where('operacion', 'UPDATE')->first();

    expect($evento)->not->toBeNull();
    expect($evento->lat_old)->toBe(-17.78);
    expect($evento->lat_new)->toBe(-17.80);
    expect($evento->lon_old)->toBe(-63.18);
    expect($evento->lon_new)->toBe(-63.20);
    expect($evento->geom_old)->toEqual(['lat' => -17.78, 'lon' => -63.18]);
    expect($evento->geom_new)->toEqual(['lat' => -17.80, 'lon' => -63.20]);
});

it('audita un cambio de campo sensible de texto con datos_old/datos_new', function () {
    $taller = Taller::factory()->create(['nombre' => 'Nombre Original']);
    AuditoriaTaller::query()->delete();

    $taller->nombre = 'Nombre Nuevo';
    $taller->save();

    $evento = AuditoriaTaller::where('taller_id', $taller->id)->where('operacion', 'UPDATE')->first();

    expect($evento->datos_old)->toBe(['nombre' => 'Nombre Original']);
    expect($evento->datos_new)->toBe(['nombre' => 'Nombre Nuevo']);
});

it('no audita si ningun campo sensible cambio', function () {
    $taller = Taller::factory()->create();
    AuditoriaTaller::query()->delete();

    $taller->touch();

    expect(AuditoriaTaller::where('taller_id', $taller->id)->count())->toBe(0);
});

it('audita el soft delete con datos_old y sin datos_new', function () {
    $taller = Taller::factory()->create(['nombre' => 'Taller a Borrar']);
    AuditoriaTaller::query()->delete();

    $taller->delete();

    $evento = AuditoriaTaller::where('taller_id', $taller->id)->where('operacion', 'DELETE')->first();

    expect($evento)->not->toBeNull();
    expect($evento->datos_old['nombre'])->toBe('Taller a Borrar');
    expect($evento->datos_new)->toBeNull();
});

it('registra el usuario sistema autenticado como actor', function () {
    $usuario = UsuarioSistema::factory()->create();
    $this->actingAs($usuario, 'sistema');

    $taller = Taller::factory()->create();

    $evento = AuditoriaTaller::where('taller_id', $taller->id)->where('operacion', 'INSERT')->first();

    expect($evento->usuario_sistema_id)->toBe($usuario->id);
});

it('rechaza a nivel de BD una operacion invalida', function () {
    $taller = Taller::factory()->create();

    expect(fn () => AuditoriaTaller::create([
        'taller_id' => $taller->id,
        'operacion' => 'INVALIDA',
    ]))->toThrow(QueryException::class);
});

it('es append-only: update() y delete() lanzan BusinessException', function () {
    $taller = Taller::factory()->create();
    $evento = AuditoriaTaller::where('taller_id', $taller->id)->firstOrFail();

    expect(fn () => $evento->update(['operacion' => 'DELETE']))->toThrow(BusinessException::class);
    expect(fn () => $evento->delete())->toThrow(BusinessException::class);
});
