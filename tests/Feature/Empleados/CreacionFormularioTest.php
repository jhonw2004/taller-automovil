<?php

use App\Actions\Roles\AsignarRolAction;
use App\Filament\Erp\Resources\EmpleadoResource\Pages\CreateEmpleado;
use App\Filament\Erp\Resources\EmpleadoResource\Pages\EditEmpleado;
use App\Models\Empleado;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('erp')));

function usuarioConPermisosDeEmpleadosFormulario(array $slugs, int $tallerId): UsuarioSistema
{
    $permisos = collect($slugs)->map(fn (string $slug) => Permiso::factory()->create(['slug' => $slug, 'activo' => true]));
    $rol = Rol::factory()->create(['taller_id' => $tallerId]);
    $rol->permisos()->attach($permisos->pluck('id'));
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerId, asignadoPor: null);

    return $usuario;
}

it('crea un empleado sin acceso desde el formulario Filament', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeEmpleadosFormulario(['empleados.ver', 'empleados.crear'], $taller->id);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    Livewire::test(CreateEmpleado::class)
        ->fillForm([
            'codigo' => 'EMP-200',
            'nombre' => 'Carlos',
            'apellido' => 'Mamani',
            'tiene_acceso' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $empleado = Empleado::where('codigo', 'EMP-200')->first();
    expect($empleado)->not->toBeNull();
    expect($empleado->usuario_sistema_id)->toBeNull();
});

it('crea un empleado con acceso desde el formulario Filament', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeEmpleadosFormulario(['empleados.ver', 'empleados.crear'], $taller->id);
    $rol = Rol::factory()->create(['taller_id' => null]);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    Livewire::test(CreateEmpleado::class)
        ->fillForm([
            'codigo' => 'EMP-201',
            'nombre' => 'Maria',
            'apellido' => 'Rojas',
            'tiene_acceso' => true,
            'username' => 'mrojas',
            'rol_id' => $rol->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $empleado = Empleado::where('codigo', 'EMP-201')->first();
    expect($empleado->usuario_sistema_id)->not->toBeNull();
    expect(UsuarioSistema::find($empleado->usuario_sistema_id)?->username)->toBe('mrojas');
});

it('otorga acceso despues a un empleado existente sin acceso desde el formulario de edicion', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeEmpleadosFormulario(['empleados.ver', 'empleados.editar'], $taller->id);
    $empleado = Empleado::factory()->create(['taller_id' => $taller->id, 'usuario_sistema_id' => null]);
    $rol = Rol::factory()->create(['taller_id' => null]);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    Livewire::test(EditEmpleado::class, ['record' => $empleado->getRouteKey()])
        ->fillForm([
            'tiene_acceso' => true,
            'username' => 'acceso.tardio',
            'rol_id' => $rol->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($empleado->fresh()->usuario_sistema_id)->not->toBeNull();
    expect(UsuarioSistema::find($empleado->fresh()->usuario_sistema_id)?->username)->toBe('acceso.tardio');
});
