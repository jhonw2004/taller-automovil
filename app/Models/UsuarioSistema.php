<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UsuarioSistema extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory, SoftDeletes;

    protected $table = 'usuarios_sistema';

    /**
     * Sin "recordarme": la sesión del guard `sistema` expira a los 30 min de inactividad
     * (CheckSessionExpiration), un remember-token la socavaría.
     */
    protected $rememberTokenName = '';

    protected $fillable = [
        'identidad_id',
        'username',
        'nombre',
        'apellido',
        'ultimo_acceso_at',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'ultimo_acceso_at' => 'datetime',
            'activo' => 'boolean',
        ];
    }

    /**
     * El hash de password no vive en este modelo, sino en la relación 1:1 CredencialSistema.
     */
    public function getAuthPassword()
    {
        return $this->credencialSistema?->password_hash;
    }

    public function identidad(): BelongsTo
    {
        return $this->belongsTo(Identidad::class);
    }

    public function credencialSistema(): HasOne
    {
        return $this->hasOne(CredencialSistema::class);
    }

    public function historialPasswords(): HasMany
    {
        return $this->hasMany(HistorialPassword::class);
    }

    /**
     * Filament deniega el acceso a TODO panel por defecto (403) si el modelo no implementa
     * `FilamentUser` — sin esto ningún usuario sistema podría entrar ni a /admin ni a /erp.
     *
     * Restricción real por panel/rol (`SUPER_ADMIN` para /admin, asignación de rol activa por
     * taller para /erp) pendiente de `002-roles-permisos`, que todavía no existe. Por ahora
     * solo se exige que el usuario esté activo.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->activo;
    }

    public function getFilamentName(): string
    {
        return trim("{$this->nombre} {$this->apellido}");
    }
}
