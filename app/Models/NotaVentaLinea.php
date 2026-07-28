<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sin `BelongsToTaller`: el aislamiento por taller ya lo garantiza la nota padre
 * (`nota_venta_id`), mismo patrón que `OrdenTrabajoServicio`/`OrdenTrabajoRepuesto` (011). Se crea
 * únicamente vía las Actions de `app/Actions/Notas/` — nunca `::create()` directo desde Filament,
 * para que `precio_unitario`/`subtotal` respeten el snapshot y el CHECK de la BD.
 */
class NotaVentaLinea extends Model
{
    protected $table = 'notas_venta_lineas';

    protected $fillable = [
        'nota_venta_id',
        'servicio_catalogo_id',
        'repuesto_id',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'descuento',
        'subtotal',
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

    public function notaVenta(): BelongsTo
    {
        return $this->belongsTo(NotaVenta::class, 'nota_venta_id');
    }

    public function servicioCatalogo(): BelongsTo
    {
        return $this->belongsTo(ServicioCatalogo::class);
    }

    public function repuesto(): BelongsTo
    {
        return $this->belongsTo(Repuesto::class);
    }
}
