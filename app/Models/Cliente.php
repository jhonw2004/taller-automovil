<?php

namespace App\Models;

use App\Traits\BelongsToTaller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use BelongsToTaller, HasFactory, SoftDeletes;

    protected $fillable = [
        'taller_id',
        'codigo',
        'tipo_persona',
        'nombre',
        'apellido',
        'razon_social',
        'nit_ci',
        'telefono',
        'email',
        'direccion',
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
     * `UNIQUE(taller_id, nit_ci) WHERE nit_ci IS NOT NULL` (007-plan.md) permite múltiples
     * clientes sin NIT/CI en el mismo taller — un valor '' (campo dejado en blanco desde un
     * formulario) rompería esa garantía al no ser NULL. Se normaliza a NULL en el propio modelo,
     * no solo en el Form Request, para que cualquier vía de escritura (Filament, tinker, seeders)
     * respete el mismo contrato.
     */
    protected function nitCi(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => filled($value) ? $value : null,
        );
    }

    public function vehiculos(): HasMany
    {
        return $this->hasMany(Vehiculo::class);
    }

    /**
     * Un cliente inactivo no puede seleccionarse para una orden/nota nueva (007-spec.md); el
     * consumidor real de este scope llega recién en `011-ordenes-trabajo`/`012-notas-venta`.
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}
