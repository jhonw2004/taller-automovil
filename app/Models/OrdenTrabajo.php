<?php

namespace App\Models;

use App\Exceptions\BusinessException;
use App\Traits\BelongsToTaller;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdenTrabajo extends Model
{
    use BelongsToTaller, HasFactory, SoftDeletes;

    protected $table = 'ordenes_trabajo';

    protected $fillable = [
        'taller_id',
        'codigo',
        'cliente_id',
        'vehiculo_id',
        'empleado_asignado_id',
        'creado_por_usuario_sistema_id',
        'estado',
        'prioridad',
        'fecha_recepcion',
        'fecha_estimada_entrega',
        'fecha_entrega_real',
        'kilometraje_ingreso',
        'sintomas',
        'diagnostico',
        'observaciones',
        'subtotal_servicios',
        'subtotal_repuestos',
        'descuento',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'fecha_recepcion' => 'datetime',
            'fecha_estimada_entrega' => 'date',
            'fecha_entrega_real' => 'datetime',
            'kilometraje_ingreso' => 'integer',
            'subtotal_servicios' => 'decimal:2',
            'subtotal_repuestos' => 'decimal:2',
            'descuento' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function empleadoAsignado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'empleado_asignado_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(UsuarioSistema::class, 'creado_por_usuario_sistema_id');
    }

    public function lineasServicios(): HasMany
    {
        return $this->hasMany(OrdenTrabajoServicio::class, 'orden_trabajo_id');
    }

    public function lineasRepuestos(): HasMany
    {
        return $this->hasMany(OrdenTrabajoRepuesto::class, 'orden_trabajo_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(OrdenTrabajoHistorialEstado::class, 'orden_trabajo_id');
    }

    public function notas(): HasMany
    {
        return $this->hasMany(OrdenTrabajoNota::class, 'orden_trabajo_id');
    }

    /**
     * Recalcula `subtotal_servicios`/`subtotal_repuestos`/`total` a partir de las líneas no
     * ANULADAS (011-spec.md: "Líneas anuladas no suman al total"). Se invoca dentro de la misma
     * transacción que cualquier alta/baja/cambio de estado de línea o de `descuento`
     * (011-plan.md, constitution.md §3.7). Si se pasa `$descuento`, valida y actualiza el
     * descuento de la orden en el mismo paso (no puede ser negativo ni superar la suma de
     * subtotales, 011-spec.md).
     */
    public function recalcularTotales(?float $descuento = null): void
    {
        $subtotalServicios = (float) $this->lineasServicios()
            ->where('estado', '!=', 'ANULADO')
            ->sum('subtotal');

        $subtotalRepuestos = (float) $this->lineasRepuestos()
            ->where('estado', '!=', 'ANULADO')
            ->sum('subtotal');

        $descuentoAplicado = $descuento ?? (float) $this->descuento;

        if ($descuentoAplicado < 0) {
            throw new BusinessException('El descuento no puede ser negativo.');
        }

        if ($descuentoAplicado > $subtotalServicios + $subtotalRepuestos) {
            throw new BusinessException('El descuento no puede superar la suma de los subtotales.');
        }

        $this->subtotal_servicios = $subtotalServicios;
        $this->subtotal_repuestos = $subtotalRepuestos;
        $this->descuento = $descuentoAplicado;
        $this->total = $subtotalServicios + $subtotalRepuestos - $descuentoAplicado;
        $this->save();
    }
}
