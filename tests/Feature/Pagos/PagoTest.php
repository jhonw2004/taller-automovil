<?php

use App\Models\MetodoPago;
use App\Models\NotaVenta;
use App\Models\Pago;
use App\Models\Taller;
use Database\Seeders\MetodoPagoSeeder;
use Illuminate\Database\QueryException;

function notaConLinea(float $total): NotaVenta
{
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $nota = NotaVenta::factory()->create([
        'taller_id' => $taller->id,
        'subtotal' => $total,
        'total' => $total,
        'saldo' => $total,
    ]);
    $nota->lineas()->create([
        'servicio_catalogo_id' => null,
        'repuesto_id' => null,
        'descripcion' => 'Servicio',
        'cantidad' => 1,
        'precio_unitario' => $total,
        'descuento' => 0,
        'subtotal' => $total,
    ]);

    return $nota->fresh();
}

it('rechaza un monto de pago menor o igual a cero a nivel de BD', function () {
    $nota = notaConLinea(100);
    $metodo = MetodoPago::factory()->create();

    expect(fn () => Pago::create([
        'nota_venta_id' => $nota->id,
        'metodo_pago_id' => $metodo->id,
        'monto' => 0,
        'estado' => 'CONFIRMADO',
    ]))->toThrow(QueryException::class);
});

it('rechaza un estado de pago invalido a nivel de BD', function () {
    $nota = notaConLinea(100);
    $metodo = MetodoPago::factory()->create();

    expect(fn () => Pago::create([
        'nota_venta_id' => $nota->id,
        'metodo_pago_id' => $metodo->id,
        'monto' => 10,
        'estado' => 'PENDIENTE',
    ]))->toThrow(QueryException::class);
});

it('metodo de pago rechaza un nombre duplicado a nivel de BD', function () {
    MetodoPago::factory()->create(['nombre' => 'Efectivo', 'slug' => 'efectivo']);

    expect(fn () => MetodoPago::factory()->create(['nombre' => 'Efectivo', 'slug' => 'efectivo-2']))
        ->toThrow(QueryException::class);
});

it('metodo de pago scopeActivos filtra los inactivos', function () {
    MetodoPago::factory()->create(['nombre' => 'Efectivo']);
    MetodoPago::factory()->inactivo()->create(['nombre' => 'Cheque']);

    expect(MetodoPago::activos()->count())->toBe(1);
});

it('MetodoPagoSeeder crea los 4 metodos base y es idempotente', function () {
    (new MetodoPagoSeeder)->run();
    (new MetodoPagoSeeder)->run();

    expect(MetodoPago::count())->toBe(4);
    expect(MetodoPago::activos()->pluck('slug')->sort()->values()->all())
        ->toBe(['efectivo', 'qr', 'tarjeta', 'transferencia']);
});

it('un pago no tiene BelongsToTaller propio: el aislamiento es via la nota padre', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    session(['taller_activo_id' => $tallerA->id]);
    $notaA = NotaVenta::factory()->create(['taller_id' => $tallerA->id]);
    $notaB = NotaVenta::factory()->create(['taller_id' => $tallerB->id]);
    $metodo = MetodoPago::factory()->create();
    Pago::factory()->create(['nota_venta_id' => $notaA->id, 'metodo_pago_id' => $metodo->id]);
    Pago::factory()->create(['nota_venta_id' => $notaB->id, 'metodo_pago_id' => $metodo->id]);

    expect(NotaVenta::count())->toBe(1);
    expect(Pago::count())->toBe(2);
});
