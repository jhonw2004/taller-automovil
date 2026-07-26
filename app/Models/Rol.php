<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'taller_id',
        'nombre',
        'slug',
        'descripcion',
        'es_sistema',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'es_sistema' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    /**
     * NULL = rol global (administrado por super admin). No usa BelongsToTaller: ese trait
     * aplicaría un global scope por `session('taller_activo_id')` que ocultaría los roles
     * globales, y el ámbito de un rol se valida explícitamente en AsignarRolAction, no vía scope.
     */
    public function taller(): BelongsTo
    {
        return $this->belongsTo(Taller::class);
    }

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'roles_permisos', 'rol_id', 'permiso_id');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionRol::class);
    }

    public function esGlobal(): bool
    {
        return $this->taller_id === null;
    }
}
