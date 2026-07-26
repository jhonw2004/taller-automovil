<?php

namespace App\Models;

use App\Casts\GeometryCast;
use App\Traits\HasGeolocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Taller extends Model
{
    use HasFactory, HasGeolocation, HasSlug, SoftDeletes;

    protected $table = 'talleres';

    protected $fillable = [
        'propietario_usuario_sistema_id',
        'nombre',
        'descripcion',
        'telefono',
        'email',
        'nit',
        'direccion',
        'lat',
        'lon',
        'osm_id',
        'logo_url',
        'estado',
        'visible_en_mapa',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lon' => 'float',
            'geom' => GeometryCast::class,
            'visible_en_mapa' => 'boolean',
            'calificacion_promedio' => 'decimal:2',
            'cantidad_resenas' => 'integer',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('nombre')
            ->saveSlugsTo('slug');
    }

    public function propietario(): BelongsTo
    {
        return $this->belongsTo(UsuarioSistema::class, 'propietario_usuario_sistema_id');
    }

    public function categorias(): BelongsToMany
    {
        return $this->belongsToMany(Categoria::class, 'talleres_categorias')
            ->withPivot('orden')
            ->withTimestamps();
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(TallerHorario::class);
    }

    /**
     * Condición de aparición en el marketplace (003-gestion-talleres/spec.md): ACTIVO, visible,
     * con geolocalización y sin soft delete. `SoftDeletes` ya excluye `deleted_at` por defecto.
     */
    public function scopeVisibleEnMarketplace(Builder $query): Builder
    {
        return $query->where('estado', 'ACTIVO')
            ->where('visible_en_mapa', true)
            ->whereNotNull('geom');
    }
}
