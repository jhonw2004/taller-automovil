<?php

use App\Actions\Roles\AsignarRolAction;
use App\Actions\Solicitudes\AprobarYCompletarSolicitudAction;
use App\Exceptions\BusinessException;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\SolicitudTaller;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use Illuminate\Database\QueryException;

function superAdminSolicitudes(): UsuarioSistema
{
    $permisos = collect(['solicitudes.aprobar', 'solicitudes.crear_taller'])
        ->map(fn (string $slug) => Permiso::factory()->create(['slug' => $slug, 'activo' => true]));
    $rol = Rol::factory()->create(['taller_id' => null]);
    $rol->permisos()->attach($permisos->pluck('id'));
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, null, asignadoPor: null);

    return $usuario;
}

it('crea el taller y vincula taller_id a la solicitud en la misma transacción', function () {
    $solicitud = SolicitudTaller::factory()->create([
        'estado' => 'PENDIENTE',
        'taller_nombre' => 'Taller Nuevo',
        'lat' => -17.78,
        'lon' => -63.18,
    ]);
    $actor = superAdminSolicitudes();

    $resultado = app(AprobarYCompletarSolicitudAction::class)->execute($solicitud, [], $actor);

    $taller = Taller::find($resultado->taller_id);
    expect($taller)->not->toBeNull()
        ->and($taller->nombre)->toBe('Taller Nuevo')
        ->and($resultado->estado)->toBe('COMPLETADA')
        ->and($resultado->completada_at)->not->toBeNull();
});

it('permite al super admin editar los datos del taller antes de crearlo', function () {
    $solicitud = SolicitudTaller::factory()->create([
        'estado' => 'PENDIENTE',
        'taller_nombre' => 'Nombre original',
        'lat' => -17.78,
        'lon' => -63.18,
    ]);
    $actor = superAdminSolicitudes();

    $resultado = app(AprobarYCompletarSolicitudAction::class)->execute($solicitud, [
        'nombre' => 'Nombre corregido por el admin',
        'lat' => -17.80,
        'lon' => -63.20,
    ], $actor);

    $taller = Taller::find($resultado->taller_id);
    expect($taller->nombre)->toBe('Nombre corregido por el admin')
        ->and((float) $taller->lat)->toBe(-17.80)
        ->and((float) $taller->lon)->toBe(-63.20);
});

it('no crea taller ni cambia el estado si falta la geolocalización', function () {
    $solicitud = SolicitudTaller::factory()->create(['estado' => 'PENDIENTE', 'lat' => null, 'lon' => null]);
    $actor = superAdminSolicitudes();

    expect(fn () => app(AprobarYCompletarSolicitudAction::class)->execute($solicitud, [], $actor))
        ->toThrow(BusinessException::class);

    expect($solicitud->fresh()->estado)->toBe('PENDIENTE');
    expect(Taller::count())->toBe(0);
});

it('hace rollback completo si la creación del taller falla: no queda taller huérfano ni solicitud a medias', function () {
    // lat fuera de rango viola el CHECK de `talleres` (constitution.md / 003-plan.md) dentro de
    // la misma transacción que actualiza la solicitud — debe abortar ambas operaciones.
    $solicitud = SolicitudTaller::factory()->create(['estado' => 'PENDIENTE', 'lat' => -17.78, 'lon' => -63.18]);
    $actor = superAdminSolicitudes();

    expect(fn () => app(AprobarYCompletarSolicitudAction::class)->execute($solicitud, ['lat' => 999], $actor))
        ->toThrow(QueryException::class);

    expect($solicitud->fresh()->estado)->toBe('PENDIENTE')
        ->and($solicitud->fresh()->taller_id)->toBeNull();
    expect(Taller::count())->toBe(0);
});

it('una solicitud ya COMPLETADA no puede volver a completarse (protección de doble aprobación)', function () {
    $solicitud = SolicitudTaller::factory()->create(['estado' => 'PENDIENTE', 'lat' => -17.78, 'lon' => -63.18]);
    $actor = superAdminSolicitudes();

    $primeraVez = app(AprobarYCompletarSolicitudAction::class)->execute($solicitud, [], $actor);
    expect($primeraVez->estado)->toBe('COMPLETADA');

    // Simula una segunda aprobación con la misma referencia de solicitud (p. ej. dos pestañas del
    // admin abriendo el mismo registro): el `lockForUpdate` + validación de estado dentro de la
    // transacción relee el estado real desde BD y lo rechaza, aunque el objeto en memoria sea el
    // mismo. Esto es lo que en producción evita que dos aprobaciones concurrentes creen dos
    // talleres para la misma solicitud.
    expect(fn () => app(AprobarYCompletarSolicitudAction::class)->execute($solicitud, [], $actor))
        ->toThrow(BusinessException::class);

    expect(Taller::count())->toBe(1);
});
