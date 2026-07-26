<?php

use App\Actions\Roles\AsignarRolAction;
use App\Filament\Admin\Resources\CategoriaResource;
use App\Filament\Admin\Resources\TallerResource as TallerResourceAdmin;
use App\Filament\Erp\Resources\TallerResource as TallerResourceErp;
use App\Models\Categoria;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;

function tallerUsuarioConPermisos(array $slugs, ?int $tallerId): UsuarioSistema
{
    $permisos = collect($slugs)->map(fn (string $slug) => Permiso::factory()->create(['slug' => $slug, 'activo' => true]));
    $rol = Rol::factory()->create(['taller_id' => $tallerId]);
    $rol->permisos()->attach($permisos->pluck('id'));
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerId, asignadoPor: null);

    return $usuario;
}

function tallerSuperAdminConPermisos(array $slugs): UsuarioSistema
{
    $permisos = collect($slugs)->map(fn (string $slug) => Permiso::factory()->create(['slug' => $slug, 'activo' => true]));
    $rol = Rol::factory()->create(['taller_id' => null, 'slug' => 'super-admin']);
    $rol->permisos()->attach($permisos->pluck('id'));
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, null, asignadoPor: null);

    return $usuario;
}

// --- TallerResource (erp, un solo registro: el taller activo) ---

it('TallerResource erp: requiere taller.ver para ver, no permite crear ni eliminar', function () {
    $taller = Taller::factory()->create();
    $conPermiso = tallerUsuarioConPermisos(['taller.ver'], $taller->id);
    $sinPermiso = UsuarioSistema::factory()->create();

    session(['taller_activo_id' => $taller->id]);

    $this->actingAs($conPermiso, 'sistema');
    expect(TallerResourceErp::canViewAny())->toBeTrue();
    expect(TallerResourceErp::canCreate())->toBeFalse();
    expect(TallerResourceErp::canDelete($taller))->toBeFalse();

    $this->actingAs($sinPermiso, 'sistema');
    expect(TallerResourceErp::canViewAny())->toBeFalse();
});

it('TallerResource erp: solo muestra el taller activo, no otros talleres', function () {
    $tallerA = Taller::factory()->create();
    Taller::factory()->create();

    session(['taller_activo_id' => $tallerA->id]);

    expect(TallerResourceErp::getEloquentQuery()->pluck('id')->all())->toBe([$tallerA->id]);
});

it('TallerResource erp: las paginas de listado y edicion cargan para un usuario con permiso', function () {
    $taller = Taller::factory()->create();
    $usuario = tallerUsuarioConPermisos(['taller.ver', 'taller.editar'], $taller->id);

    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    $this->get(TallerResourceErp::getUrl('index', panel: 'erp'))->assertSuccessful();
    $this->get(TallerResourceErp::getUrl('edit', ['record' => $taller], panel: 'erp'))->assertSuccessful();
});

// --- TallerResource (admin, global) ---

it('TallerResource admin: requiere admin.talleres.ver, y las acciones de suspender/propietario requieren su propio permiso', function () {
    $taller = Taller::factory()->create();
    $soloVer = tallerSuperAdminConPermisos(['admin.talleres.ver']);
    $sinPermiso = UsuarioSistema::factory()->create();

    $this->actingAs($soloVer, 'sistema');
    expect(TallerResourceAdmin::canViewAny())->toBeTrue();

    $this->actingAs($sinPermiso, 'sistema');
    expect(TallerResourceAdmin::canViewAny())->toBeFalse();
});

it('TallerResource admin: no tiene pagina de creacion ni edicion propia', function () {
    $taller = Taller::factory()->create();

    expect(TallerResourceAdmin::canCreate())->toBeFalse();
    expect(TallerResourceAdmin::canEdit($taller))->toBeFalse();
});

it('TallerResource admin: la pagina de listado carga y ve todos los talleres', function () {
    Taller::factory()->count(3)->create();
    $admin = tallerSuperAdminConPermisos(['admin.talleres.ver', 'admin.talleres.suspender', 'admin.talleres.cambiar_propietario']);

    $this->actingAs($admin, 'sistema');

    $this->get(TallerResourceAdmin::getUrl('index', panel: 'admin'))->assertSuccessful();
    expect(TallerResourceAdmin::getEloquentQuery()->count())->toBe(3);
});

// --- CategoriaResource (admin) ---

it('CategoriaResource: solo el super admin puede verlo, crearlo y editarlo; nunca eliminarlo', function () {
    $rolSuperAdmin = Rol::factory()->create(['slug' => 'super-admin', 'taller_id' => null]);
    $superAdmin = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($superAdmin, $rolSuperAdmin, null);
    $noAdmin = UsuarioSistema::factory()->create();
    $categoria = Categoria::factory()->create();

    $this->actingAs($superAdmin, 'sistema');
    expect(CategoriaResource::canViewAny())->toBeTrue();
    expect(CategoriaResource::canCreate())->toBeTrue();
    expect(CategoriaResource::canEdit($categoria))->toBeTrue();
    expect(CategoriaResource::canDelete($categoria))->toBeFalse();

    $this->actingAs($noAdmin, 'sistema');
    expect(CategoriaResource::canViewAny())->toBeFalse();
});

it('CategoriaResource: las paginas de listado, creacion y edicion cargan para el super admin', function () {
    $rolSuperAdmin = Rol::factory()->create(['slug' => 'super-admin', 'taller_id' => null]);
    $superAdmin = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($superAdmin, $rolSuperAdmin, null);
    $categoria = Categoria::factory()->create();

    $this->actingAs($superAdmin, 'sistema');

    $this->get(CategoriaResource::getUrl('index', panel: 'admin'))->assertSuccessful();
    $this->get(CategoriaResource::getUrl('create', panel: 'admin'))->assertSuccessful();
    $this->get(CategoriaResource::getUrl('edit', ['record' => $categoria], panel: 'admin'))->assertSuccessful();
});
