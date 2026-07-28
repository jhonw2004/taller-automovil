<?php

namespace App\Actions\Ordenes;

use App\Actions\SequentialCodeGenerator;
use App\Exceptions\BusinessException;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\OrdenTrabajo;
use App\Models\OrdenTrabajoHistorialEstado;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\DB;

/**
 * Valida cliente/vehículo/empleado dentro del taller activo (el global scope de `BelongsToTaller`
 * ya impide leer entidades de otro taller — un `find()` que no encuentra nada porque el registro
 * es de otro taller es indistinguible de "no existe", así que el mensaje de negocio cubre ambos
 * casos por igual). Genera el código con `SequentialCodeGenerator` (ya construido en `017`, no se
 * duplica en una Action `GenerarCodigoOrden` aparte) e inserta el historial inicial en la misma
 * transacción (011-plan.md).
 */
class CrearOrdenTrabajoAction
{
    public function execute(
        int $clienteId,
        int $vehiculoId,
        ?int $empleadoAsignadoId = null,
        ?int $creadoPorUsuarioSistemaId = null,
        array $datos = [],
    ): OrdenTrabajo {
        return DB::transaction(function () use ($clienteId, $vehiculoId, $empleadoAsignadoId, $creadoPorUsuarioSistemaId, $datos) {
            $cliente = Cliente::find($clienteId);

            if (! $cliente) {
                throw new BusinessException('El cliente no pertenece al taller activo.');
            }

            $vehiculo = Vehiculo::find($vehiculoId);

            if (! $vehiculo) {
                throw new BusinessException('El vehículo no pertenece al taller activo.');
            }

            if ($vehiculo->cliente_id !== $cliente->id) {
                throw new BusinessException('El vehículo no pertenece al cliente indicado.');
            }

            if ($empleadoAsignadoId !== null && ! Empleado::find($empleadoAsignadoId)) {
                throw new BusinessException('El empleado asignado no pertenece al taller activo.');
            }

            $codigo = SequentialCodeGenerator::generate((string) $cliente->taller_id, 'OT', 'ordenes_trabajo');

            $orden = OrdenTrabajo::create([
                'taller_id' => $cliente->taller_id,
                'cliente_id' => $cliente->id,
                'vehiculo_id' => $vehiculo->id,
                'empleado_asignado_id' => $empleadoAsignadoId,
                'creado_por_usuario_sistema_id' => $creadoPorUsuarioSistemaId,
                'codigo' => $codigo,
                'estado' => 'PENDIENTE',
                'prioridad' => $datos['prioridad'] ?? 'MEDIA',
                'fecha_recepcion' => now(),
                'fecha_estimada_entrega' => $datos['fecha_estimada_entrega'] ?? null,
                'kilometraje_ingreso' => $datos['kilometraje_ingreso'] ?? null,
                'sintomas' => $datos['sintomas'] ?? null,
                'diagnostico' => $datos['diagnostico'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
                'subtotal_servicios' => 0,
                'subtotal_repuestos' => 0,
                'descuento' => 0,
                'total' => 0,
            ]);

            OrdenTrabajoHistorialEstado::create([
                'orden_trabajo_id' => $orden->id,
                'estado_anterior' => null,
                'estado_nuevo' => 'PENDIENTE',
                'usuario_sistema_id' => $creadoPorUsuarioSistemaId,
                'observacion' => 'Orden creada.',
            ]);

            return $orden;
        });
    }
}
