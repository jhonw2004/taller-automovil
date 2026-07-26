<?php

use App\Models\Rol;
use App\Models\Taller;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

it('los permisos son siempre globales: la tabla permisos no tiene columna taller_id', function () {
    expect(Schema::hasColumn('permisos', 'taller_id'))->toBeFalse();
});

it('no permite dos roles globales con el mismo slug', function () {
    Rol::factory()->create(['taller_id' => null, 'slug' => 'coordinador']);

    expect(fn () => Rol::factory()->create(['taller_id' => null, 'slug' => 'coordinador']))
        ->toThrow(QueryException::class);
});

it('sí permite el mismo slug de rol en dos talleres distintos', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();

    Rol::factory()->create(['taller_id' => $tallerA->id, 'slug' => 'coordinador']);
    $rolB = Rol::factory()->create(['taller_id' => $tallerB->id, 'slug' => 'coordinador']);

    expect($rolB)->toBeInstanceOf(Rol::class);
});
