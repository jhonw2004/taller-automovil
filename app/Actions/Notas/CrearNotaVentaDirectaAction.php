<?php

namespace App\Actions\Notas;

use App\Actions\Inventario\RegistrarMovimientoInventarioAction;
use App\Actions\SequentialCodeGenerator;
use App\Exceptions\BusinessException;
use App\Models\NotaVenta;
use App\Models\NotaVentaLinea;
use App\Models\Repuesto;
use App\Models\ServicioCatalogo;
use Illuminate\Support\Facades\DB;

/**
 * Venta directa sin orden de origen (012-spec.md/plan.md): cada línea referencia un servicio, un
 * repuesto, o ninguno (línea personalizada con descripción libre), nunca ambos a la vez —mismo
 * CHECK que la migración de `notas_venta_lineas`, validado aquí antes de tocar la BD para dar un
 * mensaje de negocio legible en vez de un error de constraint. `precio_unitario` de líneas de
 * servicio/repuesto es un snapshot del catálogo al momento de crear la nota (mismo criterio que
 * `AgregarLineaServicioAction`/`AgregarLineaRepuestoAction` de 011); en líneas personalizadas lo
 * define quien crea la nota, ya que no hay catálogo del que tomarlo.
 *
 * Cada línea de repuesto descuenta stock real de inmediato (`RegistrarMovimientoInventarioAction`,
 * SALIDA, sin `ordenTrabajoRepuestoId` porque no proviene de una línea de orden) — a diferencia de
 * una orden, donde el descuento ocurre recién al marcar la línea `ENTREGADO`. La condición
 * "solo si esa línea no está ya vinculada a una orden que descontó stock" (012-spec.md) se cumple
 * por construcción: esta Action es exclusiva de ventas SIN orden de origen (`orden_trabajo_id`
 * queda NULL), así que ninguna de sus líneas pudo haber descontado stock antes. Toda la creación
 * corre en una sola transacción: si el stock de cualquier línea de repuesto es insuficiente, la
 * nota completa (incluida su fila y las líneas ya insertadas) hace rollback — sin movimiento
 * huérfano ni nota a medio crear.
 */
class CrearNotaVentaDirectaAction
{
    public function execute(
        ?int $clienteId = null,
        ?int $usuarioSistemaId = null,
        array $lineas = [],
    ): NotaVenta {
        if (empty($lineas)) {
            throw new BusinessException('La nota debe tener al menos una línea.');
        }

        $lineasValidadas = array_map(fn (array $data) => $this->validarLinea($data), $lineas);

        return DB::transaction(function () use ($clienteId, $usuarioSistemaId, $lineasValidadas) {
            $tallerId = (int) session('taller_activo_id');

            $codigo = SequentialCodeGenerator::generate((string) $tallerId, 'NV', 'notas_venta');

            $nota = NotaVenta::create([
                'taller_id' => $tallerId,
                'codigo' => $codigo,
                'cliente_id' => $clienteId,
                'orden_trabajo_id' => null,
                'usuario_sistema_id' => $usuarioSistemaId,
                'fecha_emision' => now(),
                'estado' => 'EMITIDA',
                'subtotal' => 0,
                'descuento' => 0,
                'total' => 0,
                'monto_pagado' => 0,
                'saldo' => 0,
            ]);

            foreach ($lineasValidadas as $linea) {
                NotaVentaLinea::create([
                    'nota_venta_id' => $nota->id,
                    'servicio_catalogo_id' => $linea['servicio_catalogo_id'],
                    'repuesto_id' => $linea['repuesto_id'],
                    'descripcion' => $linea['descripcion'],
                    'cantidad' => $linea['cantidad'],
                    'precio_unitario' => $linea['precio_unitario'],
                    'descuento' => $linea['descuento'],
                    'subtotal' => $linea['subtotal'],
                ]);

                if ($linea['repuesto_id'] !== null) {
                    app(RegistrarMovimientoInventarioAction::class)->execute(
                        repuesto: $linea['repuesto'],
                        tipoMovimiento: 'SALIDA',
                        cantidad: (float) $linea['cantidad'],
                        usuarioSistemaId: $usuarioSistemaId,
                        motivo: "Venta directa, nota {$nota->codigo}.",
                        referencia: $nota->codigo,
                    );
                }
            }

            $nota->recalcularTotales();

            return $nota->fresh();
        });
    }

    private function validarLinea(array $data): array
    {
        $servicioCatalogoId = $data['servicio_catalogo_id'] ?? null;
        $repuestoId = $data['repuesto_id'] ?? null;
        $cantidad = (float) ($data['cantidad'] ?? 1);
        $descuento = (float) ($data['descuento'] ?? 0);

        if ($servicioCatalogoId !== null && $repuestoId !== null) {
            throw new BusinessException('Una línea no puede referenciar un servicio y un repuesto a la vez.');
        }

        if ($cantidad <= 0) {
            throw new BusinessException('La cantidad debe ser mayor a cero.');
        }

        $servicio = null;
        $repuesto = null;

        if ($servicioCatalogoId !== null) {
            $servicio = ServicioCatalogo::find($servicioCatalogoId);

            if (! $servicio) {
                throw new BusinessException('El servicio no pertenece al taller activo.');
            }

            $descripcion = $data['descripcion'] ?? $servicio->nombre;
            $precioUnitario = (float) $servicio->precio_base;
        } elseif ($repuestoId !== null) {
            $repuesto = Repuesto::find($repuestoId);

            if (! $repuesto) {
                throw new BusinessException('El repuesto no pertenece al taller activo.');
            }

            $descripcion = $data['descripcion'] ?? $repuesto->nombre;
            $precioUnitario = (float) $repuesto->precio_venta;
        } else {
            $descripcion = trim((string) ($data['descripcion'] ?? ''));

            if ($descripcion === '') {
                throw new BusinessException('La descripción es obligatoria en una línea personalizada.');
            }

            if (! isset($data['precio_unitario'])) {
                throw new BusinessException('El precio unitario es obligatorio en una línea personalizada.');
            }

            $precioUnitario = (float) $data['precio_unitario'];

            if ($precioUnitario < 0) {
                throw new BusinessException('El precio unitario no puede ser negativo.');
            }
        }

        $bruto = $cantidad * $precioUnitario;

        if ($descuento < 0 || $descuento > $bruto) {
            throw new BusinessException('El descuento no puede superar el bruto de la línea.');
        }

        return [
            'servicio_catalogo_id' => $servicioCatalogoId,
            'repuesto_id' => $repuestoId,
            'repuesto' => $repuesto,
            'descripcion' => $descripcion,
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'descuento' => $descuento,
            'subtotal' => $bruto - $descuento,
        ];
    }
}
