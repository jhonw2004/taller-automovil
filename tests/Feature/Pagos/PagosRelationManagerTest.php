<?php

use App\Actions\Pagos\RegistrarPagoAction;
use App\Actions\Roles\AsignarRolAction;
use App\Filament\Erp\Resources\NotaVentaResource\Pages\EditNotaVenta;
use App\Filament\Erp\Resources\NotaVentaResource\RelationManagers\PagosRelationManager;
use App\Models\MetodoPago;
use App\Models\NotaVenta;
use App\Models\Pago;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('erp')));

function usuarioConPermisosDePagos(array $slugs, int $tallerId): UsuarioSistema
{
    $permisos = collect($slugs)->map(fn (string $slug) => Permiso::factory()->create(['slug' => $slug, 'activo' => true]));
    $rol = Rol::factory()->create(['taller_id' => $tallerId]);
    $rol->permisos()->attach($permisos->pluck('id'));
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerId, asignadoPor: null);

    return $usuario;
}

function notaParaPagosRelationManager(array $slugs): array
{
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDePagos($slugs, $taller->id);
    session(['taller_activo_id' => $taller->id]);
    test()->actingAs($usuario, 'sistema');

    $nota = NotaVenta::factory()->create([
        'taller_id' => $taller->id,
        'subtotal' => 100,
        'total' => 100,
        'saldo' => 100,
    ]);
    $nota->lineas()->create([
        'servicio_catalogo_id' => null,
        'repuesto_id' => null,
        'descripcion' => 'Servicio',
        'cantidad' => 1,
        'precio_unitario' => 100,
        'descuento' => 0,
        'subtotal' => 100,
    ]);

    return [$taller, $nota, $usuario];
}

it('registra un pago real via la tabla y recalcula la nota', function () {
    [, $nota] = notaParaPagosRelationManager(['notas.ver', 'notas.editar', 'pagos.registrar']);
    $metodo = MetodoPago::factory()->create();

    Livewire::test(PagosRelationManager::class, ['ownerRecord' => $nota, 'pageClass' => EditNotaVenta::class])
        ->assertTableActionVisible('create')
        ->callTableAction('create', data: [
            'metodo_pago_id' => $metodo->id,
            'monto' => 40,
            'referencia' => 'REF-1',
        ])
        ->assertHasNoTableActionErrors();

    expect(Pago::where('nota_venta_id', $nota->id)->count())->toBe(1);
    expect($nota->fresh()->estado)->toBe('PENDIENTE');
    expect((float) $nota->fresh()->saldo)->toBe(60.0);
});

it('usuario sin permiso pagos.registrar no ve la accion de registrar pago', function () {
    [, $nota] = notaParaPagosRelationManager(['notas.ver', 'notas.editar']);

    Livewire::test(PagosRelationManager::class, ['ownerRecord' => $nota, 'pageClass' => EditNotaVenta::class])
        ->assertTableActionHidden('create');
});

it('anula un pago via la tabla: la nota PAGADA vuelve a PENDIENTE', function () {
    [, $nota] = notaParaPagosRelationManager(['notas.ver', 'notas.editar', 'pagos.registrar', 'pagos.anular']);
    $metodo = MetodoPago::factory()->create();
    app(RegistrarPagoAction::class)->execute($nota, $metodo->id, 60);
    $pago = app(RegistrarPagoAction::class)->execute($nota->fresh(), $metodo->id, 40);
    expect($nota->fresh()->estado)->toBe('PAGADA');

    Livewire::test(PagosRelationManager::class, ['ownerRecord' => $nota, 'pageClass' => EditNotaVenta::class])
        ->assertTableActionVisible('anular', $pago)
        ->callTableAction('anular', $pago);

    expect($pago->fresh()->estado)->toBe('ANULADO');
    expect($nota->fresh()->estado)->toBe('PENDIENTE');
});

it('usuario sin permiso pagos.anular no ve la accion de anular pago', function () {
    [, $nota] = notaParaPagosRelationManager(['notas.ver', 'notas.editar', 'pagos.registrar']);
    $metodo = MetodoPago::factory()->create();
    $pago = app(RegistrarPagoAction::class)->execute($nota, $metodo->id, 40);

    Livewire::test(PagosRelationManager::class, ['ownerRecord' => $nota, 'pageClass' => EditNotaVenta::class])
        ->assertTableActionHidden('anular', $pago);
});
