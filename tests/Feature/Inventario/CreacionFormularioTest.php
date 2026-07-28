<?php

use App\Actions\Roles\AsignarRolAction;
use App\Filament\Erp\Resources\ProveedorResource\Pages\CreateProveedor;
use App\Filament\Erp\Resources\RepuestoResource\Pages\CreateRepuesto;
use App\Models\Permiso;
use App\Models\Proveedor;
use App\Models\Repuesto;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('erp')));

function usuarioConPermisosDeInventarioParaCrear(array $slugs, int $tallerId): UsuarioSistema
{
    $permisos = collect($slugs)->map(fn (string $slug) => Permiso::factory()->create(['slug' => $slug, 'activo' => true]));
    $rol = Rol::factory()->create(['taller_id' => $tallerId]);
    $rol->permisos()->attach($permisos->pluck('id'));
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerId, asignadoPor: null);

    return $usuario;
}

it('crea un repuesto desde el formulario Filament con stock_actual en cero', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeInventarioParaCrear(['repuestos.ver', 'repuestos.crear'], $taller->id);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    Livewire::test(CreateRepuesto::class)
        ->fillForm([
            'codigo' => 'REP-300',
            'nombre' => 'Filtro de aire',
            'precio_costo' => 15.50,
            'precio_venta' => 30,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $repuesto = Repuesto::where('codigo', 'REP-300')->first();
    expect($repuesto)->not->toBeNull();
    expect($repuesto->taller_id)->toBe($taller->id);
    expect((float) $repuesto->stock_actual)->toBe(0.0);
});

it('rechaza un precio_venta negativo desde el formulario Filament', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeInventarioParaCrear(['repuestos.ver', 'repuestos.crear'], $taller->id);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    Livewire::test(CreateRepuesto::class)
        ->fillForm([
            'codigo' => 'REP-301',
            'nombre' => 'Filtro de aire',
            'precio_costo' => 10,
            'precio_venta' => -5,
        ])
        ->call('create')
        ->assertHasFormErrors(['precio_venta']);
});

it('rechaza un codigo de repuesto duplicado real desde el formulario Filament', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeInventarioParaCrear(['repuestos.ver', 'repuestos.crear'], $taller->id);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    Repuesto::factory()->create(['taller_id' => $taller->id, 'codigo' => 'REP-302']);

    Livewire::test(CreateRepuesto::class)
        ->fillForm([
            'codigo' => 'REP-302',
            'nombre' => 'Filtro de aire',
            'precio_costo' => 10,
            'precio_venta' => 20,
        ])
        ->call('create')
        ->assertHasFormErrors(['codigo']);
});

it('crea un segundo repuesto sin codigo_barras en el mismo taller sin colision', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeInventarioParaCrear(['repuestos.ver', 'repuestos.crear'], $taller->id);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    Repuesto::factory()->create(['taller_id' => $taller->id, 'codigo_barras' => null]);

    Livewire::test(CreateRepuesto::class)
        ->fillForm([
            'codigo' => 'REP-303',
            'nombre' => 'Filtro de aire',
            'precio_costo' => 10,
            'precio_venta' => 20,
        ])
        ->call('create')
        ->assertHasNoFormErrors();
});

it('crea un proveedor desde el formulario Filament', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeInventarioParaCrear(['proveedores.ver', 'proveedores.crear'], $taller->id);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    Livewire::test(CreateProveedor::class)
        ->fillForm([
            'nombre' => 'Repuestos del Sur SRL',
            'nit' => '987654321',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $proveedor = Proveedor::where('nombre', 'Repuestos del Sur SRL')->first();
    expect($proveedor)->not->toBeNull();
    expect($proveedor->taller_id)->toBe($taller->id);
});

it('rechaza un nit de proveedor duplicado real desde el formulario Filament', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeInventarioParaCrear(['proveedores.ver', 'proveedores.crear'], $taller->id);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    Proveedor::factory()->create(['taller_id' => $taller->id, 'nit' => '111222333']);

    Livewire::test(CreateProveedor::class)
        ->fillForm([
            'nombre' => 'Otro Proveedor',
            'nit' => '111222333',
        ])
        ->call('create')
        ->assertHasFormErrors(['nit']);
});
