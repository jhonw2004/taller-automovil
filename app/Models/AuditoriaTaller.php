<?php

namespace App\Models;

use App\Casts\GeometryCast;
use App\Exceptions\BusinessException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only (constitution.md §2/015-plan.md): sin `updated_at`, `update()`/`delete()`
 * bloqueados a nivel de modelo. `geom_old`/`geom_new` reutilizan `App\Casts\GeometryCast`
 * (mismo cast que `Taller.geom`) — cada columna se castea independientemente a/desde
 * `['lat' => .., 'lon' => ..]`, el cast no depende del nombre de columna. Se crea únicamente
 * vía `App\Observers\TallerObserver`.
 */
class AuditoriaTaller extends Model
{
    const UPDATED_AT = null;

    protected $table = 'auditoria_talleres';

    protected $fillable = [
        'taller_id',
        'usuario_sistema_id',
        'operacion',
        'datos_old',
        'datos_new',
        'lat_old',
        'lat_new',
        'lon_old',
        'lon_new',
        'geom_old',
        'geom_new',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'datos_old' => 'array',
            'datos_new' => 'array',
            'lat_old' => 'float',
            'lat_new' => 'float',
            'lon_old' => 'float',
            'lon_new' => 'float',
            'geom_old' => GeometryCast::class,
            'geom_new' => GeometryCast::class,
        ];
    }

    public function save(array $options = [])
    {
        if ($this->exists) {
            throw new BusinessException('Los registros de auditoría son append-only: no se pueden editar.');
        }

        return parent::save($options);
    }

    public function delete()
    {
        throw new BusinessException('Los registros de auditoría son append-only: no se pueden eliminar.');
    }

    public function taller(): BelongsTo
    {
        return $this->belongsTo(Taller::class);
    }

    public function usuarioSistema(): BelongsTo
    {
        return $this->belongsTo(UsuarioSistema::class);
    }
}
