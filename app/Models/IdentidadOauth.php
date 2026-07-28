<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdentidadOauth extends Model
{
    use HasFactory;

    protected $table = 'identidades_oauth';

    protected $fillable = [
        'usuario_marketplace_id',
        'provider',
        'provider_subject',
        'email',
        'nombre',
        'avatar_url',
        'email_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    public function usuarioMarketplace(): BelongsTo
    {
        return $this->belongsTo(UsuarioMarketplace::class);
    }
}
