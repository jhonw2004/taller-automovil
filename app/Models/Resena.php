<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Resena extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'resenas';

    protected $fillable = [
        'usuario_marketplace_id',
        'taller_id',
        'orden_trabajo_id',
        'calificacion',
        'comentario',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'calificacion' => 'integer',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(UsuarioMarketplace::class, 'usuario_marketplace_id');
    }

    public function taller(): BelongsTo
    {
        return $this->belongsTo(Taller::class);
    }
}
