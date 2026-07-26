<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionRol extends Model
{
    protected $table = 'asignaciones_rol';

    protected $fillable = [
        'usuario_sistema_id',
        'rol_id',
        'taller_id',
        'activo',
        'asignado_por_usuario_sistema_id',
        'vigente_desde',
        'vigente_hasta',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }

    public function usuarioSistema(): BelongsTo
    {
        return $this->belongsTo(UsuarioSistema::class);
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class);
    }

    public function taller(): BelongsTo
    {
        return $this->belongsTo(Taller::class);
    }

    public function asignadoPor(): BelongsTo
    {
        return $this->belongsTo(UsuarioSistema::class, 'asignado_por_usuario_sistema_id');
    }

    public function estaVigente(): bool
    {
        if (! $this->activo) {
            return false;
        }

        $hoy = now()->toDateString();

        if ($this->vigente_desde && $this->vigente_desde->toDateString() > $hoy) {
            return false;
        }

        if ($this->vigente_hasta && $this->vigente_hasta->toDateString() < $hoy) {
            return false;
        }

        return true;
    }
}
