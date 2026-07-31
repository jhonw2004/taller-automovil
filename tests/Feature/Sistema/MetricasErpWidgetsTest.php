<?php

use App\Actions\Roles\AsignarRolAction;
use App\Filament\Erp\Widgets\ClientesVehiculosStatsWidget;
use App\Filament\Erp\Widgets\EmpleadosStatsWidget;
use App\Filament\Erp\Widgets\InventarioStatsWidget;
use App\Filament\Erp\Widgets\OrdenesTrabajoStatsWidget;
use App\Filament\Erp\Widgets\VentasStatsWidget;
use App\Models\Cliente;
use App\Models\NotaVenta;
use App\Models\OrdenTrabajo;
use App\Models\Permiso;
use App\Models\Repuesto;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;

/**
 * Dashboard de métricas por rol: cada widget del dashboard `/erp` se muestra solo si el usuario tiene
 * el permiso correspondiente en el taller activo — un mismo dashboard renderiza distintas
 * tarjetas según el rol (mecánico ve órdenes/inventario, cajero ve ventas/clientes, etc.),
 * mismo criterio que `canViewAny()` de los Resources (ver `ClienteResource`).
 */
function invocarMetodoProtegido(object $objeto, string $metodo): mixed
{
    $reflection = new ReflectionMethod($objeto, $metodo);
    $reflection->setAccessible(true);

    return $reflection->invoke($objeto);
}

function usuarioConPermisos(Taller $taller, array $permisoSlugs): UsuarioSistema
{
    $permisos = collect($permisoSlugs)->map(
        fn (string $slug) => Permiso::factory()->create(['slug' => $slug, 'activo' => true])
    );
    $rol = Rol::factory()->create(['taller_id' => $taller->id]);
    $rol->permisos()->attach($permisos->pluck('id'));
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $taller->id, asignadoPor: null);
    session(['taller_activo_id' => $taller->id]);

    return $usuario;
}

it('ClientesVehiculosStatsWidget se muestra solo con clientes.ver o vehiculos.ver', function () {
    $taller = Taller::factory()->create();

    $this->actingAs(usuarioConPermisos($taller, ['clientes.ver']), 'sistema');
    expect(ClientesVehiculosStatsWidget::canView())->toBeTrue();

    $this->actingAs(usuarioConPermisos($taller, ['ordenes.ver']), 'sistema');
    expect(ClientesVehiculosStatsWidget::canView())->toBeFalse();
});

it('OrdenesTrabajoStatsWidget se muestra solo con ordenes.ver', function () {
    $taller = Taller::factory()->create();

    $this->actingAs(usuarioConPermisos($taller, ['ordenes.ver']), 'sistema');
    expect(OrdenesTrabajoStatsWidget::canView())->toBeTrue();

    $this->actingAs(usuarioConPermisos($taller, ['notas.ver']), 'sistema');
    expect(OrdenesTrabajoStatsWidget::canView())->toBeFalse();
});

it('VentasStatsWidget se muestra solo con notas.ver', function () {
    $taller = Taller::factory()->create();

    $this->actingAs(usuarioConPermisos($taller, ['notas.ver']), 'sistema');
    expect(VentasStatsWidget::canView())->toBeTrue();

    $this->actingAs(usuarioConPermisos($taller, ['ordenes.ver']), 'sistema');
    expect(VentasStatsWidget::canView())->toBeFalse();
});

it('InventarioStatsWidget se muestra con inventario.ver o repuestos.ver', function () {
    $taller = Taller::factory()->create();

    $this->actingAs(usuarioConPermisos($taller, ['inventario.ver']), 'sistema');
    expect(InventarioStatsWidget::canView())->toBeTrue();

    $this->actingAs(usuarioConPermisos($taller, ['clientes.ver']), 'sistema');
    expect(InventarioStatsWidget::canView())->toBeFalse();
});

it('EmpleadosStatsWidget se muestra solo con empleados.ver', function () {
    $taller = Taller::factory()->create();

    $this->actingAs(usuarioConPermisos($taller, ['empleados.ver']), 'sistema');
    expect(EmpleadosStatsWidget::canView())->toBeTrue();

    $this->actingAs(usuarioConPermisos($taller, ['ordenes.ver']), 'sistema');
    expect(EmpleadosStatsWidget::canView())->toBeFalse();
});

it('un mecanico (ordenes.ver + inventario.ver) solo ve esos dos widgets, no ventas ni empleados', function () {
    $taller = Taller::factory()->create();
    $this->actingAs(usuarioConPermisos($taller, ['ordenes.ver', 'inventario.ver']), 'sistema');

    expect(OrdenesTrabajoStatsWidget::canView())->toBeTrue();
    expect(InventarioStatsWidget::canView())->toBeTrue();
    expect(VentasStatsWidget::canView())->toBeFalse();
    expect(EmpleadosStatsWidget::canView())->toBeFalse();
    expect(ClientesVehiculosStatsWidget::canView())->toBeFalse();
});

it('OrdenesTrabajoStatsWidget cuenta pendientes y en progreso del taller activo', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    OrdenTrabajo::factory()->count(2)->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id, 'estado' => 'PENDIENTE']);
    OrdenTrabajo::factory()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id, 'estado' => 'EN_PROGRESO']);

    $this->actingAs(usuarioConPermisos($taller, ['ordenes.ver']), 'sistema');

    $stats = invocarMetodoProtegido(new OrdenesTrabajoStatsWidget, 'getStats');
    $porEtiqueta = collect($stats)->mapWithKeys(fn ($stat) => [$stat->getLabel() => $stat->getValue()]);

    expect($porEtiqueta['Órdenes pendientes'])->toBe(2);
    expect($porEtiqueta['Órdenes en progreso'])->toBe(1);
});

it('InventarioStatsWidget detecta repuestos con stock por debajo del minimo', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    Repuesto::factory()->create(['taller_id' => $taller->id, 'activo' => true, 'stock_actual' => 1, 'stock_minimo' => 5]);
    Repuesto::factory()->create(['taller_id' => $taller->id, 'activo' => true, 'stock_actual' => 10, 'stock_minimo' => 2]);

    $this->actingAs(usuarioConPermisos($taller, ['inventario.ver']), 'sistema');

    $stats = invocarMetodoProtegido(new InventarioStatsWidget, 'getStats');
    $porEtiqueta = collect($stats)->mapWithKeys(fn ($stat) => [$stat->getLabel() => $stat->getValue()]);

    expect($porEtiqueta['Con stock bajo'])->toBe(1);
    expect($porEtiqueta['Repuestos activos'])->toBe(2);
});

it('VentasStatsWidget suma solo las notas de venta del taller activo, no anuladas, del mes actual', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    NotaVenta::factory()->create(['taller_id' => $taller->id, 'estado' => 'PAGADA', 'subtotal' => 100, 'total' => 100, 'monto_pagado' => 100, 'saldo' => 0, 'fecha_emision' => now()]);
    NotaVenta::factory()->create(['taller_id' => $taller->id, 'estado' => 'ANULADA', 'subtotal' => 999, 'total' => 999, 'monto_pagado' => 0, 'saldo' => 999, 'fecha_emision' => now()]);
    NotaVenta::factory()->create(['taller_id' => $taller->id, 'estado' => 'PENDIENTE', 'subtotal' => 50, 'total' => 50, 'monto_pagado' => 0, 'saldo' => 50, 'fecha_emision' => now()]);

    $this->actingAs(usuarioConPermisos($taller, ['notas.ver']), 'sistema');

    $stats = invocarMetodoProtegido(new VentasStatsWidget, 'getStats');
    $porEtiqueta = collect($stats)->mapWithKeys(fn ($stat) => [$stat->getLabel() => $stat->getValue()]);

    expect($porEtiqueta['Ventas del mes'])->toBe('Bs. 150.00');
    expect($porEtiqueta['Notas pendientes de pago'])->toBe(1);
});
