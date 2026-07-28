<?php

use App\Actions\Roles\AsignarRolAction;
use App\Filament\Erp\Resources\OrdenTrabajoResource\Pages\CreateOrdenTrabajo;
use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use App\Models\Vehiculo;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('erp')));

function usuarioConPermisosDeOrdenesParaCrear(array $slugs, int $tallerId): UsuarioSistema
{
    $permisos = collect($slugs)->map(fn (string $slug) => Permiso::factory()->create(['slug' => $slug, 'activo' => true]));
    $rol = Rol::factory()->create(['taller_id' => $tallerId]);
    $rol->permisos()->attach($permisos->pluck('id'));
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerId, asignadoPor: null);

    return $usuario;
}

it('crea una orden de trabajo desde el formulario Filament con valores iniciales correctos', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeOrdenesParaCrear(['ordenes.ver', 'ordenes.crear'], $taller->id);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $vehiculo = Vehiculo::factory()->create(['cliente_id' => $cliente->id]);

    Livewire::test(CreateOrdenTrabajo::class)
        ->fillForm([
            'cliente_id' => $cliente->id,
            'vehiculo_id' => $vehiculo->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $orden = OrdenTrabajo::where('cliente_id', $cliente->id)->first();
    expect($orden)->not->toBeNull();
    expect($orden->taller_id)->toBe($taller->id);
    expect($orden->vehiculo_id)->toBe($vehiculo->id);
    expect($orden->estado)->toBe('PENDIENTE');
    expect((float) $orden->total)->toBe(0.0);
    expect($orden->codigo)->toStartWith('OT-'.date('Y').'-');
});

it('rechaza un vehiculo que no pertenece al cliente seleccionado (validado por la Action, no solo el formulario)', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeOrdenesParaCrear(['ordenes.ver', 'ordenes.crear'], $taller->id);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $otroCliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $vehiculoDeOtroCliente = Vehiculo::factory()->create(['cliente_id' => $otroCliente->id]);

    Livewire::test(CreateOrdenTrabajo::class)
        ->fillForm([
            'cliente_id' => $cliente->id,
            'vehiculo_id' => $vehiculoDeOtroCliente->id,
        ])
        ->call('create');

    expect(OrdenTrabajo::count())->toBe(0);
});

it('permite crear una segunda orden en el mismo taller con codigo distinto', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeOrdenesParaCrear(['ordenes.ver', 'ordenes.crear'], $taller->id);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $vehiculo1 = Vehiculo::factory()->create(['cliente_id' => $cliente->id]);
    $vehiculo2 = Vehiculo::factory()->create(['cliente_id' => $cliente->id]);

    Livewire::test(CreateOrdenTrabajo::class)
        ->fillForm(['cliente_id' => $cliente->id, 'vehiculo_id' => $vehiculo1->id])
        ->call('create')
        ->assertHasNoFormErrors();

    Livewire::test(CreateOrdenTrabajo::class)
        ->fillForm(['cliente_id' => $cliente->id, 'vehiculo_id' => $vehiculo2->id])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(OrdenTrabajo::count())->toBe(2);
    expect(OrdenTrabajo::pluck('codigo')->unique())->toHaveCount(2);
});
