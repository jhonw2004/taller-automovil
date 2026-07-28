<?php

namespace App\Models;

use App\Traits\BelongsToTaller;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only (constitution.md §2): nunca se edita ni se borra, sin `updated_at`. Solo se crea
 * a traves de `App\Actions\Inventario\RegistrarMovimientoInventarioAction`.
 */
class InventarioMovimiento extends Model
{
    use BelongsToTaller, HasFactory;

    protected $table = 'inventario_movimientos';

    const UPDATED_AT = null;

    protected $fillable = [
        'taller_id',
        'repuesto_id',
        'tipo_movimiento',
        'cantidad',
        'stock_anterior',
        'stock_resultante',
        'costo_unitario',
        'proveedor_id',
        'orden_trabajo_repuesto_id',
        'usuario_sistema_id',
        'motivo',
        'referencia',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:3',
            'stock_anterior' => 'decimal:3',
            'stock_resultante' => 'decimal:3',
            'costo_unitario' => 'decimal:2',
        ];
    }

    public function repuesto(): BelongsTo
    {
        return $this->belongsTo(Repuesto::class);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function usuarioSistema(): BelongsTo
    {
        return $this->belongsTo(UsuarioSistema::class);
    }
}
