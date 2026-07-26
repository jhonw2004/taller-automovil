<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Identidad extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'identidades';

    protected $fillable = [
        'tipo',
        'email',
        'telefono',
        'estado',
    ];

    public function usuarioMarketplace(): HasOne
    {
        return $this->hasOne(UsuarioMarketplace::class);
    }

    public function usuarioSistema(): HasOne
    {
        return $this->hasOne(UsuarioSistema::class);
    }
}
