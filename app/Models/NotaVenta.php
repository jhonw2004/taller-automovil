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

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'nota_venta_id');
    }

    /**
     * Recalcula `subtotal`/`total`/`monto_pagado`/`saldo`/`estado` (012-spec.md + 013-spec.md).
     * `subtotal` = suma de subtotales de líneas (sin estado propio en el MVP, todas cuentan).
     * `monto_pagado` ya no es un valor que se "respeta": desde que `013-pagos` existe, es siempre
     * la suma de `pagos` en estado `CONFIRMADO` — su único escritor real es
     * `RegistrarPagoAction`/`AnularPagoAction`, que llaman a este método tras crear/anular un pago
     * (nunca escriben la columna directamente). `estado` se deriva de `saldo`/`monto_pagado`
     * (013-spec.md: `saldo=0` y `total>0` → `PAGADA`; `saldo>0` y `monto_pagado>0` → `PENDIENTE`;
     * `monto_pagado=0` → `EMITIDA`) salvo que la nota ya esté `ANULADA` — una nota anulada nunca se
     * recalcula (013-spec.md: los pagos nuevos sobre una nota anulada se rechazan antes de llegar
     * aquí, pero el guard queda explícito por si se invoca directamente). Se invoca en la misma
     * transacción que la creación de líneas o de un pago (constitution.md §3.7).
     */
    public function recalcularTotales(?float $descuento = null): void
    {
        if ($this->estado === 'ANULADA') {
            return;
        }

        $subtotal = (float) $this->lineas()->sum('subtotal');
        $descuentoAplicado = $descuento ?? (float) $this->descuento;

        if ($descuentoAplicado < 0) {
            throw new BusinessException('El descuento no puede ser negativo.');
        }

        if ($descuentoAplicado > $subtotal) {
            throw new BusinessException('El descuento no puede superar el subtotal.');
        }

        $total = $subtotal - $descuentoAplicado;
        $montoPagado = round((float) $this->pagos()->where('estado', 'CONFIRMADO')->sum('monto'), 2);

        if ($montoPagado > $total) {
            throw new BusinessException('El monto pagado no puede superar el total de la nota.');
        }

        $saldo = round($total - $montoPagado, 2);

        $this->subtotal = $subtotal;
        $this->descuento = $descuentoAplicado;
        $this->total = $total;
        $this->monto_pagado = $montoPagado;
        $this->saldo = $saldo;
        $this->estado = match (true) {
            $total > 0 && $saldo <= 0.0 => 'PAGADA',
            $montoPagado > 0 => 'PENDIENTE',
            default => 'EMITIDA',
        };
        $this->save();
    }
}
