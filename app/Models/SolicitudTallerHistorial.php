<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only (constitution.md §2): nunca se edita ni se borra, sin `updated_at`.
 */
class SolicitudTallerHistorial extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_taller_historial';

    const UPDATED_AT = null;

    protected $fillable = [
        'solicitud_taller_id',
        'estado_anterior',
        'estado_nuevo',
        'usuario_sistema_id',
        'observacion',
    ];

    public function solicitudTaller(): BelongsTo
    {
        return $this->belongsTo(SolicitudTaller::class);
    }

    public function usuarioSistema(): BelongsTo
    {
        return $this->belongsTo(UsuarioSistema::class);
    }
}
