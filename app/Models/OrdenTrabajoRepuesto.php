<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sin `BelongsToTaller` (ver `OrdenTrabajoServicio`). Se crea únicamente vía las Actions de
 * `app/Actions/Ordenes/`. El paso a `ENTREGADO` descuenta stock vía
 * `RegistrarMovimientoInventarioAction` pasando `$this->id` como `ordenTrabajoRepuestoId`
 * (010-inventario-repuestos), que ya protege contra doble salida por esta misma línea.
 */
class OrdenTrabajoRepuesto extends Model
{
    protected $table = 'ordenes_trabajo_repuestos';

    protected $fillable = [
        'orden_trabajo_id',
        'repuesto_id',
        'cantidad',
        'precio_unitario',
        'descuento',
        'subtotal',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:3',
            'precio_unitario' => 'decimal:2',
            'descuento' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id');
    }

    public function repuesto(): BelongsTo
    {
        return $this->belongsTo(Repuesto::class);
    }

    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(InventarioMovimiento::class, 'orden_trabajo_repuesto_id');
    }
}
