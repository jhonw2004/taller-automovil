<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UsuarioMarketplace extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $table = 'usuarios_marketplace';

    /**
     * El marketplace no tiene contraseña ni "recordarme": el login es exclusivamente OAuth Google.
     */
    protected $rememberTokenName = '';

    protected $fillable = [
        'identidad_id',
        'nombre',
        'avatar_url',
        'locale',
        'email_verified_at',
        'ultimo_acceso_at',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'ultimo_acceso_at' => 'datetime',
            'activo' => 'boolean',
        ];
    }

    public function getAuthPassword()
    {
        return null;
    }

    public function identidad(): BelongsTo
    {
        return $this->belongsTo(Identidad::class);
    }

    public function identidadesOauth(): HasMany
    {
        return $this->hasMany(IdentidadOauth::class);
    }
}
