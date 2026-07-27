<?php

namespace App\Actions\Empleados;

use App\Actions\Identidad\CrearUsuarioSistemaAction;
use App\Actions\Roles\AsignarRolAction;
use App\Exceptions\BusinessException;
use App\Models\Empleado;
use App\Models\Rol;
use App\Models\UsuarioSistema;
use Illuminate\Support\Facades\DB;

/**
 * Otorga acceso al sistema a un empleado que todavía no lo tiene (008-spec.md): en una sola
 * transacción crea identidad+usuario+credencial (reutiliza `CrearUsuarioSistemaAction` de 001),
 * asigna el rol operativo (reutiliza `AsignarRolAction` de 002 — ya valida que el rol
 * corresponda al taller del empleado) y vincula `empleado.usuario_sistema_id`.
 *
 * Es el paso compartido entre "crear empleado con acceso desde cero" (`CrearEmpleadoConAccesoAction`,
 * que primero crea el `Empleado` y luego llama aquí) y "otorgar acceso después" a un empleado
 * que ya existía sin acceso — no se duplica la lógica de alta de cuenta entre ambos casos.
 */
class VincularAccesoEmpleadoAction
{
    public function __construct(
        private readonly CrearUsuarioSistemaAction $crearUsuarioSistema,
        private readonly AsignarRolAction $asignarRol,
    ) {}

    public function execute(Empleado $empleado, string $username, Rol $rol, ?UsuarioSistema $asignadoPor = null): array
    {
        if ($empleado->usuario_sistema_id !== null) {
            throw new BusinessException('El empleado ya tiene un usuario del sistema vinculado.');
        }

        return DB::transaction(function () use ($empleado, $username, $rol, $asignadoPor) {
            $resultado = $this->crearUsuarioSistema->execute(
                tallerId: $empleado->taller_id,
                username: $username,
                nombre: $empleado->nombre,
                email: $empleado->email,
                apellido: $empleado->apellido,
            );

            $this->asignarRol->execute(
                usuario: $resultado['usuario'],
                rol: $rol,
                tallerId: $empleado->taller_id,
                asignadoPor: $asignadoPor,
            );

            $empleado->update(['usuario_sistema_id' => $resultado['usuario']->id]);

            activity()
                ->causedBy($asignadoPor)
                ->performedOn($empleado)
                ->withProperties(['usuario_sistema_id' => $resultado['usuario']->id, 'rol_id' => $rol->id])
                ->log('acceso_otorgado_empleado');

            return [
                'usuario' => $resultado['usuario'],
                'password_temporal' => $resultado['password_temporal'],
            ];
        });
    }
}
