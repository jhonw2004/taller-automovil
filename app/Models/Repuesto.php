<?php

namespace App\Models;

use App\Traits\BelongsToTaller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Repuesto extends Model
{
    use BelongsToTaller, HasFactory, SoftDeletes;

    protected $fillable = [
        'taller_id',
        'codigo',
        'nombre',
        'descripcion',
        'codigo_barras',
        'unidad_medida_id',
        'stock_actual',
        'stock_minimo',
        'precio_costo',
        'precio_venta',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'stock_actual' => 'decimal:3',
            'stock_minimo' => 'decimal:3',
            'precio_costo' => 'decimal:2',
            'precio_venta' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    /**
     * `UNIQUE(taller_id, codigo_barras) WHERE codigo_barras IS NOT NULL` (010-plan.md) permite
     * varios repuestos sin codigo de barras en el mismo taller — un valor '' (campo dejado en
     * blanco desde un formulario) rompería esa garantía al no ser NULL. Mismo riesgo real
     * detectado y corregido en `Cliente::nitCi()` (007-clientes-vehiculos), aplicado aqui igual.
     */
    protected function codigoBarras(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => filled($value) ? $value : null,
        );
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class);
    }

    public function proveedores(): BelongsToMany
    {
        return $this->belongsToMany(Proveedor::class, 'repuestos_proveedores')
            ->withPivot(['codigo_proveedor', 'precio_referencia', 'tiempo_entrega_dias', 'es_principal'])
            ->withTimestamps();
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(InventarioMovimiento::class);
    }

    /**
     * Un repuesto inactivo no puede seleccionarse para una línea nueva de orden/nota
     * (010-spec.md); el consumidor real llega recién en `011-ordenes-trabajo`/`012-notas-venta`.
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}
