<?php

use App\Actions\Empleados\CrearEmpleadoConAccesoAction;
use App\Actions\Empleados\VincularAccesoEmpleadoAction;
use App\Actions\Roles\AsignarRolAction;
use App\Actions\Solicitudes\AprobarSinCompletarSolicitudAction;
use App\Actions\Solicitudes\AprobarYCompletarSolicitudAction;
use App\Actions\Solicitudes\RechazarSolicitudAction;
use App\Models\Empleado;
use App\Models\Notificacion;
use App\Models\Rol;
use App\Models\SolicitudTaller;
use App\Models\Taller;
use App\Models\UsuarioSistema;

/**
 * `solicitud.aprobada`/`solicitud.rechazada`/`usuario.creado` (014-plan.md) se llaman directo
 * desde la Action de origen (sin evento intermedio): a diferencia de `pago.registrado`/
 * `stock.bajo`/`resena.nueva` (eventos que ya existían de sesiones previas sin consumidor), acá no
 * había ningún evento previo, así que no se agrega ceremonia extra solo para tener uno.
 */
function superAdminDePrueba(): UsuarioSistema
{
    $admin = UsuarioSistema::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null, 'slug' => 'super-admin']);
    app(AsignarRolAction::class)->execute($admin, $rol, null, asignadoPor: null);

    return $admin;
}

it('solicitud.aprobada notifica al super admin que aprobo (sin completar)', function () {
    $admin = superAdminDePrueba();
    $solicitud = SolicitudTaller::factory()->create();

    app(AprobarSinCompletarSolicitudAction::class)->execute($solicitud, $admin);

    expect(Notificacion::where('usuario_sistema_id', $admin->id)->where('tipo', 'solicitud.aprobada')->exists())->toBeTrue();
});

it('solicitud.aprobada notifica al super admin que aprobo y completo', function () {
    $admin = superAdminDePrueba();
    $solicitud = SolicitudTaller::factory()->create();

    app(AprobarYCompletarSolicitudAction::class)->execute($solicitud, [], $admin);

    expect(Notificacion::where('usuario_sistema_id', $admin->id)->where('tipo', 'solicitud.aprobada')->exists())->toBeTrue();
});

it('solicitud.rechazada notifica al super admin que rechazo', function () {
    $admin = superAdminDePrueba();
    $solicitud = SolicitudTaller::factory()->create();

    app(RechazarSolicitudAction::class)->execute($solicitud, 'No cumple los requisitos.', $admin);

    $notificacion = Notificacion::where('usuario_sistema_id', $admin->id)->where('tipo', 'solicitud.rechazada')->first();
    expect($notificacion)->not->toBeNull();
    expect($notificacion->titulo)->toContain('No cumple los requisitos.');
});

it('usuario.creado notifica al empleado al otorgarle acceso desde cero', function () {
    $taller = Taller::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null]);

    $resultado = app(CrearEmpleadoConAccesoAction::class)->execute(
        tallerId: $taller->id,
        datosEmpleado: ['codigo' => 'EMP-001', 'nombre' => 'Juan', 'apellido' => 'Perez'],
        username: 'jperez',
        rol: $rol,
    );

    expect(Notificacion::where('usuario_sistema_id', $resultado['usuario']->id)->where('tipo', 'usuario.creado')->exists())->toBeTrue();
});

it('usuario.creado notifica al empleado al otorgarle acceso despues (no en la creacion sin acceso)', function () {
    $taller = Taller::factory()->create();
    $empleado = Empleado::factory()->create(['taller_id' => $taller->id, 'usuario_sistema_id' => null]);
    $rol = Rol::factory()->create(['taller_id' => null]);

    $resultado = app(VincularAccesoEmpleadoAction::class)->execute($empleado, 'nuevo.acceso', $rol);

    expect(Notificacion::where('usuario_sistema_id', $resultado['usuario']->id)->where('tipo', 'usuario.creado')->exists())->toBeTrue();
});
