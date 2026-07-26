<?php

use App\Actions\Roles\AsignarRolAction;
use App\Filament\Admin\Resources\SolicitudTallerResource;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\SolicitudTaller;
use App\Models\UsuarioSistema;

// Reutiliza el helper global `usuarioConPermiso($slugs, $tallerId)` definido en
// tests/Feature/Roles/ResourcesAutorizacionTest.php (Pest carga todos los tests en el mismo
// proceso, así que las funciones de nivel superior ya están disponibles sin importarlas).

it('SolicitudTallerResource: requiere solicitudes.ver para ver, nunca permite crear ni editar ni eliminar', function () {
    $solicitud = SolicitudTaller::factory()->create();
    $conPermiso = usuarioConPermiso('solicitudes.ver', null);
    $sinPermiso = UsuarioSistema::factory()->create();

    $this->actingAs($conPermiso, 'sistema');
    expect(SolicitudTallerResource::canViewAny())->toBeTrue();
    expect(SolicitudTallerResource::canCreate())->toBeFalse();
    expect(SolicitudTallerResource::canEdit($solicitud))->toBeFalse();
    expect(SolicitudTallerResource::canDelete($solicitud))->toBeFalse();

    $this->actingAs($sinPermiso, 'sistema');
    expect(SolicitudTallerResource::canViewAny())->toBeFalse();
});

it('SolicitudTallerResource: la pagina de listado carga para un usuario con permiso', function () {
    SolicitudTaller::factory()->count(3)->create();

    // `canAccessPanel('admin')` exige el rol global `super-admin` (no solo el permiso del
    // Resource) — igual que en tests/Feature/Roles/ResourcesAutorizacionTest.php.
    $permiso = Permiso::factory()->create(['slug' => 'solicitudes.ver', 'activo' => true]);
    $rolSuperAdmin = Rol::factory()->create(['slug' => 'super-admin', 'taller_id' => null]);
    $rolSuperAdmin->permisos()->attach($permiso->id);
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rolSuperAdmin, null, asignadoPor: null);

    $this->actingAs($usuario, 'sistema');

    $this->get(SolicitudTallerResource::getUrl('index', panel: 'admin'))->assertSuccessful();
});
