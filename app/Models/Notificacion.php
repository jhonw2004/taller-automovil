<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Se crea únicamente vía `App\Actions\Notificaciones\CrearNotificacionAction` (014-plan.md: "la
 * feature 014 solo define la tabla y el endpoint de marcar como leída"), nunca `::create()`
 * directo — así el CHECK de destinatario único siempre se valida en PHP antes de tocar la BD, con
 * un mensaje de negocio legible en vez de un error de constraint.
 */
class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';

    protected $fillable = [
        'usuario_marketplace_id',
        'usuario_sistema_id',
        'taller_id',
        'orden_trabajo_id',
        'tipo',
        'titulo',
        'mensaje',
        'data',
        'leida',
        'leida_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'leida' => 'boolean',
            'leida_at' => 'datetime',
        ];
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

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class);
    }

    public function scopeNoLeidas(Builder $query): Builder
    {
        return $query->where('leida', false);
    }

    public function marcarLeida(): void
    {
        if ($this->leida) {
            return;
        }

        $this->leida = true;
        $this->leida_at = now();
        $this->save();
    }
}
