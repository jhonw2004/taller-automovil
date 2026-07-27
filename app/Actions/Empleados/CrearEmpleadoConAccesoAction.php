<?php

namespace App\Actions\Empleados;

use App\Models\Empleado;
use App\Models\Rol;
use App\Models\UsuarioSistema;
use Illuminate\Support\Facades\DB;

/**
 * Alta de un "empleado con acceso" (008-spec.md): crea el registro operativo `Empleado` y,
 * en la misma transacción, le otorga acceso al sistema vía `VincularAccesoEmpleadoAction`
 * (identidad+usuario+credencial+rol+auditoría). Si el rol no corresponde al taller del
 * empleado (validado dentro de `AsignarRolAction`) o cualquier otro paso falla, rollback
 * completo: no debe quedar ni el `Empleado` ni un usuario sistema huérfano.
 */
class CrearEmpleadoConAccesoAction
{
    public function __construct(
        private readonly VincularAccesoEmpleadoAction $vincularAcceso,
    ) {}

    public function execute(
        int $tallerId,
        array $datosEmpleado,
        string $username,
        Rol $rol,
        ?UsuarioSistema $asignadoPor = null,
    ): array {
        return DB::transaction(function () use ($tallerId, $datosEmpleado, $username, $rol, $asignadoPor) {
            $empleado = Empleado::create([
                ...$datosEmpleado,
                'taller_id' => $tallerId,
            ]);

            $resultado = $this->vincularAcceso->execute($empleado, $username, $rol, $asignadoPor);

            return [
                'empleado' => $empleado,
                'usuario' => $resultado['usuario'],
                'password_temporal' => $resultado['password_temporal'],
            ];
        });
    }
}
