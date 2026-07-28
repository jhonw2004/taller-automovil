<?php

use App\Actions\Auditoria\RegistrarEventoAuditoriaAction;
use App\Actions\Roles\AsignarRolAction;
use App\Filament\Admin\Resources\AuditoriaAccesoResource as AuditoriaAccesoResourceAdmin;
use App\Filament\Admin\Resources\AuditoriaEventoResource as AuditoriaEventoResourceAdmin;
use App\Filament\Admin\Resources\AuditoriaTallerResource as AuditoriaTallerResourceAdmin;
use App\Filament\Erp\Resources\AuditoriaAccesoResource as AuditoriaAccesoResourceErp;
use App\Filament\Erp\Resources\AuditoriaEventoResource as AuditoriaEventoResourceErp;
use App\Filament\Erp\Resources\AuditoriaTallerResource as AuditoriaTallerResourceErp;
use App\Models\AuditoriaEvento;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;

function auditoriaUsuarioConPermiso(string $slug, ?int $tallerId): UsuarioSistema
{
    $permiso = Permiso::factory()->create(['slug' => $slug, 'activo' => true]);
    $rol = Rol::factory()->create(['taller_id' => $tallerId]);
    $rol->permisos()->attach($permiso->id);
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerId, asignadoPor: null);

    return $usuario;
}

/**
 * `/admin` exige `esSuperAdmin()` (rol slug `super-admin`) para cargar cualquier página del
 * panel, además del permiso propio del Resource — a diferencia de `canViewAny()` a secas
 * (comprobado en aislamiento, sin pasar por `canAccessPanel()`). Mismo patrón que
 * `tallerSuperAdminConPermisos()` en `tests/Feature/Talleres/ResourcesAutorizacionTest.php`.
 */
function auditoriaSuperAdminConPermiso(string $slug): UsuarioSistema
{
    $permiso = Permiso::factory()->create(['slug' => $slug, 'activo' => true]);
    $rol = Rol::factory()->create(['taller_id' => null, 'slug' => 'super-admin']);
    $rol->permisos()->attach($permiso->id);
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, null, asignadoPor: null);

    return $usuario;
}

// --- /erp: requieren auditoria.ver, filtran por taller activo ---

it('los 3 Resources de auditoria en /erp requieren auditoria.ver y niegan sin permiso', function () {
    $taller = Taller::factory()->create();
    $conPermiso = auditoriaUsuarioConPermiso('auditoria.ver', $taller->id);
    $sinPermiso = UsuarioSistema::factory()->create();

    session(['taller_activo_id' => $taller->id]);

    $this->actingAs($conPermiso, 'sistema');
    expect(AuditoriaEventoResourceErp::canViewAny())->toBeTrue();
    expect(AuditoriaAccesoResourceErp::canViewAny())->toBeTrue();
    expect(AuditoriaTallerResourceErp::canViewAny())->toBeTrue();

    $this->actingAs($sinPermiso, 'sistema');
    expect(AuditoriaEventoResourceErp::canViewAny())->toBeFalse();
    expect(AuditoriaAccesoResourceErp::canViewAny())->toBeFalse();
    expect(AuditoriaTallerResourceErp::canViewAny())->toBeFalse();
});

it('AuditoriaEventoResource erp: solo muestra eventos del taller activo', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    app(RegistrarEventoAuditoriaAction::class)->execute(evento: 'evento_a', tallerId: $tallerA->id);
    app(RegistrarEventoAuditoriaAction::class)->execute(evento: 'evento_b', tallerId: $tallerB->id);

    session(['taller_activo_id' => $tallerA->id]);

    expect(AuditoriaEventoResourceErp::getEloquentQuery()->pluck('evento')->all())->toBe(['evento_a']);
});

it('ningun Resource de auditoria permite crear, editar ni eliminar', function () {
    $taller = Taller::factory()->create();
    app(RegistrarEventoAuditoriaAction::class)->execute(evento: 'evento_x', tallerId: $taller->id);
    $evento = AuditoriaEvento::first();

    expect(AuditoriaEventoResourceErp::canCreate())->toBeFalse();
    expect(AuditoriaEventoResourceErp::canEdit($evento))->toBeFalse();
    expect(AuditoriaEventoResourceErp::canDelete($evento))->toBeFalse();
    expect(AuditoriaEventoResourceAdmin::canCreate())->toBeFalse();
    expect(AuditoriaAccesoResourceErp::canCreate())->toBeFalse();
    expect(AuditoriaTallerResourceErp::canCreate())->toBeFalse();
});

it('las paginas de listado en /erp cargan para un usuario con auditoria.ver', function () {
    $taller = Taller::factory()->create();
    $usuario = auditoriaUsuarioConPermiso('auditoria.ver', $taller->id);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    $this->get(AuditoriaEventoResourceErp::getUrl('index', panel: 'erp'))->assertSuccessful();
    $this->get(AuditoriaAccesoResourceErp::getUrl('index', panel: 'erp'))->assertSuccessful();
    $this->get(AuditoriaTallerResourceErp::getUrl('index', panel: 'erp'))->assertSuccessful();
});

// --- /admin: requieren admin.auditoria.ver, ven todos los talleres ---

it('los 3 Resources de auditoria en /admin requieren admin.auditoria.ver y niegan sin permiso', function () {
    $conPermiso = auditoriaUsuarioConPermiso('admin.auditoria.ver', null);
    $sinPermiso = UsuarioSistema::factory()->create();

    $this->actingAs($conPermiso, 'sistema');
    expect(AuditoriaEventoResourceAdmin::canViewAny())->toBeTrue();
    expect(AuditoriaAccesoResourceAdmin::canViewAny())->toBeTrue();
    expect(AuditoriaTallerResourceAdmin::canViewAny())->toBeTrue();

    $this->actingAs($sinPermiso, 'sistema');
    expect(AuditoriaEventoResourceAdmin::canViewAny())->toBeFalse();
});

it('AuditoriaEventoResource admin: ve eventos de todos los talleres', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    app(RegistrarEventoAuditoriaAction::class)->execute(evento: 'evento_a', tallerId: $tallerA->id);
    app(RegistrarEventoAuditoriaAction::class)->execute(evento: 'evento_b', tallerId: $tallerB->id);

    expect(AuditoriaEventoResourceAdmin::getEloquentQuery()->pluck('evento')->sort()->values()->all())
        ->toBe(['evento_a', 'evento_b']);
});

it('las paginas de listado en /admin cargan para el super admin', function () {
    $superAdmin = auditoriaSuperAdminConPermiso('admin.auditoria.ver');
    $this->actingAs($superAdmin, 'sistema');

    $this->get(AuditoriaEventoResourceAdmin::getUrl('index', panel: 'admin'))->assertSuccessful();
    $this->get(AuditoriaAccesoResourceAdmin::getUrl('index', panel: 'admin'))->assertSuccessful();
    $this->get(AuditoriaTallerResourceAdmin::getUrl('index', panel: 'admin'))->assertSuccessful();
});
