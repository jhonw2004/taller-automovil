<?php

use App\Actions\Pagos\AnularPagoAction;
use App\Actions\Pagos\RegistrarPagoAction;
use App\Exceptions\BusinessException;
use App\Models\MetodoPago;
use App\Models\NotaVenta;
use App\Models\Taller;
use Spatie\Activitylog\Models\Activity;

function notaConLineaParaAnularPago(float $total): NotaVenta
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

it('rechaza anular un pago que ya esta ANULADO', function () {
    $nota = notaConLineaParaAnularPago(100);
    $metodo = MetodoPago::factory()->create();
    $pago = app(RegistrarPagoAction::class)->execute($nota, $metodo->id, 100);
    app(AnularPagoAction::class)->execute($pago);

    expect(fn () => app(AnularPagoAction::class)->execute($pago->fresh()))
        ->toThrow(BusinessException::class);
});

it('anula un pago total: la nota PAGADA vuelve a EMITIDA (monto_pagado queda en 0)', function () {
    $nota = notaConLineaParaAnularPago(100);
    $metodo = MetodoPago::factory()->create();
    $pago = app(RegistrarPagoAction::class)->execute($nota, $metodo->id, 100);
    expect($nota->fresh()->estado)->toBe('PAGADA');

    app(AnularPagoAction::class)->execute($pago);

    $notaFresca = $nota->fresh();
    expect((float) $notaFresca->monto_pagado)->toBe(0.0);
    expect((float) $notaFresca->saldo)->toBe(100.0);
    expect($notaFresca->estado)->toBe('EMITIDA');
});

it('anula uno de dos pagos parciales: la nota PAGADA vuelve a PENDIENTE', function () {
    $nota = notaConLineaParaAnularPago(100);
    $metodo = MetodoPago::factory()->create();
    $pago1 = app(RegistrarPagoAction::class)->execute($nota, $metodo->id, 60);
    app(RegistrarPagoAction::class)->execute($nota->fresh(), $metodo->id, 40);
    expect($nota->fresh()->estado)->toBe('PAGADA');

    app(AnularPagoAction::class)->execute($pago1);

    $notaFresca = $nota->fresh();
    expect((float) $notaFresca->monto_pagado)->toBe(40.0);
    expect((float) $notaFresca->saldo)->toBe(60.0);
    expect($notaFresca->estado)->toBe('PENDIENTE');
});

it('la anulacion de pago queda auditada', function () {
    $nota = notaConLineaParaAnularPago(100);
    $metodo = MetodoPago::factory()->create();
    $pago = app(RegistrarPagoAction::class)->execute($nota, $metodo->id, 100);

    app(AnularPagoAction::class)->execute($pago, usuarioSistemaId: null);

    expect(Activity::where('description', 'anular_pago')->exists())->toBeTrue();
});
