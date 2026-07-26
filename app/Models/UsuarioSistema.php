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
use Illuminate\Support\Collection;

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

    public function asignacionesRol(): HasMany
    {
        return $this->hasMany(AsignacionRol::class);
    }

    /**
     * Solo asignaciones activas y dentro de su rango de vigencia (constitution.md, criterios
     * de 002-roles-permisos). No es lo mismo que `activo = true`: una asignación puede estar
     * activa pero fuera de vigencia (aún no empieza o ya venció).
     */
    public function asignacionesVigentes(): Collection
    {
        return $this->asignacionesRol()
            ->with('rol.permisos')
            ->get()
            ->filter(fn (AsignacionRol $asignacion) => $asignacion->estaVigente());
    }

    /**
     * Evalúa el permiso `$slug` contra las asignaciones vigentes del usuario para `$tallerId`
     * (o las globales, si `$tallerId` es null). Un rol global (SUPER_ADMIN, MARKETPLACE_USER)
     * aplica sin importar el taller activo.
     */
    public function tienePermiso(string $slug, ?int $tallerId = null): bool
    {
        return $this->asignacionesVigentes()
            ->filter(fn (AsignacionRol $asignacion) => $asignacion->taller_id === null || $asignacion->taller_id === $tallerId)
            ->contains(fn (AsignacionRol $asignacion) => $asignacion->rol->activo
                && $asignacion->rol->permisos->contains('slug', $slug)
            );
    }

    public function esSuperAdmin(): bool
    {
        return $this->asignacionesVigentes()
            ->contains(fn (AsignacionRol $asignacion) => $asignacion->rol->slug === 'super-admin');
    }

    /**
     * Filament deniega el acceso a TODO panel por defecto (403) si el modelo no implementa
     * `FilamentUser` — sin esto ningún usuario sistema podría entrar ni a /admin ni a /erp.
     *
     * `/admin` (Super Admin) exige el rol global `SUPER_ADMIN`. `/erp` exige al menos una
     * asignación activa y vigente a algún taller (el taller activo se resuelve después vía
     * `SetTallerActivo`, pendiente de `017`).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->activo) {
            return false;
        }

        if ($panel->getId() === 'admin') {
            return $this->esSuperAdmin();
        }

        return $this->asignacionesVigentes()->contains(fn (AsignacionRol $asignacion) => $asignacion->taller_id !== null);
    }

    public function getFilamentName(): string
    {
        return trim("{$this->nombre} {$this->apellido}");
    }
}
