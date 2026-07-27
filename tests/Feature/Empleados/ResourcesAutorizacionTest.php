<?php

use App\Actions\Roles\AsignarRolAction;
use App\Filament\Erp\Resources\EmpleadoResource;
use App\Filament\Erp\Resources\UsuarioResource;
use App\Models\Empleado;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;

function usuarioConPermisosDeEmpleados(array $slugs, int $tallerId): UsuarioSistema
{
    $permisos = collect($slugs)->map(fn (string $slug) => Permiso::factory()->create(['slug' => $slug, 'activo' => true]));
    $rol = Rol::factory()->create(['taller_id' => $tallerId]);
    $rol->permisos()->attach($permisos->pluck('id'));
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerId, asignadoPor: null);

    return $usuario;
}

// --- EmpleadoResource ---

it('EmpleadoResource: cada permiso (ver/crear/editar/eliminar) es independiente', function () {
    $taller = Taller::factory()->create();
    $empleado = Empleado::factory()->create(['taller_id' => $taller->id]);
    $soloVer = usuarioConPermisosDeEmpleados(['empleados.ver'], $taller->id);
    $sinPermiso = UsuarioSistema::factory()->create();

    session(['taller_activo_id' => $taller->id]);

    $this->actingAs($soloVer, 'sistema');
    expect(EmpleadoResource::canViewAny())->toBeTrue();
    expect(EmpleadoResource::canCreate())->toBeFalse();
    expect(EmpleadoResource::canEdit($empleado))->toBeFalse();
    expect(EmpleadoResource::canDelete($empleado))->toBeFalse();

    $this->actingAs($sinPermiso, 'sistema');
    expect(EmpleadoResource::canViewAny())->toBeFalse();
});

it('EmpleadoResource: solo lista empleados del taller activo', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    Empleado::factory()->create(['taller_id' => $tallerA->id]);
    Empleado::factory()->create(['taller_id' => $tallerB->id]);

    session(['taller_activo_id' => $tallerA->id]);

    expect(EmpleadoResource::getEloquentQuery()->pluck('taller_id')->unique()->all())->toBe([$tallerA->id]);
});

it('EmpleadoResource: las paginas de listado, creacion y edicion cargan con permiso', function () {
    $taller = Taller::factory()->create();
    $empleado = Empleado::factory()->create(['taller_id' => $taller->id]);
    $usuario = usuarioConPermisosDeEmpleados(['empleados.ver', 'empleados.crear', 'empleados.editar'], $taller->id);

    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    $this->get(EmpleadoResource::getUrl('index', panel: 'erp'))->assertSuccessful();
    $this->get(EmpleadoResource::getUrl('create', panel: 'erp'))->assertSuccessful();
    $this->get(EmpleadoResource::getUrl('edit', ['record' => $empleado], panel: 'erp'))->assertSuccessful();
});

// --- UsuarioResource ---

it('UsuarioResource: requiere permiso usuarios.ver y no permite crear/editar/eliminar', function () {
    $taller = Taller::factory()->create();
    $conPermiso = usuarioConPermisosDeEmpleados(['usuarios.ver'], $taller->id);
    $sinPermiso = UsuarioSistema::factory()->create();

    session(['taller_activo_id' => $taller->id]);

    $this->actingAs($conPermiso, 'sistema');
    expect(UsuarioResource::canViewAny())->toBeTrue();
    expect(UsuarioResource::canCreate())->toBeFalse();
    expect(UsuarioResource::canEdit($conPermiso))->toBeFalse();
    expect(UsuarioResource::canDelete($conPermiso))->toBeFalse();

    $this->actingAs($sinPermiso, 'sistema');
    expect(UsuarioResource::canViewAny())->toBeFalse();
});

it('UsuarioResource: solo lista usuarios con asignacion vigente en el taller activo', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    $usuarioA = usuarioConPermisosDeEmpleados(['usuarios.ver'], $tallerA->id);
    $usuarioB = UsuarioSistema::factory()->create();
    $rolB = Rol::factory()->create(['taller_id' => $tallerB->id]);
    app(AsignarRolAction::class)->execute($usuarioB, $rolB, $tallerB->id, asignadoPor: null);

    session(['taller_activo_id' => $tallerA->id]);

    $ids = UsuarioResource::getEloquentQuery()->pluck('id')->all();

    expect($ids)->toContain($usuarioA->id);
    expect($ids)->not->toContain($usuarioB->id);
});

it('UsuarioResource: la pagina de listado carga con permiso', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeEmpleados(['usuarios.ver'], $taller->id);

    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    $this->get(UsuarioResource::getUrl('index', panel: 'erp'))->assertSuccessful();
});
