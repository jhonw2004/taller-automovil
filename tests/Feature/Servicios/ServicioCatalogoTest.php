<?php

use App\Models\ServicioCatalogo;
use App\Models\Taller;
use Illuminate\Database\QueryException;

it('aisla servicios por taller: un taller no ve servicios de otro', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    ServicioCatalogo::factory()->create(['taller_id' => $tallerA->id]);
    ServicioCatalogo::factory()->create(['taller_id' => $tallerB->id]);

    session(['taller_activo_id' => $tallerA->id]);

    expect(ServicioCatalogo::all())->toHaveCount(1);
    expect(ServicioCatalogo::first()->taller_id)->toBe($tallerA->id);
});

it('auto-rellena taller_id desde la sesion al crear sin especificarlo', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);

    $servicio = ServicioCatalogo::factory()->make(['taller_id' => null]);
    $servicio->save();

    expect($servicio->fresh()->taller_id)->toBe($taller->id);
});

it('rechaza en BD dos servicios con el mismo codigo en el mismo taller', function () {
    $taller = Taller::factory()->create();
    ServicioCatalogo::factory()->create(['taller_id' => $taller->id, 'codigo' => 'SRV-001']);

    expect(fn () => ServicioCatalogo::factory()->create(['taller_id' => $taller->id, 'codigo' => 'SRV-001']))
        ->toThrow(QueryException::class);
});

it('permite el mismo codigo de servicio en talleres distintos', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    ServicioCatalogo::factory()->create(['taller_id' => $tallerA->id, 'codigo' => 'SRV-001']);

    $servicioB = ServicioCatalogo::factory()->create(['taller_id' => $tallerB->id, 'codigo' => 'SRV-001']);

    expect($servicioB->exists)->toBeTrue();
});

it('rechaza en BD un precio_base negativo', function () {
    $taller = Taller::factory()->create();

    expect(fn () => ServicioCatalogo::factory()->create(['taller_id' => $taller->id, 'precio_base' => -10]))
        ->toThrow(QueryException::class);
});

it('permite precio_base en cero', function () {
    $taller = Taller::factory()->create();

    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $taller->id, 'precio_base' => 0]);

    expect($servicio->exists)->toBeTrue();
});

it('soft-deletea un servicio en vez de borrarlo fisicamente', function () {
    $taller = Taller::factory()->create();
    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $taller->id]);

    $servicio->delete();

    expect(ServicioCatalogo::withTrashed()->find($servicio->id))->not->toBeNull();
    expect(ServicioCatalogo::find($servicio->id))->toBeNull();
});

it('scopeActivos excluye servicios inactivos', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    ServicioCatalogo::factory()->create(['taller_id' => $taller->id]);
    ServicioCatalogo::factory()->inactivo()->create(['taller_id' => $taller->id]);

    expect(ServicioCatalogo::activos()->count())->toBe(1);
});

it('modificar precio_base no afecta una copia previamente leida del valor (snapshot manual)', function () {
    // El snapshot real de precio_unitario en lineas de orden/nota se implementa en
    // 011-ordenes-trabajo/012-notas-venta (esas tablas no existen todavia). Este test verifica
    // que precio_base es simplemente un valor mutable del catalogo, sin ningun mecanismo que
    // propague el cambio hacia atras — la garantia de "no afecta lineas historicas" depende de
    // que la linea guarde su propio precio_unitario al crearse, responsabilidad de esas features.
    $taller = Taller::factory()->create();
    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $taller->id, 'precio_base' => 100]);

    $precioAlMomentoDeCrearLinea = (string) $servicio->precio_base;

    $servicio->update(['precio_base' => 150]);

    expect($precioAlMomentoDeCrearLinea)->toBe('100.00');
    expect($servicio->fresh()->precio_base)->toBe('150.00');
});
