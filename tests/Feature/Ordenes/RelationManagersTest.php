<?php

use App\Actions\Ordenes\AgregarLineaRepuestoAction;
use App\Actions\Ordenes\AgregarLineaServicioAction;
use App\Actions\Roles\AsignarRolAction;
use App\Filament\Erp\Resources\OrdenTrabajoResource\Pages\EditOrdenTrabajo;
use App\Filament\Erp\Resources\OrdenTrabajoResource\RelationManagers\HistorialRelationManager;
use App\Filament\Erp\Resources\OrdenTrabajoResource\RelationManagers\NotasRelationManager;
use App\Filament\Erp\Resources\OrdenTrabajoResource\RelationManagers\RepuestosRelationManager;
use App\Filament\Erp\Resources\OrdenTrabajoResource\RelationManagers\ServiciosRelationManager;
use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use App\Models\OrdenTrabajoServicio;
use App\Models\Permiso;
use App\Models\Repuesto;
use App\Models\Rol;
use App\Models\ServicioCatalogo;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('erp')));

function ordenParaRelationManagers(): array
{
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeOrdenesParaRelaciones(['ordenes.ver', 'ordenes.editar'], $taller->id);
    session(['taller_activo_id' => $taller->id]);
    test()->actingAs($usuario, 'sistema');
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $orden = OrdenTrabajo::factory()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id]);

    return [$taller, $orden, $usuario];
}

it('ServiciosRelationManager: agrega una linea real y recalcula el total de la orden', function () {
    [$taller, $orden] = ordenParaRelationManagers();
    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $taller->id, 'precio_base' => 60]);

    Livewire::test(ServiciosRelationManager::class, ['ownerRecord' => $orden, 'pageClass' => EditOrdenTrabajo::class])
        ->callTableAction('create', data: [
            'servicio_catalogo_id' => $servicio->id,
            'cantidad' => 2,
            'descuento' => 0,
        ])
        ->assertHasNoTableActionErrors();

    expect(OrdenTrabajoServicio::where('orden_trabajo_id', $orden->id)->count())->toBe(1);
    expect((float) $orden->fresh()->total)->toBe(120.0);
});

it('ServiciosRelationManager: marcar realizado y anular linea funcionan via la tabla', function () {
    [, $orden] = ordenParaRelationManagers();
    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $orden->taller_id, 'precio_base' => 60]);
    $linea = app(AgregarLineaServicioAction::class)->execute($orden, $servicio->id, 1);

    Livewire::test(ServiciosRelationManager::class, ['ownerRecord' => $orden, 'pageClass' => EditOrdenTrabajo::class])
        ->callTableAction('marcarRealizado', $linea);

    expect($linea->fresh()->estado)->toBe('REALIZADO');

    Livewire::test(ServiciosRelationManager::class, ['ownerRecord' => $orden, 'pageClass' => EditOrdenTrabajo::class])
        ->callTableAction('anularLinea', $linea);

    expect($linea->fresh()->estado)->toBe('ANULADO');
    expect((float) $orden->fresh()->total)->toBe(0.0);
});

it('RepuestosRelationManager: entregar descuenta stock real', function () {
    [$taller, $orden] = ordenParaRelationManagers();
    $repuesto = Repuesto::factory()->create(['taller_id' => $taller->id, 'stock_actual' => 10]);
    $linea = app(AgregarLineaRepuestoAction::class)->execute($orden, $repuesto->id, 3);

    Livewire::test(RepuestosRelationManager::class, ['ownerRecord' => $orden, 'pageClass' => EditOrdenTrabajo::class])
        ->callTableAction('entregar', $linea);

    expect($linea->fresh()->estado)->toBe('ENTREGADO');
    expect((float) $repuesto->fresh()->stock_actual)->toBe(7.0);
});

it('RepuestosRelationManager: entregar con stock insuficiente muestra el toast con nombre y disponible', function () {
    [$taller, $orden] = ordenParaRelationManagers();
    $repuesto = Repuesto::factory()->create(['taller_id' => $taller->id, 'nombre' => 'Filtro de aceite', 'stock_actual' => 1]);
    $linea = app(AgregarLineaRepuestoAction::class)->execute($orden, $repuesto->id, 5);

    Livewire::test(RepuestosRelationManager::class, ['ownerRecord' => $orden, 'pageClass' => EditOrdenTrabajo::class])
        ->callTableAction('entregar', $linea)
        ->assertNotified('Stock insuficiente para Filtro de aceite. Disponible: 1.000');

    expect($linea->fresh()->estado)->toBe('PENDIENTE');
});

it('NotasRelationManager: agrega una nota real', function () {
    [, $orden] = ordenParaRelationManagers();

    Livewire::test(NotasRelationManager::class, ['ownerRecord' => $orden, 'pageClass' => EditOrdenTrabajo::class])
        ->callTableAction('create', data: ['tipo' => 'INTERNA', 'nota' => 'Cliente pidió revisar frenos también.'])
        ->assertHasNoTableActionErrors();

    expect($orden->notas()->count())->toBe(1);
});

it('HistorialRelationManager: renderiza de solo lectura sin acciones de crear/editar/eliminar', function () {
    [, $orden] = ordenParaRelationManagers();

    Livewire::test(HistorialRelationManager::class, ['ownerRecord' => $orden, 'pageClass' => EditOrdenTrabajo::class])
        ->assertSuccessful()
        ->assertTableActionDoesNotExist('create');
});

function usuarioConPermisosDeOrdenesParaRelaciones(array $slugs, int $tallerId): UsuarioSistema
{
    $permisos = collect($slugs)->map(fn (string $slug) => Permiso::factory()->create(['slug' => $slug, 'activo' => true]));
    $rol = Rol::factory()->create(['taller_id' => $tallerId]);
    $rol->permisos()->attach($permisos->pluck('id'));
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerId, asignadoPor: null);

    return $usuario;
}
