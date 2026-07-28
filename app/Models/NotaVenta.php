<?php

namespace App\Models;

use App\Exceptions\BusinessException;
use App\Traits\BelongsToTaller;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotaVenta extends Model
{
    use BelongsToTaller, HasFactory, SoftDeletes;

    protected $table = 'notas_venta';

    protected $fillable = [
        'taller_id',
        'codigo',
        'cliente_id',
        'orden_trabajo_id',
        'usuario_sistema_id',
        'fecha_emision',
        'estado',
        'subtotal',
        'descuento',
        'total',
        'monto_pagado',
        'saldo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date',
            'subtotal' => 'decimal:2',
            'descuento' => 'decimal:2',
            'total' => 'decimal:2',
            'monto_pagado' => 'decimal:2',
            'saldo' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id');
    }

    public function usuarioSistema(): BelongsTo
    {
        return $this->belongsTo(UsuarioSistema::class);
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(NotaVentaLinea::class, 'nota_venta_id');
    }

    /**
     * Recalcula `subtotal`/`total`/`saldo` a partir de las líneas (012-spec.md: "subtotal = suma
     * de subtotales de líneas no anuladas" — las líneas de nota no tienen estado propio en el MVP,
     * así que todas cuentan). Se invoca en la misma transacción que la creación de líneas
     * (constitution.md §3.7). `monto_pagado` no se toca aquí — su único escritor es
     * `013-pagos` (no existe todavía); se recalcula `saldo` contra el valor actual para mantener el
     * CHECK `saldo = total - monto_pagado` consistente incluso antes de que exista esa feature.
     */
    public function recalcularTotales(?float $descuento = null): void
    {
        $subtotal = (float) $this->lineas()->sum('subtotal');
        $descuentoAplicado = $descuento ?? (float) $this->descuento;

        if ($descuentoAplicado < 0) {
            throw new BusinessException('El descuento no puede ser negativo.');
        }

        if ($descuentoAplicado > $subtotal) {
            throw new BusinessException('El descuento no puede superar el subtotal.');
        }

        $total = $subtotal - $descuentoAplicado;
        $montoPagado = (float) $this->monto_pagado;

        if ($montoPagado > $total) {
            throw new BusinessException('El monto pagado no puede superar el nuevo total.');
        }

        $this->subtotal = $subtotal;
        $this->descuento = $descuentoAplicado;
        $this->total = $total;
        $this->saldo = $total - $montoPagado;
        $this->save();
    }
}
