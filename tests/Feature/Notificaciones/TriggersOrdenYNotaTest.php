<?php

use App\Actions\Notas\CrearNotaVentaDirectaAction;
use App\Actions\Ordenes\AnularOrdenTrabajoAction;
use App\Actions\Ordenes\CambiarEstadoOrdenAction;
use App\Actions\Ordenes\CrearOrdenTrabajoAction;
use App\Actions\Roles\AsignarRolAction;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\Notificacion;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\ServicioCatalogo;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use App\Models\Vehiculo;

/**
 * `orden.asignada`/`orden.cambio_estado`/`nota.emitida` (014-plan.md) se enganchan vía
 * `OrdenTrabajoObserver`/`NotaVentaObserver` (primer uso de Observers en el proyecto) porque
 * `empleado_asignado_id`/`estado` cambian por más de un camino (creación, edición estándar de
 * Filament, `CambiarEstadoOrdenAction`, `AnularOrdenTrabajoAction`).
 */
function empleadoConAcceso(Taller $taller): Empleado
{
    $usuario = UsuarioSistema::factory()->create();

    return Empleado::factory()->create(['taller_id' => $taller->id, 'usuario_sistema_id' => $usuario->id]);
}

function ownerDelTaller(Taller $taller): UsuarioSistema
{
    $owner = UsuarioSistema::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null, 'slug' => 'owner']);
    app(AsignarRolAction::class)->execute($owner, $rol, $taller->id, asignadoPor: null);

    return $owner;
}

it('orden.asignada notifica al empleado con acceso cuando se crea la orden ya asignada', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $vehiculo = Vehiculo::factory()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id]);
    $empleado = empleadoConAcceso($taller);

    app(CrearOrdenTrabajoAction::class)->execute($cliente->id, $vehiculo->id, $empleado->id);

    expect(Notificacion::where('usuario_sistema_id', $empleado->usuario_sistema_id)->where('tipo', 'orden.asignada')->exists())->toBeTrue();
});

it('orden.asignada no notifica si el empleado asignado no tiene acceso al sistema', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $vehiculo = Vehiculo::factory()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id]);
    $empleadoSinAcceso = Empleado::factory()->create(['taller_id' => $taller->id, 'usuario_sistema_id' => null]);

    app(CrearOrdenTrabajoAction::class)->execute($cliente->id, $vehiculo->id, $empleadoSinAcceso->id);

    expect(Notificacion::where('tipo', 'orden.asignada')->exists())->toBeFalse();
});

it('orden.asignada notifica cuando se asigna el empleado despues, vía edicion estandar', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $vehiculo = Vehiculo::factory()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id]);
    $orden = app(CrearOrdenTrabajoAction::class)->execute($cliente->id, $vehiculo->id);
    $empleado = empleadoConAcceso($taller);

    // Edición estándar de Filament (sin Action dedicada, 011-plan.md): update() directo.
    $orden->update(['empleado_asignado_id' => $empleado->id]);

    expect(Notificacion::where('usuario_sistema_id', $empleado->usuario_sistema_id)->where('tipo', 'orden.asignada')->exists())->toBeTrue();
});

it('orden.cambio_estado notifica al empleado asignado y al owner del taller', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $vehiculo = Vehiculo::factory()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id]);
    $empleado = empleadoConAcceso($taller);
    $owner = ownerDelTaller($taller);
    $orden = app(CrearOrdenTrabajoAction::class)->execute($cliente->id, $vehiculo->id, $empleado->id);
    Notificacion::query()->delete();

    app(CambiarEstadoOrdenAction::class)->execute($orden, 'EN_DIAGNOSTICO');

    expect(Notificacion::where('usuario_sistema_id', $empleado->usuario_sistema_id)->where('tipo', 'orden.cambio_estado')->exists())->toBeTrue();
    expect(Notificacion::where('usuario_sistema_id', $owner->id)->where('tipo', 'orden.cambio_estado')->exists())->toBeTrue();
});

it('orden.cambio_estado tambien notifica cuando la orden se anula', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $vehiculo = Vehiculo::factory()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id]);
    $owner = ownerDelTaller($taller);
    $orden = app(CrearOrdenTrabajoAction::class)->execute($cliente->id, $vehiculo->id);
    Notificacion::query()->delete();

    app(AnularOrdenTrabajoAction::class)->execute($orden, 'Cliente canceló');

    expect(Notificacion::where('usuario_sistema_id', $owner->id)->where('tipo', 'orden.cambio_estado')->exists())->toBeTrue();
});

it('orden.cambio_estado no se dispara en la creacion (solo transiciones reales)', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $vehiculo = Vehiculo::factory()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id]);
    ownerDelTaller($taller);

    app(CrearOrdenTrabajoAction::class)->execute($cliente->id, $vehiculo->id);

    expect(Notificacion::where('tipo', 'orden.cambio_estado')->exists())->toBeFalse();
});

it('nota.emitida notifica a los usuarios con permiso notas.ver del taller', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $destinatario = UsuarioSistema::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null]);
    $rol->permisos()->attach(Permiso::factory()->create(['slug' => 'notas.ver']));
    app(AsignarRolAction::class)->execute($destinatario, $rol, $taller->id, asignadoPor: null);

    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $taller->id, 'precio_base' => 50]);

    app(CrearNotaVentaDirectaAction::class)->execute(lineas: [
        ['servicio_catalogo_id' => $servicio->id, 'cantidad' => 1],
    ]);

    expect(Notificacion::where('usuario_sistema_id', $destinatario->id)->where('tipo', 'nota.emitida')->exists())->toBeTrue();
});
