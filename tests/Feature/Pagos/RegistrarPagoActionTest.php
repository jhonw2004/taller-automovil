<?php

use App\Actions\Pagos\RegistrarPagoAction;
use App\Events\PagoRegistrado;
use App\Exceptions\BusinessException;
use App\Models\MetodoPago;
use App\Models\NotaVenta;
use App\Models\Pago;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use Illuminate\Support\Facades\Event;

function notaConLineaParaPago(float $total, array $overrides = []): NotaVenta
{
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $nota = NotaVenta::factory()->create(array_merge([
        'taller_id' => $taller->id,
        'subtotal' => $total,
        'total' => $total,
        'saldo' => $total,
    ], $overrides));
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

it('rechaza un monto menor o igual a cero', function () {
    $nota = notaConLineaParaPago(100);
    $metodo = MetodoPago::factory()->create();

    expect(fn () => app(RegistrarPagoAction::class)->execute($nota, $metodo->id, 0))
        ->toThrow(BusinessException::class);

    expect(Pago::count())->toBe(0);
});

it('rechaza un metodo de pago inactivo', function () {
    $nota = notaConLineaParaPago(100);
    $metodo = MetodoPago::factory()->inactivo()->create();

    expect(fn () => app(RegistrarPagoAction::class)->execute($nota, $metodo->id, 50))
        ->toThrow(BusinessException::class);

    expect(Pago::count())->toBe(0);
    expect($nota->fresh()->estado)->toBe('EMITIDA');
});

it('rechaza un pago que supera el saldo pendiente de la nota', function () {
    $nota = notaConLineaParaPago(100);
    $metodo = MetodoPago::factory()->create();

    expect(fn () => app(RegistrarPagoAction::class)->execute($nota, $metodo->id, 150))
        ->toThrow(BusinessException::class);

    expect(Pago::count())->toBe(0);
});

it('rechaza pagos sobre una nota anulada', function () {
    $nota = notaConLineaParaPago(100, ['estado' => 'ANULADA']);
    $metodo = MetodoPago::factory()->create();

    expect(fn () => app(RegistrarPagoAction::class)->execute($nota, $metodo->id, 50))
        ->toThrow(BusinessException::class);

    expect(Pago::count())->toBe(0);
});

it('registra un pago parcial: la nota queda PENDIENTE con el saldo correcto', function () {
    $nota = notaConLineaParaPago(100);
    $metodo = MetodoPago::factory()->create();

    $pago = app(RegistrarPagoAction::class)->execute($nota, $metodo->id, 40, referencia: 'REF-001');

    expect($pago->estado)->toBe('CONFIRMADO');
    expect($pago->referencia)->toBe('REF-001');

    $notaFresca = $nota->fresh();
    expect((float) $notaFresca->monto_pagado)->toBe(40.0);
    expect((float) $notaFresca->saldo)->toBe(60.0);
    expect($notaFresca->estado)->toBe('PENDIENTE');
});

it('registra un pago total: la nota queda PAGADA con saldo cero', function () {
    $nota = notaConLineaParaPago(100);
    $metodo = MetodoPago::factory()->create();

    app(RegistrarPagoAction::class)->execute($nota, $metodo->id, 100);

    $notaFresca = $nota->fresh();
    expect((float) $notaFresca->monto_pagado)->toBe(100.0);
    expect((float) $notaFresca->saldo)->toBe(0.0);
    expect($notaFresca->estado)->toBe('PAGADA');
});

it('permite varios pagos parciales por nota sin superar el total', function () {
    $nota = notaConLineaParaPago(100);
    $metodo = MetodoPago::factory()->create();

    app(RegistrarPagoAction::class)->execute($nota, $metodo->id, 30);
    $notaFresca = $nota->fresh();
    expect($notaFresca->estado)->toBe('PENDIENTE');

    app(RegistrarPagoAction::class)->execute($notaFresca, $metodo->id, 70);
    $notaFinal = $nota->fresh();

    expect(Pago::where('nota_venta_id', $nota->id)->count())->toBe(2);
    expect((float) $notaFinal->monto_pagado)->toBe(100.0);
    expect($notaFinal->estado)->toBe('PAGADA');

    expect(fn () => app(RegistrarPagoAction::class)->execute($notaFinal, $metodo->id, 1))
        ->toThrow(BusinessException::class);
});

it('dispara PagoRegistrado tras confirmar el pago', function () {
    $nota = notaConLineaParaPago(100);
    $metodo = MetodoPago::factory()->create();
    Event::fake([PagoRegistrado::class]);

    $pago = app(RegistrarPagoAction::class)->execute($nota, $metodo->id, 40);

    Event::assertDispatched(PagoRegistrado::class, fn (PagoRegistrado $event) => $event->pago->id === $pago->id);
});

it('guarda el usuario sistema que registro el pago', function () {
    $nota = notaConLineaParaPago(100);
    $metodo = MetodoPago::factory()->create();
    $usuario = UsuarioSistema::factory()->create();

    $pago = app(RegistrarPagoAction::class)->execute($nota, $metodo->id, 20, usuarioSistemaId: $usuario->id);

    expect($pago->usuario_sistema_id)->toBe($usuario->id);
});
