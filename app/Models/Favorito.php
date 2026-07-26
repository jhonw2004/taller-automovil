<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorito extends Model
{
    use HasFactory;

    protected $table = 'favoritos';

    /**
     * Solo alta/baja (006-resenas-favoritos/plan.md): no hay `updated_at` en la tabla.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'usuario_marketplace_id',
        'taller_id',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(UsuarioMarketplace::class, 'usuario_marketplace_id');
    }

    public function taller(): BelongsTo
    {
        return $this->belongsTo(Taller::class);
    }
}
