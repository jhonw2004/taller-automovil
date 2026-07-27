<?php

namespace App\Models;

use App\Traits\BelongsToTaller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicioCatalogo extends Model
{
    use BelongsToTaller, HasFactory, SoftDeletes;

    protected $table = 'servicios_catalogo';

    protected $fillable = [
        'taller_id',
        'codigo',
        'nombre',
        'descripcion',
        'precio_base',
        'duracion_minutos',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio_base' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    /**
     * Un servicio inactivo no puede seleccionarse para una línea nueva de orden/nota
     * (009-spec.md); el consumidor real llega recién en `011-ordenes-trabajo`/`012-notas-venta`.
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}
