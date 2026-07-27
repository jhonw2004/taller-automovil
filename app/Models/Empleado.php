<?php

namespace App\Models;

use App\Traits\BelongsToTaller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empleado extends Model
{
    use BelongsToTaller, HasFactory, SoftDeletes;

    protected $fillable = [
        'taller_id',
        'usuario_sistema_id',
        'codigo',
        'nombre',
        'apellido',
        'cargo',
        'telefono',
        'email',
        'fecha_ingreso',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
            'activo' => 'boolean',
        ];
    }

    public function usuarioSistema(): BelongsTo
    {
        return $this->belongsTo(UsuarioSistema::class);
    }

    public function tieneAcceso(): bool
    {
        return $this->usuario_sistema_id !== null;
    }

    /**
     * Un empleado inactivo no puede seleccionarse para una orden de trabajo nueva
     * (008-spec.md, mismo criterio que `Cliente::scopeActivos()`/`Vehiculo::scopeActivos()` de
     * 007); el consumidor real llega recién en `011-ordenes-trabajo`.
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}
