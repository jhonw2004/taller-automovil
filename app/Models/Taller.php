<?php

namespace App\Models;

use App\Casts\GeometryCast;
use App\Observers\TallerObserver;
use App\Traits\HasGeolocation;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[ObservedBy(TallerObserver::class)]
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

    /**
     * Alias del scope anterior con el nombre que usa 005-marketplace-busqueda-perfil/plan.md
     * (`Taller::activosVisibles()`) — mismo predicado, una sola fuente de verdad para no
     * duplicar la condición de "aparece en el marketplace" en dos sitios.
     */
    public function scopeActivosVisibles(Builder $query): Builder
    {
        return $query->visibleEnMarketplace();
    }

    /**
     * "Abierto ahora" (005-marketplace-busqueda-perfil/spec.md): usa `America/La_Paz`, sin
     * horarios partidos en el MVP. Si `horarios` ya viene eager-loaded (listados de búsqueda),
     * no dispara una query adicional por taller.
     */
    public function estaAbiertoAhora(): bool
    {
        $diaHoy = Carbon::now('America/La_Paz')->isoWeekday();
        $horaActual = Carbon::now('America/La_Paz')->format('H:i:s');

        $horario = $this->horarios->firstWhere('dia_semana', $diaHoy);

        if (! $horario || $horario->cerrado) {
            return false;
        }

        return $horaActual >= $horario->hora_apertura && $horaActual <= $horario->hora_cierre;
    }

    /**
     * Conteo real de entidades hijas activas para la advertencia de soft-delete
     * (003-gestion-talleres/spec.md: "no bloquea, pero advierte"). Envuelto en `Cliente::sinScope()`
     * — primer consumidor real de ese método (017-infraestructura-sistema/spec.md ya documentaba
     * este caso exacto: "Super Admin que necesita acceder a datos de un taller específico...
     * la acción queda registrada en auditoría"). No es estrictamente necesario para ver los datos
     * desde `/admin` (no hay `taller_activo_id` en sesión ahí), pero es la semántica correcta y dado
     * que el propio spec pide auditar este tipo de acceso cross-tenant del Super Admin.
     *
     * @return array<string, int>
     */
    public function contarEntidadesHijasActivas(): array
    {
        return Cliente::sinScope(fn () => [
            'Clientes' => Cliente::where('taller_id', $this->id)->activos()->count(),
            'Vehículos' => Vehiculo::where('taller_id', $this->id)->activos()->count(),
            'Empleados' => Empleado::where('taller_id', $this->id)->activos()->count(),
            'Repuestos' => Repuesto::where('taller_id', $this->id)->activos()->count(),
            'Proveedores' => Proveedor::where('taller_id', $this->id)->activos()->count(),
            'Servicios de catálogo' => ServicioCatalogo::where('taller_id', $this->id)->activos()->count(),
            'Órdenes de trabajo activas' => OrdenTrabajo::where('taller_id', $this->id)->where('estado', '!=', 'ANULADA')->count(),
            'Notas de venta activas' => NotaVenta::where('taller_id', $this->id)->where('estado', '!=', 'ANULADA')->count(),
        ]);
    }

    /**
     * Formato JSON de `GET /api/talleres/search` (005-marketplace-busqueda-perfil/plan.md).
     * `distancia_km` solo se resuelve si el query agregó la columna `distancia` vía
     * `ST_DistanceSphere` (selectRaw), ausente cuando la búsqueda no envía lat/lon.
     */
    public function toSearchJsonResponse(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'slug' => $this->slug,
            'descripcion_corta' => $this->descripcion ? Str::limit($this->descripcion, 120) : null,
            'logo_url' => $this->logo_url,
            'direccion' => $this->direccion,
            'lat' => $this->lat,
            'lon' => $this->lon,
            'calificacion_promedio' => (float) $this->calificacion_promedio,
            'cantidad_resenas' => $this->cantidad_resenas,
            'categorias' => $this->categorias->pluck('nombre')->all(),
            'abierto_ahora' => $this->estaAbiertoAhora(),
            'distancia_km' => $this->distancia !== null ? round($this->distancia / 1000, 2) : null,
        ];
    }
}
