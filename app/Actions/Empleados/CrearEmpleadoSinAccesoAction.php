<?php

namespace App\Actions\Empleados;

use App\Models\Empleado;

/**
 * Alta de un "empleado sin acceso" (008-spec.md): solo el registro operativo, sin identidad
 * ni credencial. Sin regla de negocio adicional más allá de fijar el taller — no amerita
 * envolverla en una transacción propia (un único INSERT).
 */
class CrearEmpleadoSinAccesoAction
{
    public function execute(int $tallerId, array $datosEmpleado): Empleado
    {
        return Empleado::create([
            ...$datosEmpleado,
            'taller_id' => $tallerId,
            'usuario_sistema_id' => null,
        ]);
    }
}
