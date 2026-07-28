<?php

use App\Actions\Ordenes\CrearOrdenTrabajoAction;
use App\Exceptions\BusinessException;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\OrdenTrabajo;
use App\Models\OrdenTrabajoHistorialEstado;
use App\Models\Taller;
use App\Models\Vehiculo;

function tallerConClienteYVehiculo(): array
{
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $vehiculo = Vehiculo::factory()->create(['cliente_id' => $cliente->id]);

    return [$taller, $cliente, $vehiculo];
}

it('crea la orden con valores iniciales y el historial inicial', function () {
    [$taller, $cliente, $vehiculo] = tallerConClienteYVehiculo();

    $orden = app(CrearOrdenTrabajoAction::class)->execute($cliente->id, $vehiculo->id);

    expect($orden->taller_id)->toBe($taller->id);
    expect($orden->estado)->toBe('PENDIENTE');
    expect($orden->prioridad)->toBe('MEDIA');
    expect((float) $orden->total)->toBe(0.0);
    expect($orden->codigo)->toStartWith('OT-'.date('Y').'-');

    $historial = OrdenTrabajoHistorialEstado::where('orden_trabajo_id', $orden->id)->first();
    expect($historial->estado_anterior)->toBeNull();
    expect($historial->estado_nuevo)->toBe('PENDIENTE');
});

it('genera codigos secuenciales unicos por taller', function () {
    [, $cliente, $vehiculo] = tallerConClienteYVehiculo();

    $orden1 = app(CrearOrdenTrabajoAction::class)->execute($cliente->id, $vehiculo->id);
    $vehiculo2 = Vehiculo::factory()->create(['cliente_id' => $cliente->id]);
    $orden2 = app(CrearOrdenTrabajoAction::class)->execute($cliente->id, $vehiculo2->id);

    expect($orden1->codigo)->not->toBe($orden2->codigo);
});

it('rechaza un cliente que no pertenece al taller activo', function () {
    [, , $vehiculo] = tallerConClienteYVehiculo();
    $otroCliente = Cliente::factory()->create();

    expect(fn () => app(CrearOrdenTrabajoAction::class)->execute($otroCliente->id, $vehiculo->id))
        ->toThrow(BusinessException::class);
});

it('rechaza un vehiculo que no pertenece al cliente indicado', function () {
    [$taller, $cliente] = tallerConClienteYVehiculo();
    $otroCliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $vehiculoDeOtroCliente = Vehiculo::factory()->create(['cliente_id' => $otroCliente->id]);

    expect(fn () => app(CrearOrdenTrabajoAction::class)->execute($cliente->id, $vehiculoDeOtroCliente->id))
        ->toThrow(BusinessException::class);

    expect(OrdenTrabajo::count())->toBe(0);
});

it('rechaza un empleado asignado que no pertenece al taller activo', function () {
    [, $cliente, $vehiculo] = tallerConClienteYVehiculo();
    $empleadoDeOtroTaller = Empleado::factory()->create();

    expect(fn () => app(CrearOrdenTrabajoAction::class)->execute($cliente->id, $vehiculo->id, $empleadoDeOtroTaller->id))
        ->toThrow(BusinessException::class);
});

it('acepta un empleado asignado valido del mismo taller', function () {
    [$taller, $cliente, $vehiculo] = tallerConClienteYVehiculo();
    $empleado = Empleado::factory()->create(['taller_id' => $taller->id]);

    $orden = app(CrearOrdenTrabajoAction::class)->execute($cliente->id, $vehiculo->id, $empleado->id);

    expect($orden->empleado_asignado_id)->toBe($empleado->id);
});
