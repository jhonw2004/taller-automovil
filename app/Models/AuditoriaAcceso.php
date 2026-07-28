<?php

namespace App\Models;

use App\Exceptions\BusinessException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only (constitution.md §2/015-plan.md): sin `updated_at`, `update()`/`delete()`
 * bloqueados a nivel de modelo. Nunca guarda `password_hash` (constitution.md §7) — solo
 * `identificador` (username/email intentado). Se crea únicamente vía
 * `App\Actions\Auditoria\RegistrarAccesoAuditoriaAction`.
 */
class AuditoriaAcceso extends Model
{
    const UPDATED_AT = null;

    protected $table = 'auditoria_accesos';

    protected $fillable = [
        'usuario_marketplace_id',
        'usuario_sistema_id',
        'taller_id',
        'tipo_acceso',
        'resultado',
        'identificador',
        'ip',
        'user_agent',
    ];

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

    public function usuarioMarketplace(): BelongsTo
    {
        return $this->belongsTo(UsuarioMarketplace::class);
    }

    public function usuarioSistema(): BelongsTo
    {
        return $this->belongsTo(UsuarioSistema::class);
    }

    public function taller(): BelongsTo
    {
        return $this->belongsTo(Taller::class);
    }
}
