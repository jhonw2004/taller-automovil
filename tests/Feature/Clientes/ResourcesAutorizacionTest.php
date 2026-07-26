<?php

use App\Actions\Roles\AsignarRolAction;
use App\Filament\Erp\Resources\ClienteResource;
use App\Filament\Erp\Resources\VehiculoResource;
use App\Models\Cliente;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use App\Models\Vehiculo;

function clienteUsuarioConPermisos(array $slugs, int $tallerId): UsuarioSistema
{
    $permisos = collect($slugs)->map(fn (string $slug) => Permiso::factory()->create(['slug' => $slug, 'activo' => true]));
    $rol = Rol::factory()->create(['taller_id' => $tallerId]);
    $rol->permisos()->attach($permisos->pluck('id'));
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerId, asignadoPor: null);

    return $usuario;
}

// --- ClienteResource ---

it('ClienteResource: cada permiso (ver/crear/editar/eliminar) es independiente', function () {
    $taller = Taller::factory()->create();
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $soloVer = clienteUsuarioConPermisos(['clientes.ver'], $taller->id);
    $sinPermiso = UsuarioSistema::factory()->create();

    session(['taller_activo_id' => $taller->id]);

    $this->actingAs($soloVer, 'sistema');
    expect(ClienteResource::canViewAny())->toBeTrue();
    expect(ClienteResource::canCreate())->toBeFalse();
    expect(ClienteResource::canEdit($cliente))->toBeFalse();
    expect(ClienteResource::canDelete($cliente))->toBeFalse();

    $this->actingAs($sinPermiso, 'sistema');
    expect(ClienteResource::canViewAny())->toBeFalse();
});

it('ClienteResource: solo lista clientes del taller activo', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    Cliente::factory()->create(['taller_id' => $tallerA->id]);
    Cliente::factory()->create(['taller_id' => $tallerB->id]);

    session(['taller_activo_id' => $tallerA->id]);

    expect(ClienteResource::getEloquentQuery()->pluck('taller_id')->unique()->all())->toBe([$tallerA->id]);
});

it('ClienteResource: las paginas de listado, creacion y edicion cargan con permiso', function () {
    $taller = Taller::factory()->create();
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $usuario = clienteUsuarioConPermisos(['clientes.ver', 'clientes.crear', 'clientes.editar'], $taller->id);

    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    $this->get(ClienteResource::getUrl('index', panel: 'erp'))->assertSuccessful();
    $this->get(ClienteResource::getUrl('create', panel: 'erp'))->assertSuccessful();
    $this->get(ClienteResource::getUrl('edit', ['record' => $cliente], panel: 'erp'))->assertSuccessful();
});

// --- VehiculoResource ---

it('VehiculoResource: cada permiso (ver/crear/editar/eliminar) es independiente', function () {
    $taller = Taller::factory()->create();
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $vehiculo = Vehiculo::factory()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id]);
    $soloVer = clienteUsuarioConPermisos(['vehiculos.ver'], $taller->id);
    $sinPermiso = UsuarioSistema::factory()->create();

    session(['taller_activo_id' => $taller->id]);

    $this->actingAs($soloVer, 'sistema');
    expect(VehiculoResource::canViewAny())->toBeTrue();
    expect(VehiculoResource::canCreate())->toBeFalse();
    expect(VehiculoResource::canEdit($vehiculo))->toBeFalse();
    expect(VehiculoResource::canDelete($vehiculo))->toBeFalse();

    $this->actingAs($sinPermiso, 'sistema');
    expect(VehiculoResource::canViewAny())->toBeFalse();
});

it('VehiculoResource: solo lista vehiculos del taller activo', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    $clienteA = Cliente::factory()->create(['taller_id' => $tallerA->id]);
    $clienteB = Cliente::factory()->create(['taller_id' => $tallerB->id]);
    Vehiculo::factory()->create(['taller_id' => $tallerA->id, 'cliente_id' => $clienteA->id]);
    Vehiculo::factory()->create(['taller_id' => $tallerB->id, 'cliente_id' => $clienteB->id]);

    session(['taller_activo_id' => $tallerA->id]);

    expect(VehiculoResource::getEloquentQuery()->pluck('taller_id')->unique()->all())->toBe([$tallerA->id]);
});

it('VehiculoResource: las paginas de listado, creacion y edicion cargan con permiso', function () {
    $taller = Taller::factory()->create();
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $vehiculo = Vehiculo::factory()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id]);
    $usuario = clienteUsuarioConPermisos(['vehiculos.ver', 'vehiculos.crear', 'vehiculos.editar'], $taller->id);

    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    $this->get(VehiculoResource::getUrl('index', panel: 'erp'))->assertSuccessful();
    $this->get(VehiculoResource::getUrl('create', panel: 'erp'))->assertSuccessful();
    $this->get(VehiculoResource::getUrl('edit', ['record' => $vehiculo], panel: 'erp'))->assertSuccessful();
});
