<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sin `BelongsToTaller`: el aislamiento por taller ya lo garantiza la orden padre
 * (`orden_trabajo_id`), mismo patrón que las líneas de pivot de `010` que no repiten `taller_id`.
 * Se crea únicamente vía las Actions de `app/Actions/Ordenes/` — nunca `::create()` directo desde
 * Filament, para que `precio_unitario`/`subtotal` respeten el snapshot y el CHECK de la BD.
 */
class OrdenTrabajoServicio extends Model
{
    protected $table = 'ordenes_trabajo_servicios';

    protected $fillable = [
        'orden_trabajo_id',
        'servicio_catalogo_id',
        'cantidad',
        'precio_unitario',
        'descuento',
        'subtotal',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio_unitario' => 'decimal:2',
            'descuento' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id');
    }

    public function servicioCatalogo(): BelongsTo
    {
        return $this->belongsTo(ServicioCatalogo::class);
    }
}
