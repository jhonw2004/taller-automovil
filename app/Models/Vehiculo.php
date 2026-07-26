<?php

namespace App\Models;

use App\Traits\BelongsToTaller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehiculo extends Model
{
    use BelongsToTaller, HasFactory, SoftDeletes;

    protected $fillable = [
        'taller_id',
        'cliente_id',
        'placa',
        'marca',
        'modelo',
        'anio',
        'color',
        'vin',
        'tipo_vehiculo',
        'kilometraje',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'kilometraje' => 'integer',
            'activo' => 'boolean',
        ];
    }

    /**
     * `placa` se guarda sin guion (`ABC123`, 007-spec.md) sin importar cómo la haya tipeado el
     * usuario (`ABC-123`, `abc 123`, etc.) — se normaliza en el propio modelo, no solo en el
     * Form Request, para que el CHECK `placa ~ '^[A-Z]{3}[0-9]{3}$'` nunca reciba un valor crudo
     * sin normalizar sin importar la vía de escritura.
     */
    protected function placa(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? strtoupper(str_replace(['-', ' '], '', $value)) : $value,
        );
    }

    /**
     * Accessor de presentación (`$vehiculo->placa_formateada`), nunca se escribe: `ABC123` -> `ABC-123`.
     */
    protected function placaFormateada(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->placa ? substr($this->placa, 0, 3).'-'.substr($this->placa, 3) : null,
        );
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Un vehículo inactivo no puede seleccionarse para una orden nueva (007-spec.md); el
     * consumidor real de este scope llega recién en `011-ordenes-trabajo`.
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}
