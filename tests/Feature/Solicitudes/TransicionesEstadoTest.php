<?php

use App\Actions\Roles\AsignarRolAction;
use App\Actions\Solicitudes\AprobarSinCompletarSolicitudAction;
use App\Actions\Solicitudes\AprobarYCompletarSolicitudAction;
use App\Actions\Solicitudes\CancelarSolicitudAction;
use App\Actions\Solicitudes\IniciarRevisionSolicitudAction;
use App\Actions\Solicitudes\RechazarSolicitudAction;
use App\Exceptions\BusinessException;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\SolicitudTaller;
use App\Models\UsuarioSistema;

/**
 * Tabla de transiciones de 004-solicitud-alta-taller/spec.md. Un test por cada fila de la tabla
 * (transición válida) y por cada transición no listada que se rechaza como error de negocio.
 */
function actorConPermisos(array $slugs): UsuarioSistema
{
    $permisos = collect($slugs)->map(fn (string $slug) => Permiso::factory()->create(['slug' => $slug, 'activo' => true]));
    $rol = Rol::factory()->create(['taller_id' => null]);
    $rol->permisos()->attach($permisos->pluck('id'));
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, null, asignadoPor: null);

    return $usuario;
}

// --- Transiciones válidas ---

it('PENDIENTE -> EN_REVISION al iniciar revisión', function () {
    $solicitud = SolicitudTaller::factory()->create(['estado' => 'PENDIENTE']);
    $actor = actorConPermisos(['solicitudes.revisar']);

    $resultado = app(IniciarRevisionSolicitudAction::class)->execute($solicitud, $actor);

    expect($resultado->estado)->toBe('EN_REVISION')
        ->and($resultado->revisada_at)->not->toBeNull();
});

it('PENDIENTE -> APROBADA sin completar', function () {
    $solicitud = SolicitudTaller::factory()->create(['estado' => 'PENDIENTE']);
    $actor = actorConPermisos(['solicitudes.aprobar']);

    $resultado = app(AprobarSinCompletarSolicitudAction::class)->execute($solicitud, $actor);

    expect($resultado->estado)->toBe('APROBADA')
        ->and($resultado->taller_id)->toBeNull();
});

it('EN_REVISION -> APROBADA sin completar', function () {
    $solicitud = SolicitudTaller::factory()->enRevision()->create();
    $actor = actorConPermisos(['solicitudes.aprobar']);

    expect(app(AprobarSinCompletarSolicitudAction::class)->execute($solicitud, $actor)->estado)->toBe('APROBADA');
});

it('PENDIENTE -> COMPLETADA al aprobar y completar', function () {
    $solicitud = SolicitudTaller::factory()->create(['estado' => 'PENDIENTE', 'lat' => -17.78, 'lon' => -63.18]);
    $actor = actorConPermisos(['solicitudes.aprobar', 'solicitudes.crear_taller']);

    $resultado = app(AprobarYCompletarSolicitudAction::class)->execute($solicitud, [], $actor);

    expect($resultado->estado)->toBe('COMPLETADA')
        ->and($resultado->taller_id)->not->toBeNull();
});

it('EN_REVISION -> COMPLETADA al aprobar y completar', function () {
    $solicitud = SolicitudTaller::factory()->enRevision()->create(['lat' => -17.78, 'lon' => -63.18]);
    $actor = actorConPermisos(['solicitudes.aprobar', 'solicitudes.crear_taller']);

    expect(app(AprobarYCompletarSolicitudAction::class)->execute($solicitud, [], $actor)->estado)->toBe('COMPLETADA');
});

it('APROBADA -> COMPLETADA al crear el taller', function () {
    $solicitud = SolicitudTaller::factory()->aprobada()->create(['lat' => -17.78, 'lon' => -63.18]);
    $actor = actorConPermisos(['solicitudes.aprobar', 'solicitudes.crear_taller']);

    expect(app(AprobarYCompletarSolicitudAction::class)->execute($solicitud, [], $actor)->estado)->toBe('COMPLETADA');
});

it('PENDIENTE -> RECHAZADA', function () {
    $solicitud = SolicitudTaller::factory()->create(['estado' => 'PENDIENTE']);
    $actor = actorConPermisos(['solicitudes.rechazar']);

    $resultado = app(RechazarSolicitudAction::class)->execute($solicitud, 'Datos incompletos', $actor);

    expect($resultado->estado)->toBe('RECHAZADA')
        ->and($resultado->motivo_rechazo)->toBe('Datos incompletos')
        ->and($resultado->taller_id)->toBeNull();
});

it('EN_REVISION -> RECHAZADA', function () {
    $solicitud = SolicitudTaller::factory()->enRevision()->create();
    $actor = actorConPermisos(['solicitudes.rechazar']);

    expect(app(RechazarSolicitudAction::class)->execute($solicitud, 'No cumple requisitos', $actor)->estado)->toBe('RECHAZADA');
});

it('APROBADA -> RECHAZADA por error posterior', function () {
    $solicitud = SolicitudTaller::factory()->aprobada()->create();
    $actor = actorConPermisos(['solicitudes.rechazar']);

    expect(app(RechazarSolicitudAction::class)->execute($solicitud, 'Error detectado luego de aprobar', $actor)->estado)->toBe('RECHAZADA');
});

it('PENDIENTE -> CANCELADA por el solicitante', function () {
    $solicitud = SolicitudTaller::factory()->create(['estado' => 'PENDIENTE']);

    expect(app(CancelarSolicitudAction::class)->execute($solicitud)->estado)->toBe('CANCELADA');
});

it('EN_REVISION -> CANCELADA por el solicitante', function () {
    $solicitud = SolicitudTaller::factory()->enRevision()->create();

    expect(app(CancelarSolicitudAction::class)->execute($solicitud)->estado)->toBe('CANCELADA');
});

// --- Transiciones inválidas (no listadas en la tabla) ---

it('no se puede iniciar revisión de una solicitud que no está PENDIENTE', function () {
    $solicitud = SolicitudTaller::factory()->enRevision()->create();
    $actor = actorConPermisos(['solicitudes.revisar']);

    expect(fn () => app(IniciarRevisionSolicitudAction::class)->execute($solicitud, $actor))
        ->toThrow(BusinessException::class);
});

it('una solicitud COMPLETADA no puede volver a PENDIENTE ni aprobarse de nuevo', function () {
    $solicitud = SolicitudTaller::factory()->completada()->create();
    $actor = actorConPermisos(['solicitudes.aprobar', 'solicitudes.crear_taller']);

    expect(fn () => app(AprobarYCompletarSolicitudAction::class)->execute($solicitud, [], $actor))
        ->toThrow(BusinessException::class);
    expect(fn () => app(IniciarRevisionSolicitudAction::class)->execute($solicitud, actorConPermisos(['solicitudes.revisar'])))
        ->toThrow(BusinessException::class);
});

it('una solicitud CANCELADA no puede aprobarse', function () {
    $solicitud = SolicitudTaller::factory()->cancelada()->create();
    $actor = actorConPermisos(['solicitudes.aprobar']);

    expect(fn () => app(AprobarSinCompletarSolicitudAction::class)->execute($solicitud, $actor))
        ->toThrow(BusinessException::class);
});

it('una solicitud RECHAZADA no puede cancelarse ni volver a aprobarse', function () {
    $solicitud = SolicitudTaller::factory()->rechazada()->create();

    expect(fn () => app(CancelarSolicitudAction::class)->execute($solicitud))
        ->toThrow(BusinessException::class);
    expect(fn () => app(AprobarSinCompletarSolicitudAction::class)->execute($solicitud, actorConPermisos(['solicitudes.aprobar'])))
        ->toThrow(BusinessException::class);
});

it('una solicitud APROBADA no puede cancelarse (evento no listado en la tabla)', function () {
    $solicitud = SolicitudTaller::factory()->aprobada()->create();

    expect(fn () => app(CancelarSolicitudAction::class)->execute($solicitud))
        ->toThrow(BusinessException::class);
});

// --- Permisos ---

it('rechaza iniciar revisión sin el permiso solicitudes.revisar', function () {
    $solicitud = SolicitudTaller::factory()->create(['estado' => 'PENDIENTE']);
    $sinPermiso = UsuarioSistema::factory()->create();

    expect(fn () => app(IniciarRevisionSolicitudAction::class)->execute($solicitud, $sinPermiso))
        ->toThrow(BusinessException::class);
});

it('rechaza rechazar sin motivo', function () {
    $solicitud = SolicitudTaller::factory()->create(['estado' => 'PENDIENTE']);
    $actor = actorConPermisos(['solicitudes.rechazar']);

    expect(fn () => app(RechazarSolicitudAction::class)->execute($solicitud, '', $actor))
        ->toThrow(BusinessException::class);
    expect(fn () => app(RechazarSolicitudAction::class)->execute($solicitud, '   ', $actor))
        ->toThrow(BusinessException::class);
});

it('aprobar y completar exige ambos permisos, aprobar y crear_taller', function () {
    $solicitud = SolicitudTaller::factory()->create(['estado' => 'PENDIENTE', 'lat' => -17.78, 'lon' => -63.18]);
    $soloAprobar = actorConPermisos(['solicitudes.aprobar']);

    expect(fn () => app(AprobarYCompletarSolicitudAction::class)->execute($solicitud, [], $soloAprobar))
        ->toThrow(BusinessException::class);
});
