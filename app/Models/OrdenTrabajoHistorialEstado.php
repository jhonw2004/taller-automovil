<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only (constitution.md §2): sin `updated_at`, se crea únicamente vía
 * `CambiarEstadoOrdenAction`/`AnularOrdenTrabajoAction`/`CrearOrdenTrabajoAction` (el historial
 * inicial PENDIENTE), nunca se edita ni se borra.
 */
class OrdenTrabajoHistorialEstado extends Model
{
    const UPDATED_AT = null;

    protected $table = 'ordenes_trabajo_estados_historial';

    protected $fillable = [
        'orden_trabajo_id',
        'estado_anterior',
        'estado_nuevo',
        'usuario_sistema_id',
        'observacion',
    ];

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id');
    }

    public function usuarioSistema(): BelongsTo
    {
        return $this->belongsTo(UsuarioSistema::class);
    }
}
