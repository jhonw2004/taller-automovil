<?php

use App\Actions\Resenas\ModerarResenaAction;
use App\Actions\Roles\AsignarRolAction;
use App\Exceptions\BusinessException;
use App\Filament\Admin\Resources\ModeracionResenasResource;
use App\Models\Permiso;
use App\Models\Resena;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;

function usuarioSistemaConPermisoModeracion(): UsuarioSistema
{
    $permiso = Permiso::factory()->create(['slug' => 'moderacion.resenas', 'activo' => true]);
    // `slug` en 'super-admin': `canAccessPanel('admin')` exige `esSuperAdmin()` (además del
    // permiso del gate del propio Resource) para poder cargar la página del panel /admin.
    $rol = Rol::factory()->create(['taller_id' => null, 'slug' => 'super-admin']);
    $rol->permisos()->attach($permiso->id);
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, null, asignadoPor: null);

    return $usuario;
}

it('un usuario sistema sin permiso moderacion.resenas no puede moderar', function () {
    $taller = Taller::factory()->create();
    $resena = Resena::factory()->create(['taller_id' => $taller->id]);
    $sinPermiso = UsuarioSistema::factory()->create();

    expect(fn () => app(ModerarResenaAction::class)->execute($resena, 'OCULTA', $sinPermiso))
        ->toThrow(BusinessException::class);

    expect($resena->fresh()->estado)->toBe('PUBLICADA');
});

it('el super admin (con permiso moderacion.resenas) puede ocultar una resena y recalcula la calificacion del taller', function () {
    $taller = Taller::factory()->create();
    $resena = Resena::factory()->create(['taller_id' => $taller->id, 'calificacion' => 5, 'estado' => 'PUBLICADA']);
    $taller->update(['calificacion_promedio' => 5, 'cantidad_resenas' => 1]);

    $moderador = usuarioSistemaConPermisoModeracion();

    app(ModerarResenaAction::class)->execute($resena, 'OCULTA', $moderador);

    expect($resena->fresh()->estado)->toBe('OCULTA')
        ->and((float) $taller->fresh()->calificacion_promedio)->toBe(0.0)
        ->and($taller->fresh()->cantidad_resenas)->toBe(0);
});

it('rechaza un estado de moderacion invalido', function () {
    $resena = Resena::factory()->create();
    $moderador = usuarioSistemaConPermisoModeracion();

    expect(fn () => app(ModerarResenaAction::class)->execute($resena, 'INVALIDO', $moderador))
        ->toThrow(BusinessException::class);
});

it('ModeracionResenasResource no permite crear, editar ni eliminar (solo cambiar estado)', function () {
    $resena = Resena::factory()->create();

    expect(ModeracionResenasResource::canCreate())->toBeFalse()
        ->and(ModeracionResenasResource::canEdit($resena))->toBeFalse()
        ->and(ModeracionResenasResource::canDelete($resena))->toBeFalse();
});

it('ModeracionResenasResource solo lo ve quien tiene el permiso moderacion.resenas', function () {
    $conPermiso = usuarioSistemaConPermisoModeracion();
    $sinPermiso = UsuarioSistema::factory()->create();

    $this->actingAs($conPermiso, 'sistema');
    expect(ModeracionResenasResource::canViewAny())->toBeTrue();

    $this->actingAs($sinPermiso, 'sistema');
    expect(ModeracionResenasResource::canViewAny())->toBeFalse();
});

it('la pagina de listado de moderacion carga para quien tiene el permiso', function () {
    $usuario = usuarioSistemaConPermisoModeracion();

    $this->actingAs($usuario, 'sistema')
        ->get(ModeracionResenasResource::getUrl('index', panel: 'admin'))
        ->assertSuccessful();
});
