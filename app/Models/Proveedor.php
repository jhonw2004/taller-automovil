<?php

namespace App\Models;

use App\Traits\BelongsToTaller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proveedor extends Model
{
    use BelongsToTaller, HasFactory, SoftDeletes;

    protected $table = 'proveedores';

    protected $fillable = [
        'taller_id',
        'nombre',
        'contacto',
        'telefono',
        'email',
        'direccion',
        'nit',
        'observaciones',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * `UNIQUE(taller_id, nit) WHERE nit IS NOT NULL` (010-plan.md) — mismo riesgo de '' vs NULL
     * que `Cliente::nitCi()`/`Repuesto::codigoBarras()`, normalizado aqui igual.
     */
    protected function nit(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => filled($value) ? $value : null,
        );
    }

    public function repuestos(): BelongsToMany
    {
        return $this->belongsToMany(Repuesto::class, 'repuestos_proveedores')
            ->withPivot(['codigo_proveedor', 'precio_referencia', 'tiempo_entrega_dias', 'es_principal'])
            ->withTimestamps();
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}
