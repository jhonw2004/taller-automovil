<?php

use App\Actions\Roles\AsignarRolAction;
use App\Filament\Admin\Resources\PermisoResource;
use App\Filament\Admin\Resources\RolResource as RolResourceAdmin;
use App\Filament\Erp\Resources\AsignacionRolResource;
use App\Filament\Erp\Resources\RolResource as RolResourceErp;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;

function usuarioConPermiso(string|array $slugs, ?int $tallerId): UsuarioSistema
{
    $permisos = collect((array) $slugs)->map(fn (string $slug) => Permiso::factory()->create(['slug' => $slug, 'activo' => true]));
    $rol = Rol::factory()->create(['taller_id' => $tallerId]);
    $rol->permisos()->attach($permisos->pluck('id'));
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerId, asignadoPor: null);

    return $usuario;
}

// --- PermisoResource (admin, solo super admin) ---

it('PermisoResource: solo el super admin puede verlo y crearlo', function () {
    $rolSuperAdmin = Rol::factory()->create(['slug' => 'super-admin', 'taller_id' => null]);
    $superAdmin = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($superAdmin, $rolSuperAdmin, null);

    $noAdmin = UsuarioSistema::factory()->create();

    $this->actingAs($superAdmin, 'sistema');
    expect(PermisoResource::canViewAny())->toBeTrue();
    expect(PermisoResource::canCreate())->toBeTrue();

    $this->actingAs($noAdmin, 'sistema');
    expect(PermisoResource::canViewAny())->toBeFalse();
});

it('PermisoResource: la pagina de listado carga para el super admin', function () {
    $rolSuperAdmin = Rol::factory()->create(['slug' => 'super-admin', 'taller_id' => null]);
    $superAdmin = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($superAdmin, $rolSuperAdmin, null);

    $this->actingAs($superAdmin, 'sistema')->get(PermisoResource::getUrl('index', panel: 'admin'))->assertSuccessful();
});

// --- RolResource (admin, roles globales) ---

it('RolResource admin: solo con permiso admin.roles.gestionar puede verlo', function () {
    $conPermiso = usuarioConPermiso('admin.roles.gestionar', null);
    $sinPermiso = UsuarioSistema::factory()->create();

    $this->actingAs($conPermiso, 'sistema');
    expect(RolResourceAdmin::canViewAny())->toBeTrue();

    $this->actingAs($sinPermiso, 'sistema');
    expect(RolResourceAdmin::canViewAny())->toBeFalse();
});

it('RolResource admin: no permite editar ni eliminar roles de sistema', function () {
    $usuario = usuarioConPermiso('admin.roles.gestionar', null);
    $rolSistema = Rol::factory()->create(['taller_id' => null, 'es_sistema' => true]);

    $this->actingAs($usuario, 'sistema');

    expect(RolResourceAdmin::canEdit($rolSistema))->toBeFalse();
    expect(RolResourceAdmin::canDelete($rolSistema))->toBeFalse();
});

it('RolResource admin: las paginas de listado, creacion y edicion cargan con permiso admin.roles.gestionar', function () {
    // El panel /admin exige esSuperAdmin() en canAccessPanel() (independiente del permiso del
    // Resource) — el rol debe ser slug 'super-admin' Y tener el permiso adjunto, igual que en
    // producción (RolSistemaSeeder adjunta TODOS los permisos al rol super-admin).
    $permiso = Permiso::factory()->create(['slug' => 'admin.roles.gestionar', 'activo' => true]);
    $rolSuperAdmin = Rol::factory()->create(['slug' => 'super-admin', 'taller_id' => null]);
    $rolSuperAdmin->permisos()->attach($permiso);
    $admin = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($admin, $rolSuperAdmin, null);
    $rolExistente = Rol::factory()->create(['taller_id' => null]);

    $this->actingAs($admin, 'sistema');

    $this->get(RolResourceAdmin::getUrl('index', panel: 'admin'))->assertSuccessful();
    $this->get(RolResourceAdmin::getUrl('create', panel: 'admin'))->assertSuccessful();
    $this->get(RolResourceAdmin::getUrl('edit', ['record' => $rolExistente], panel: 'admin'))->assertSuccessful();
});

it('RolResource admin: solo lista roles globales (taller_id null)', function () {
    $usuario = usuarioConPermiso('admin.roles.gestionar', null);
    $taller = Taller::factory()->create();
    Rol::factory()->create(['taller_id' => null, 'nombre' => 'Global Uno']);
    Rol::factory()->create(['taller_id' => $taller->id, 'nombre' => 'Del taller']);

    expect(RolResourceAdmin::getEloquentQuery()->pluck('taller_id')->unique()->all())->toBe([null]);
});

// --- RolResource (erp, roles del taller activo) ---

it('RolResource erp: requiere permiso roles.ver para el taller activo', function () {
    $taller = Taller::factory()->create();
    $conPermiso = usuarioConPermiso('roles.ver', $taller->id);
    $sinPermiso = UsuarioSistema::factory()->create();

    session(['taller_activo_id' => $taller->id]);

    $this->actingAs($conPermiso, 'sistema');
    expect(RolResourceErp::canViewAny())->toBeTrue();

    $this->actingAs($sinPermiso, 'sistema');
    expect(RolResourceErp::canViewAny())->toBeFalse();
});

it('RolResource erp: las paginas de listado, creacion y edicion cargan para un admin de taller', function () {
    $taller = Taller::factory()->create();
    $admin = usuarioConPermiso(['roles.ver', 'roles.crear', 'roles.editar'], $taller->id);
    $rolExistente = Rol::factory()->create(['taller_id' => $taller->id]);

    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($admin, 'sistema');

    $this->get(RolResourceErp::getUrl('index', panel: 'erp'))->assertSuccessful();
    $this->get(RolResourceErp::getUrl('create', panel: 'erp'))->assertSuccessful();
    $this->get(RolResourceErp::getUrl('edit', ['record' => $rolExistente], panel: 'erp'))->assertSuccessful();
});

it('RolResource erp: solo lista roles del taller activo, no de otro taller', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    Rol::factory()->create(['taller_id' => $tallerA->id, 'nombre' => 'De A']);
    Rol::factory()->create(['taller_id' => $tallerB->id, 'nombre' => 'De B']);

    session(['taller_activo_id' => $tallerA->id]);

    $nombres = RolResourceErp::getEloquentQuery()->pluck('nombre')->all();
    expect($nombres)->toContain('De A');
    expect($nombres)->not->toContain('De B');
});

// --- AsignacionRolResource (erp) ---

it('AsignacionRolResource: requiere permiso usuarios.gestionar para el taller activo', function () {
    $taller = Taller::factory()->create();
    $conPermiso = usuarioConPermiso('usuarios.gestionar', $taller->id);
    $sinPermiso = UsuarioSistema::factory()->create();

    session(['taller_activo_id' => $taller->id]);

    $this->actingAs($conPermiso, 'sistema');
    expect(AsignacionRolResource::canViewAny())->toBeTrue();
    expect(AsignacionRolResource::canCreate())->toBeTrue();

    $this->actingAs($sinPermiso, 'sistema');
    expect(AsignacionRolResource::canViewAny())->toBeFalse();
});

it('AsignacionRolResource: no permite editar ni eliminar (solo crear/desactivar)', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermiso('usuarios.gestionar', $taller->id);
    $rol = Rol::factory()->create(['taller_id' => null]);
    $asignacion = app(AsignarRolAction::class)->execute($usuario, $rol, $taller->id, asignadoPor: null);

    expect(AsignacionRolResource::canEdit($asignacion))->toBeFalse();
    expect(AsignacionRolResource::canDelete($asignacion))->toBeFalse();
});

it('AsignacionRolResource: la pagina de listado y creacion cargan para un admin de taller', function () {
    $taller = Taller::factory()->create();
    $admin = usuarioConPermiso('usuarios.gestionar', $taller->id);

    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($admin, 'sistema');

    $this->get(AsignacionRolResource::getUrl('index', panel: 'erp'))->assertSuccessful();
    $this->get(AsignacionRolResource::getUrl('create', panel: 'erp'))->assertSuccessful();
});
