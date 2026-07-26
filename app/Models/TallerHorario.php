<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TallerHorario extends Model
{
    use HasFactory;

    protected $table = 'talleres_horarios';

    protected $fillable = [
        'taller_id',
        'dia_semana',
        'hora_apertura',
        'hora_cierre',
        'cerrado',
    ];

    protected function casts(): array
    {
        return [
            'dia_semana' => 'integer',
            'cerrado' => 'boolean',
        ];
    }

    public function taller(): BelongsTo
    {
        return $this->belongsTo(Taller::class);
    }
}
