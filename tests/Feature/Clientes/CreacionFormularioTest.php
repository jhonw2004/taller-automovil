<?php

use App\Actions\Roles\AsignarRolAction;
use App\Filament\Erp\Resources\ClienteResource\Pages\CreateCliente;
use App\Filament\Erp\Resources\VehiculoResource\Pages\CreateVehiculo;
use App\Models\Cliente;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use App\Models\Vehiculo;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('erp')));

/**
 * `scopedUnique()` en un campo opcional (`nit_ci`) construye la query directo sobre
 * `Cliente::query()->where('nit_ci', $value)` (007-plan.md, mismo helper que ya usa
 * `RolResource::slug` de 002) — se verifica de punta a punta vía Livewire, no solo a nivel de
 * Form Request, que dos clientes con NIT/CI en blanco en el mismo taller no colisionan entre sí
 * (el valor en blanco nunca llega a la BD como cadena vacía real, ver `Cliente::nitCi()`).
 */
function usuarioConPermisosDeCliente(array $slugs, int $tallerId): UsuarioSistema
{
    $permisos = collect($slugs)->map(fn (string $slug) => Permiso::factory()->create(['slug' => $slug, 'activo' => true]));
    $rol = Rol::factory()->create(['taller_id' => $tallerId]);
    $rol->permisos()->attach($permisos->pluck('id'));
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerId, asignadoPor: null);

    return $usuario;
}

it('crea un cliente sin nit_ci desde el formulario Filament', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeCliente(['clientes.ver', 'clientes.crear'], $taller->id);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    Livewire::test(CreateCliente::class)
        ->fillForm([
            'codigo' => 'CLI-100',
            'tipo_persona' => 'NATURAL',
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'nit_ci' => '',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Cliente::where('codigo', 'CLI-100')->first()?->nit_ci)->toBeNull();
});

it('permite crear un segundo cliente sin nit_ci en el mismo taller sin falso positivo de unicidad', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeCliente(['clientes.ver', 'clientes.crear'], $taller->id);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    Cliente::factory()->create(['taller_id' => $taller->id, 'nit_ci' => null]);

    Livewire::test(CreateCliente::class)
        ->fillForm([
            'codigo' => 'CLI-101',
            'tipo_persona' => 'NATURAL',
            'nombre' => 'Ana',
            'apellido' => 'Lopez',
            'nit_ci' => '',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Cliente::where('codigo', 'CLI-101')->exists())->toBeTrue();
});

it('rechaza un nit_ci duplicado real desde el formulario Filament', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeCliente(['clientes.ver', 'clientes.crear'], $taller->id);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    Cliente::factory()->create(['taller_id' => $taller->id, 'nit_ci' => '9999999']);

    Livewire::test(CreateCliente::class)
        ->fillForm([
            'codigo' => 'CLI-102',
            'tipo_persona' => 'NATURAL',
            'nombre' => 'Pedro',
            'apellido' => 'Gomez',
            'nit_ci' => '9999999',
        ])
        ->call('create')
        ->assertHasFormErrors(['nit_ci']);
});

it('normaliza la placa y crea el vehiculo desde el formulario Filament', function () {
    $taller = Taller::factory()->create();
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $usuario = usuarioConPermisosDeCliente(['vehiculos.ver', 'vehiculos.crear'], $taller->id);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    Livewire::test(CreateVehiculo::class)
        ->fillForm([
            'cliente_id' => $cliente->id,
            'placa' => 'abc-123',
            'tipo_vehiculo' => 'AUTO',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Vehiculo::where('taller_id', $taller->id)->first()?->placa)->toBe('ABC123');
});
