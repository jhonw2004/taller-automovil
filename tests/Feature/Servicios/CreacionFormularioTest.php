<?php

use App\Actions\Roles\AsignarRolAction;
use App\Filament\Erp\Resources\ServicioResource\Pages\CreateServicio;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\ServicioCatalogo;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('erp')));

function usuarioConPermisosDeServicioParaCrear(array $slugs, int $tallerId): UsuarioSistema
{
    $permisos = collect($slugs)->map(fn (string $slug) => Permiso::factory()->create(['slug' => $slug, 'activo' => true]));
    $rol = Rol::factory()->create(['taller_id' => $tallerId]);
    $rol->permisos()->attach($permisos->pluck('id'));
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerId, asignadoPor: null);

    return $usuario;
}

it('crea un servicio desde el formulario Filament', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeServicioParaCrear(['servicios.ver', 'servicios.crear'], $taller->id);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    Livewire::test(CreateServicio::class)
        ->fillForm([
            'codigo' => 'SRV-200',
            'nombre' => 'Cambio de aceite',
            'precio_base' => 120.50,
            'duracion_minutos' => 30,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $servicio = ServicioCatalogo::where('codigo', 'SRV-200')->first();
    expect($servicio)->not->toBeNull();
    expect($servicio->taller_id)->toBe($taller->id);
    expect($servicio->precio_base)->toBe('120.50');
});

it('rechaza un precio_base negativo desde el formulario Filament', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeServicioParaCrear(['servicios.ver', 'servicios.crear'], $taller->id);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    Livewire::test(CreateServicio::class)
        ->fillForm([
            'codigo' => 'SRV-201',
            'nombre' => 'Cambio de aceite',
            'precio_base' => -5,
        ])
        ->call('create')
        ->assertHasFormErrors(['precio_base']);
});

it('rechaza un codigo duplicado real desde el formulario Filament', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeServicioParaCrear(['servicios.ver', 'servicios.crear'], $taller->id);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    ServicioCatalogo::factory()->create(['taller_id' => $taller->id, 'codigo' => 'SRV-202']);

    Livewire::test(CreateServicio::class)
        ->fillForm([
            'codigo' => 'SRV-202',
            'nombre' => 'Cambio de aceite',
            'precio_base' => 50,
        ])
        ->call('create')
        ->assertHasFormErrors(['codigo']);
});
