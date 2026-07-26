<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CredencialSistema extends Model
{
    use HasFactory;

    protected $table = 'credenciales_sistema';

    /**
     * `password_hash` nunca se loggea, audita ni expone (constitution.md §7).
     */
    protected $hidden = [
        'password_hash',
    ];

    protected $fillable = [
        'usuario_sistema_id',
        'password_hash',
        'debe_cambiar_password',
        'password_changed_at',
        'intentos_fallidos',
        'bloqueado_hasta',
        'password_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'debe_cambiar_password' => 'boolean',
            'password_changed_at' => 'datetime',
            'bloqueado_hasta' => 'datetime',
            'password_expires_at' => 'datetime',
        ];
    }

    public function usuarioSistema(): BelongsTo
    {
        return $this->belongsTo(UsuarioSistema::class);
    }
}
