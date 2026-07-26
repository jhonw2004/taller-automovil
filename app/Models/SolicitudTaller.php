<?php

namespace App\Models;

use App\Casts\GeometryCast;
use App\Traits\HasGeolocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Solicitud pública de alta de taller (004-solicitud-alta-taller). El `token_publico` (no el
 * `id`) es la clave que se expone al solicitante sin cuenta para consultar su estado — las rutas
 * públicas nunca hacen route-model-binding implícito por `id`, buscan explícitamente por
 * `token_publico` (ver `App\Http\Controllers\SolicitudTallerController`).
 */
class SolicitudTaller extends Model
{
    use HasFactory, HasGeolocation;

    protected $table = 'solicitudes_taller';

    protected $fillable = [
        'token_publico',
        'estado',
        'solicitante_nombre',
        'solicitante_email',
        'solicitante_telefono',
        'taller_nombre',
        'taller_direccion',
        'referencia',
        'categoria_principal_id',
        'lat',
        'lon',
        'osm_id',
        'comentario',
        'motivo_rechazo',
        'gestionada_por_usuario_sistema_id',
        'enviada_at',
        'revisada_at',
        'aprobada_at',
        'rechazada_at',
        'completada_at',
        'taller_id',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lon' => 'float',
            'geom' => GeometryCast::class,
            'enviada_at' => 'datetime',
            'revisada_at' => 'datetime',
            'aprobada_at' => 'datetime',
            'rechazada_at' => 'datetime',
            'completada_at' => 'datetime',
        ];
    }

    public function categoriaPrincipal(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_principal_id');
    }

    public function gestionadaPor(): BelongsTo
    {
        return $this->belongsTo(UsuarioSistema::class, 'gestionada_por_usuario_sistema_id');
    }

    public function taller(): BelongsTo
    {
        return $this->belongsTo(Taller::class);
    }

    public function historial(): HasMany
    {
        return $this->hasMany(SolicitudTallerHistorial::class)->orderBy('created_at');
    }
}
