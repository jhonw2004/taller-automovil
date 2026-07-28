<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Catalogo global de unidades de medida (010-inventario-repuestos/spec.md), no por taller.
 */
class UnidadMedida extends Model
{
    use HasFactory;

    protected $table = 'unidades_medida';

    protected $fillable = [
        'nombre',
        'simbolo',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}
