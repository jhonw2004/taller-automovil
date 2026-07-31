<?php

use App\Actions\Roles\AsignarRolAction;
use App\Filament\Erp\Widgets\ResumenTallerWidget;
use App\Models\Cliente;
use App\Models\NotaVenta;
use App\Models\OrdenTrabajo;
use App\Models\Permiso;
use App\Models\Repuesto;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;

/**
 * Dashboard de métricas por rol: `ResumenTallerWidget` (único widget de estadísticas de `/erp`)
 * solo muestra la tarjeta de cada área si el usuario tiene el permiso correspondiente en el
 * taller activo — un mismo dashboard renderiza distintas tarjetas según el rol (mecánico ve
 * órdenes/inventario, cajero ve ventas/clientes, etc.), mismo criterio que `canViewAny()` de los
 * Resources (ver `ClienteResource`).
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

function etiquetasDeStats(): array
{
    $stats = invocarMetodoProtegido(new ResumenTallerWidget, 'getStats');

    return collect($stats)->map(fn ($stat) => $stat->getLabel())->all();
}

it('ResumenTallerWidget solo se muestra si el usuario tiene al menos un permiso de dashboard', function () {
    $taller = Taller::factory()->create();

    $this->actingAs(usuarioConPermisos($taller, ['clientes.ver']), 'sistema');
    expect(ResumenTallerWidget::canView())->toBeTrue();

    $this->actingAs(usuarioConPermisos($taller, ['reportes.ver']), 'sistema');
    expect(ResumenTallerWidget::canView())->toBeFalse();
});

it('un usuario con clientes.ver o vehiculos.ver ve esas tarjetas y no otras', function () {
    $taller = Taller::factory()->create();
    $this->actingAs(usuarioConPermisos($taller, ['clientes.ver']), 'sistema');

    $etiquetas = etiquetasDeStats();

    expect($etiquetas)->toContain('Clientes activos');
    expect($etiquetas)->not->toContain('Órdenes pendientes');
    expect($etiquetas)->not->toContain('Empleados activos');
});

it('un mecanico (ordenes.ver + inventario.ver) solo ve tarjetas de esas dos areas', function () {
    $taller = Taller::factory()->create();
    $this->actingAs(usuarioConPermisos($taller, ['ordenes.ver', 'inventario.ver']), 'sistema');

    $etiquetas = etiquetasDeStats();

    expect($etiquetas)->toContain('Órdenes pendientes');
    expect($etiquetas)->toContain('Repuestos activos');
    expect($etiquetas)->not->toContain('Ventas del mes');
    expect($etiquetas)->not->toContain('Empleados activos');
    expect($etiquetas)->not->toContain('Clientes activos');
});

it('un owner con todos los permisos ve las tarjetas de las 5 areas', function () {
    $taller = Taller::factory()->create();
    $this->actingAs(usuarioConPermisos($taller, [
        'clientes.ver', 'vehiculos.ver', 'empleados.ver', 'inventario.ver', 'ordenes.ver', 'notas.ver',
    ]), 'sistema');

    $etiquetas = etiquetasDeStats();

    expect($etiquetas)->toContain('Clientes activos', 'Vehículos registrados', 'Empleados activos', 'Repuestos activos', 'Órdenes pendientes', 'Ventas del mes');
});

it('cuenta ordenes pendientes y en progreso del taller activo', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    OrdenTrabajo::factory()->count(2)->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id, 'estado' => 'PENDIENTE']);
    OrdenTrabajo::factory()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id, 'estado' => 'EN_PROGRESO']);

    $this->actingAs(usuarioConPermisos($taller, ['ordenes.ver']), 'sistema');

    $stats = invocarMetodoProtegido(new ResumenTallerWidget, 'getStats');
    $porEtiqueta = collect($stats)->mapWithKeys(fn ($stat) => [$stat->getLabel() => $stat->getValue()]);

    expect($porEtiqueta['Órdenes pendientes'])->toBe(2);
    expect($porEtiqueta['Órdenes en progreso'])->toBe(1);
});

it('detecta repuestos con stock por debajo del minimo', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    Repuesto::factory()->create(['taller_id' => $taller->id, 'activo' => true, 'stock_actual' => 1, 'stock_minimo' => 5]);
    Repuesto::factory()->create(['taller_id' => $taller->id, 'activo' => true, 'stock_actual' => 10, 'stock_minimo' => 2]);

    $this->actingAs(usuarioConPermisos($taller, ['inventario.ver']), 'sistema');

    $stats = invocarMetodoProtegido(new ResumenTallerWidget, 'getStats');
    $porEtiqueta = collect($stats)->mapWithKeys(fn ($stat) => [$stat->getLabel() => $stat->getValue()]);

    expect($porEtiqueta['Con stock bajo'])->toBe(1);
    expect($porEtiqueta['Repuestos activos'])->toBe(2);
});

it('suma solo las notas de venta del taller activo, no anuladas, del mes actual', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    NotaVenta::factory()->create(['taller_id' => $taller->id, 'estado' => 'PAGADA', 'subtotal' => 100, 'total' => 100, 'monto_pagado' => 100, 'saldo' => 0, 'fecha_emision' => now()]);
    NotaVenta::factory()->create(['taller_id' => $taller->id, 'estado' => 'ANULADA', 'subtotal' => 999, 'total' => 999, 'monto_pagado' => 0, 'saldo' => 999, 'fecha_emision' => now()]);
    NotaVenta::factory()->create(['taller_id' => $taller->id, 'estado' => 'PENDIENTE', 'subtotal' => 50, 'total' => 50, 'monto_pagado' => 0, 'saldo' => 50, 'fecha_emision' => now()]);

    $this->actingAs(usuarioConPermisos($taller, ['notas.ver']), 'sistema');

    $stats = invocarMetodoProtegido(new ResumenTallerWidget, 'getStats');
    $porEtiqueta = collect($stats)->mapWithKeys(fn ($stat) => [$stat->getLabel() => $stat->getValue()]);

    expect($porEtiqueta['Ventas del mes'])->toBe('Bs. 150.00');
    expect($porEtiqueta['Notas pendientes de pago'])->toBe(1);
});
