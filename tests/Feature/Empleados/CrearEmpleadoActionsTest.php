<?php

use App\Actions\Empleados\CrearEmpleadoConAccesoAction;
use App\Actions\Empleados\CrearEmpleadoSinAccesoAction;
use App\Actions\Empleados\VincularAccesoEmpleadoAction;
use App\Exceptions\BusinessException;
use App\Models\AsignacionRol;
use App\Models\CredencialSistema;
use App\Models\Empleado;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use Illuminate\Database\QueryException;
use Spatie\Activitylog\Models\Activity;

function datosEmpleadoDePrueba(): array
{
    return [
        'codigo' => 'EMP-'.fake()->unique()->numerify('####'),
        'nombre' => 'Juan',
        'apellido' => 'Perez',
        'cargo' => 'Mecánico',
        'activo' => true,
    ];
}

it('CrearEmpleadoSinAccesoAction: crea el empleado sin usuario sistema', function () {
    $taller = Taller::factory()->create();

    $empleado = app(CrearEmpleadoSinAccesoAction::class)->execute($taller->id, datosEmpleadoDePrueba());

    expect($empleado->exists)->toBeTrue();
    expect($empleado->taller_id)->toBe($taller->id);
    expect($empleado->usuario_sistema_id)->toBeNull();
});

it('CrearEmpleadoConAccesoAction: crea identidad+usuario+credencial+rol+vinculo+auditoria en una sola transaccion', function () {
    $taller = Taller::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null]);

    $resultado = app(CrearEmpleadoConAccesoAction::class)->execute(
        tallerId: $taller->id,
        datosEmpleado: datosEmpleadoDePrueba(),
        username: 'jperez',
        rol: $rol,
    );

    $empleado = $resultado['empleado'];
    $usuario = $resultado['usuario'];

    expect($empleado->usuario_sistema_id)->toBe($usuario->id);
    expect($usuario->username)->toBe('jperez');
    expect(CredencialSistema::where('usuario_sistema_id', $usuario->id)->first()?->debe_cambiar_password)->toBeTrue();
    expect(AsignacionRol::where('usuario_sistema_id', $usuario->id)->where('rol_id', $rol->id)->where('taller_id', $taller->id)->exists())->toBeTrue();
    expect($resultado['password_temporal'])->toBeString()->not->toBeEmpty();
    expect(Activity::where('description', 'acceso_otorgado_empleado')->exists())->toBeTrue();
});

it('CrearEmpleadoConAccesoAction: el rol debe corresponder al taller del empleado, si no rechaza y hace rollback total', function () {
    $taller = Taller::factory()->create();
    $otroTaller = Taller::factory()->create();
    $rolDeOtroTaller = Rol::factory()->create(['taller_id' => $otroTaller->id]);

    expect(fn () => app(CrearEmpleadoConAccesoAction::class)->execute(
        tallerId: $taller->id,
        datosEmpleado: datosEmpleadoDePrueba(),
        username: 'nuevo.usuario',
        rol: $rolDeOtroTaller,
    ))->toThrow(BusinessException::class);

    // Nada debe haber quedado persistido: ni el Empleado ni un usuario sistema huerfano.
    expect(Empleado::withTrashed()->count())->toBe(0);
    expect(UsuarioSistema::where('username', 'nuevo.usuario')->exists())->toBeFalse();
});

it('CrearEmpleadoConAccesoAction: alta con acceso es transaccional (no deja usuario huerfano si falla)', function () {
    $taller = Taller::factory()->create();
    // Un usuario que ya usa el mismo username fuerza que la creacion de la identidad falle
    // por la unicidad de `usuarios_sistema.username` a nivel de BD.
    UsuarioSistema::factory()->create(['username' => 'duplicado']);
    $rol = Rol::factory()->create(['taller_id' => null]);

    expect(fn () => app(CrearEmpleadoConAccesoAction::class)->execute(
        tallerId: $taller->id,
        datosEmpleado: datosEmpleadoDePrueba(),
        username: 'duplicado',
        rol: $rol,
    ))->toThrow(QueryException::class);

    expect(Empleado::withTrashed()->count())->toBe(0);
});

it('VincularAccesoEmpleadoAction: rechaza si el empleado ya tiene un usuario vinculado', function () {
    $taller = Taller::factory()->create();
    $usuario = UsuarioSistema::factory()->create();
    $empleado = Empleado::factory()->create(['taller_id' => $taller->id, 'usuario_sistema_id' => $usuario->id]);
    $rol = Rol::factory()->create(['taller_id' => null]);

    expect(fn () => app(VincularAccesoEmpleadoAction::class)->execute($empleado, 'otro.username', $rol))
        ->toThrow(BusinessException::class);
});

it('VincularAccesoEmpleadoAction: otorga acceso despues a un empleado que no lo tenia', function () {
    $taller = Taller::factory()->create();
    $empleado = Empleado::factory()->create(['taller_id' => $taller->id, 'usuario_sistema_id' => null]);
    $rol = Rol::factory()->create(['taller_id' => null]);

    $resultado = app(VincularAccesoEmpleadoAction::class)->execute($empleado, 'nuevo.acceso', $rol);

    expect($empleado->fresh()->usuario_sistema_id)->toBe($resultado['usuario']->id);
});
