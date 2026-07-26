<?php

use App\Models\Taller;
use Illuminate\Database\QueryException;

it('sincroniza geom desde lat/lon en el mismo guardado', function () {
    $taller = Taller::factory()->create(['lat' => -17.783, 'lon' => -63.182]);

    $fresh = $taller->fresh();

    expect($fresh->geom)->toBe(['lon' => -63.182, 'lat' => -17.783]);
});

it('rechaza lat fuera de [-90, 90]', function () {
    expect(fn () => Taller::factory()->create(['lat' => 91, 'lon' => -63.182]))
        ->toThrow(QueryException::class);
});

it('rechaza lon fuera de [-180, 180]', function () {
    expect(fn () => Taller::factory()->create(['lat' => -17.783, 'lon' => 181]))
        ->toThrow(QueryException::class);
});
