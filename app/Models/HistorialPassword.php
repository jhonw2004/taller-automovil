<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only: sin `updated_at`, nunca se edita ni se borra (constitution.md §2).
 */
class HistorialPassword extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'historial_passwords';

    protected $hidden = [
        'password_hash',
    ];

    protected $fillable = [
        'usuario_sistema_id',
        'password_hash',
    ];

    public function usuarioSistema(): BelongsTo
    {
        return $this->belongsTo(UsuarioSistema::class);
    }
}
